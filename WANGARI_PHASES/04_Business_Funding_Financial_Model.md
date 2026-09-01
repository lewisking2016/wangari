# WANGARI — Business, Funding & Financial Model (v2 — FREE STACK)
## Zero-Dollar Infrastructure, Premium Results

**Document Owner:** Lewis (Founder, iMeanTech)
**Last Updated:** September 2026
**Philosophy:** "Why pay $10,000/month when free tools give you the same results?"

---

## THE CORE PRINCIPLE

> "The best things in life are free. The second best are very expensive." — Coco Chanel

You don't need Twilio ($42K/month). You don't need AWS ($5K/month). You don't need OpenAI API ($2K/month).

You need:
- **Contabo VPS** ($6/month) — for hosting
- **Evolution API** (free, self-hosted) — for WhatsApp
- **Ollama + Mistral** (free, self-hosted) — for AI
- **Whisper** (free, self-hosted) — for voice-to-text
- **MySQL** (free, on Contabo) — for database
- **Mailcow** (free, self-hosted) — for email

**Total infrastructure cost: $6/month.** Not $6,000. Not $600. Six dollars.

---

## PART 1: FREE STACK BREAKDOWN

### 1. WhatsApp Bot — Evolution API (FREE)

**What is it:** Self-hosted WhatsApp automation server built on Baileys library. Connects your personal WhatsApp account without Meta API fees.

| Item | Twilio (Paid) | Evolution API (Free) |
|---|---|---|
| Setup cost | $0 | $0 |
| Monthly cost | $42,000-84,000 (at 1,000 users) | **$0** |
| Per-message cost | KES 0.70/message | **$0** |
| Self-hosted | No | Yes (runs on your VPS) |
| Multi-service | Limited | Full (chat, media, groups) |
| WhatsApp Web protocol | No (official API) | Yes (Baileys library) |

**How to install on Contabo:**
```bash
# Install Docker on Contabo
curl -fsSL https://get.docker.com | sh

# Run Evolution API
docker run -d \
  --name evolution-api \
  -p 8080:8080 \
  -v ./evolution:/evolution/instances \
  atendai/evolution-api:latest
```

**Cost: $0/month.** You just need a WhatsApp number (use a free Safaricom SIM).

**⚠️ Important note:** Evolution API uses the unofficial WhatsApp Web protocol. It's free and works great for small-to-medium scale. For enterprise scale (100K+ messages/day), you'd eventually need the official Meta API. But for 1,000 farmers? Evolution API is perfect.

---

### 2. VPS Hosting — Contabo (€5.50/month)

**Best value VPS on Earth.** Period.

| Plan | Price | Specs | Best For |
|---|---|---|---|
| **Cloud VPS 4** | **€5.50/month (~$6)** | 4 vCPU, 8GB RAM, 100GB SSD | ✅ START HERE |
| Cloud VPS 6 | €7.50/month (~$8) | 6 vCPU, 12GB RAM, 200GB SSD | 500-2,000 users |
| Cloud VPS 8 | €14.00/month (~$15) | 8 vCPU, 24GB RAM, 300GB SSD | 2,000-5,000 users |

**What runs on Contabo VPS 4 ($6/month):**
- Wangari web app (PHP + MySQL)
- Evolution API (WhatsApp bot)
- Ollama (AI — Mistral Small)
- Whisper (voice-to-text)
- Nginx (reverse proxy)
- Let's Encrypt SSL (free)

**Comparison:**
| Provider | Monthly Cost | Specs |
|---|---|---|
| **Contabo VPS 4** | **$6** | 4 vCPU, 8GB RAM, 100GB SSD |
| AWS EC2 t3.medium | $30-50 | 2 vCPU, 4GB RAM, 8GB EBS |
| DigitalOcean | $24-48 | 2-4 vCPU, 4-8GB RAM |
| Linode | $24-60 | 2-4 vCPU, 4-8GB RAM |
| Hetzner | $5-10 | 2-4 vCPU, 4-8GB RAM |

**Contabo wins on price-per-GB by 3-5x.** The catch? Slightly slower disk I/O. But for a PHP app + MySQL, it's more than enough.

---

### 3. AI Chatbot — Ollama + Mistral Small (FREE)

**What is it:** Run AI models locally on your VPS. No API keys, no per-token costs.

| Item | OpenAI API (Paid) | Ollama + Mistral (Free) |
|---|---|---|
| Setup cost | $0 | $0 |
| Per-query cost | $0.015/1K tokens | **$0** |
| Monthly (1K users) | $50-200 | **$0** |
| Data privacy | Data sent to OpenAI | **Data stays on YOUR server** |
| Swahili support | Good | Good (Mistral multilingual) |

**Best models for farm advice (runs on 8GB RAM):**

| Model | Size | RAM Needed | Quality | Best For |
|---|---|---|---|---|
| **Mistral Small 3.1** | 24GB | 8GB+ | ⭐⭐⭐⭐ | General advice, Swahili |
| **Llama 3.2 3B** | 2GB | 4GB+ | ⭐⭐⭐ | Quick responses, low RAM |
| **Gemma 2 2B** | 2GB | 4GB+ | ⭐⭐⭐ | Fast, lightweight |
| **Phi-3 Mini** | 4GB | 6GB+ | ⭐⭐⭐⭐ | Reasoning, calculations |

**Recommended:** Start with **Llama 3.2 3B** (fast, light) and upgrade to **Mistral Small 3.1** when you have more users.

**How to install on Contabo:**
```bash
# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Pull Mistral Small
ollama pull mistral-small3.1

# Pull Llama 3.2 (lighter)
ollama pull llama3.2

# Start serving
ollama serve
```

**API endpoint:** `http://localhost:11434/api/generate`

**Integrate with Wangari:**
```php
// Simple AI query - costs $0
function askWangariAI(string $question, array $farmData): string {
    $prompt = "You are Wangari, a Kenyan farm management AI assistant. 
    Answer in simple English or Swahili. Be helpful and specific.
    
    Farm data: " . json_encode($farmData) . "
    
    Question: $question";
    
    $response = file_get_contents('http://localhost:11434/api/generate', false, 
        stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode([
                    'model' => 'mistral-small3.1',
                    'prompt' => $prompt,
                    'stream' => false
                ])
            ]
        ])
    );
    
    $result = json_decode($response, true);
    return $result['response'] ?? 'Sorry, I could not process that.';
}
```

---

### 4. Speech-to-Text — OpenAI Whisper (FREE)

**What is it:** Open-source speech recognition model. Supports 99+ languages including Swahili.

| Item | Google Speech API (Paid) | Whisper (Free) |
|---|---|---|
| Per-second cost | $0.006/15sec | **$0** |
| Monthly (1K users) | $100-300 | **$0** |
| Swahili support | Good | Good |
| Self-hosted | No | Yes |
| Offline capable | No | Yes |

**How to install on Contabo:**
```bash
# Install Python + Whisper
pip install openai-whisper

# Or use faster-whisper (optimized)
pip install faster-whisper

# Test with a file
whisper audio.wav --language sw --model base
```

**For real-time voice messages via WhatsApp:**
```php
// When farmer sends voice message via WhatsApp
function processVoiceMessage(string $audioFilePath): string {
    // Use Whisper to transcribe
    $transcription = shell_exec(
        "whisper $audioFilePath --language sw --model base --output_format txt 2>/dev/null"
    );
    
    return trim($transcription);
}
```

---

### 5. Email — Mailcow (FREE)

**What is it:** Self-hosted email server with webmail, CalDAV, CardDAV.

| Item | SendGrid (Paid) | Mailcow (Free) |
|---|---|---|
| Monthly cost | $20-50 | **$0** |
| Emails/month | 100K (then extra) | **Unlimited** |
| Webmail | No | Yes |
| Self-hosted | No | Yes |
| Calendars | No | Yes |

**How to install on Contabo:**
```bash
# Install Mailcow
git clone https://github.com/mailcow/mailcow-dockerized
cd mailcow-dockerized
./generate_config.sh
docker-compose up -d
```

---

### 6. Database — MySQL (FREE)

Already included with your Contabo VPS. No need for AWS RDS ($15/month).

```bash
# Install MySQL on Contabo
apt install mysql-server
mysql_secure_installation
```

---

### 7. SMS — Africa's Talking (CHEAP)

Africa's Talking is already very affordable:
- SMS: KES 0.20/SMS (~$0.0015)
- USSD: KES 0.30/session (~$0.002)
- 1,000 users × 2 SMS/day × 30 days = **KES 12,000/month (~$90)**

This is the ONE paid service worth keeping. The ROI is massive.

---

## PART 2: REVISED COST MODEL

### Monthly Costs (1,000 Active Users)

| Item | Paid Stack (Old) | Free Stack (New) | Savings |
|---|---|---|---|
| **VPS/Cloud** | $50-100 (AWS/DigitalOcean) | **$6 (Contabo)** | $44-94 |
| **WhatsApp API** | $42,000-84,000 (Twilio) | **$0 (Evolution API)** | $42,000-84,000 |
| **AI Chatbot** | $50-200 (OpenAI API) | **$0 (Ollama + Mistral)** | $50-200 |
| **Speech-to-Text** | $100-300 (Google) | **$0 (Whisper)** | $100-300 |
| **Email** | $20-50 (SendGrid) | **$0 (Mailcow)** | $20-50 |
| **Database** | $15-30 (AWS RDS) | **$0 (MySQL on Contabo)** | $15-30 |
| **SMS/USSD** | $90 (Africa's Talking) | **$90 (Africa's Talking)** | $0 |
| **Domain/SSL** | $15 (domain) + $0 (Let's Encrypt) | **$15 + $0** | $0 |
| **Agent commissions** | $100,000 (2 agents × 4 months) | **$100,000** | $0 |
| **Misc** | $15,000 | **$15,000** | $0 |
| **TOTAL MONTHLY** | **~KES 200,000 ($1,540)** | **~KES 115,000 ($885)** | **-42%** |

### Break-Even Analysis (Revised)

| Metric | Paid Stack | Free Stack |
|---|---|---|
| Monthly fixed costs | KES 200,000 | **KES 115,000** |
| ARPU (blended) | KES 1,000 | KES 1,000 |
| Break-even point | 200 paying users | **115 paying users** |
| Free users needed (20% conversion) | 1,000 | **575** |

**You break even 40% faster with the free stack.**

---

## PART 3: REVISED FINANCIAL PROJECTIONS

### Year 1 Projections (Free Stack)

| Month | Free Users | Paying Users | Revenue | Costs | Net |
|---|---|---|---|---|---|
| Sep 2026 | 10 | 0 | KES 0 | KES 115,000 | -KES 115,000 |
| Oct 2026 | 90 | 0 | KES 0 | KES 115,000 | -KES 115,000 |
| Nov 2026 | 800 | 100 | KES 100,000 | KES 130,000 | -KES 30,000 |
| Dec 2026 | 900 | 200 | KES 200,000 | KES 140,000 | +KES 60,000 |
| Jan 2027 | 1,000 | 300 | KES 300,000 | KES 150,000 | +KES 150,000 |
| Feb 2027 | 1,200 | 400 | KES 400,000 | KES 160,000 | +KES 240,000 |
| Mar 2027 | 1,500 | 500 | KES 500,000 | KES 175,000 | +KES 325,000 |
| Apr 2027 | 2,000 | 700 | KES 700,000 | KES 200,000 | +KES 500,000 |
| May 2027 | 2,500 | 900 | KES 900,000 | KES 230,000 | +KES 670,000 |
| Jun 2027 | 3,000 | 1,100 | KES 1,100,000 | KES 260,000 | +KES 840,000 |
| Jul 2027 | 3,500 | 1,300 | KES 1,300,000 | KES 290,000 | +KES 1,010,000 |
| Aug 2027 | 4,000 | 1,500 | KES 1,500,000 | KES 320,000 | +KES 1,180,000 |
| **Year 1 Total** | | | **KES 6,000,000** | **KES 2,185,000** | **+KES 3,815,000** |

**Year 1 profit with free stack: KES 3.8M ($29,300) vs KES 3M ($23,100) with paid stack.**

That's **KES 815,000 more profit** just from choosing free tools.

---

## PART 4: INFRASTRUCTURE ARCHITECTURE (FREE STACK)

```
┌─────────────────────────────────────────────────────┐
│              CONTABO VPS 4 ($6/month)                │
│              4 vCPU | 8GB RAM | 100GB SSD            │
├─────────────────────────────────────────────────────┤
│                                                       │
│  ┌─────────────────┐  ┌─────────────────┐            │
│  │   NGINX          │  │   MYSQL          │            │
│  │   (Reverse Proxy)│  │   (Database)     │            │
│  │   Port 80/443    │  │   Port 3306      │            │
│  └────────┬────────┘  └────────┬────────┘            │
│           │                     │                      │
│  ┌────────┴────────┐  ┌────────┴────────┐            │
│  │   WANGARI APP    │  │   EVOLUTION API  │            │
│  │   (PHP + HTML)   │  │   (WhatsApp Bot) │            │
│  │   Port 8080      │  │   Port 8081      │            │
│  └────────┬────────┘  └────────┬────────┘            │
│           │                     │                      │
│  ┌────────┴────────┐  ┌────────┴────────┐            │
│  │   OLLAMA         │  │   WHISPER        │            │
│  │   (AI Chatbot)   │  │   (Voice-to-Text)│            │
│  │   Port 11434     │  │   Port 5000      │            │
│  └─────────────────┘  └─────────────────┘            │
│                                                       │
│  ┌─────────────────┐  ┌─────────────────┐            │
│  │   MAILCOW        │  │   LET'S ENCRYPT  │            │
│  │   (Email Server) │  │   (Free SSL)     │            │
│  │   Port 443/993   │  │                  │            │
│  └─────────────────┘  └─────────────────┘            │
│                                                       │
└─────────────────────────────────────────────────────┘

EXTERNAL SERVICES (Free/Cheap):
├── Africa's Talking (SMS/USSD) — KES 0.20/SMS
├── Google Fonts (CDN) — Free
├── Cloudflare (CDN + DDoS) — Free tier
├── GitHub (Code hosting) — Free
└── Google Analytics (Traffic) — Free
```

---

## PART 5: DEPLOYMENT GUIDE (Contabo Setup)

### Step 1: Order Contabo VPS
1. Go to contabo.com
2. Select Cloud VPS 4 (€5.50/month)
3. Choose Ubuntu 22.04 LTS
4. Select nearest data center (EU is fine for Kenya — latency is ~150ms)
5. Pay and get your IP + root password

### Step 2: Initial Server Setup
```bash
# SSH into your VPS
ssh root@YOUR_IP

# Update system
apt update && apt upgrade -y

# Install essential packages
apt install -y nginx mysql-server php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd composer git

# Install Docker (for Evolution API, Mailcow)
curl -fsSL https://get.docker.com | sh
```

### Step 3: Deploy Wangari
```bash
# Clone your repo
cd /var/www
git clone https://github.com/YOUR_REPO/wangari.git
cd wangari

# Set up database
mysql -u root -p -e "CREATE DATABASE wangari_db;"
mysql -u root -p wangari_db < Backend/config/schema.sql
mysql -u root -p wangari_db < Backend/config/migrate_whatsapp_bot.sql

# Configure Nginx
cp /path/to/nginx.conf /etc/nginx/sites-available/wangari
ln -s /etc/nginx/sites-available/wangari /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

### Step 4: Deploy Evolution API (WhatsApp)
```bash
# Run Evolution API
docker run -d \
  --name evolution-api \
  --restart always \
  -p 8081:8080 \
  -v ./evolution:/evolution/instances \
  atendai/evolution-api:latest

# Scan QR code with your WhatsApp
# Access dashboard at http://YOUR_IP:8081
```

### Step 5: Deploy Ollama (AI)
```bash
# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Pull model
ollama pull llama3.2

# Start as service
systemctl enable ollama
systemctl start ollama
```

### Step 6: Deploy Whisper (Voice-to-Text)
```bash
# Install Python
apt install -y python3-pip

# Install Whisper
pip install faster-whisper

# Test
echo "Test audio transcription" | whisper --language sw --model base
```

### Step 7: SSL + Domain
```bash
# Install Certbot
apt install -y certbot python3-certbot-nginx

# Get SSL certificate
certbot --nginx -d wangari.imeantech.com

# Auto-renew
echo "0 0 1 * * certbot renew" | crontab -
```

---

## PART 6: WHEN TO UGRADE (Scaling Triggers)

| Trigger | Action | Cost |
|---|---|---|
| 1,000 users, 8GB RAM full | Upgrade to Contabo VPS 6 | €7.50/month |
| 5,000 users, WhatsApp messages heavy | Upgrade to Contabo VPS 8 | €14/month |
| 10,000 users, need GPU for AI | Upgrade to Contabo Performance VPS | €25/month |
| 25,000 users, need high availability | Consider Hetzner + load balancer | €50/month |
| 100,000+ users | Consider official Meta WhatsApp API | $500+/month |

**You won't need to upgrade from the free stack until you have 5,000+ active users.** By then, you'll be making KES 500K+/month revenue. The infrastructure costs will be <5% of revenue.

---

## PART 7: RISK ASSESSMENT

### What Could Go Wrong with Free Stack

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Evolution API gets blocked by WhatsApp | MEDIUM | HIGH | Keep official Meta API as backup (upgrade when needed) |
| Contabo VPS goes down | LOW | HIGH | Daily backups to separate location, Contabo has 99.9% SLA |
| Ollama AI quality not good enough | MEDIUM | MEDIUM | Use rule-based responses as fallback, upgrade to Mistral Large |
| Whisper accuracy on Swahili dialects | MEDIUM | MEDIUM | Fine-tune with Kenyan Swahili data, use text input as fallback |
| Contabo support is slow | MEDIUM | LOW | Self-manage everything, only contact for hardware issues |
| Free tools stop being maintained | LOW | MEDIUM | All tools have large communities (Baileys: 10K+ GitHub stars) |

### The "Nuclear Option" — If Free Tools Fail

If Evolution API gets blocked or WhatsApp changes their protocol:
1. **Immediate:** Switch to Africa's Talking WhatsApp channel (paid but reliable)
2. **Short-term:** Register for Meta WhatsApp Business API (free tier: 1,000 conversations/month)
3. **Long-term:** Negotiate Meta API pricing based on your user count

---

## PART 8: STEVE JOBS FINANCIAL PHILOSOPHY (FREE STACK EDITION)

**Jobs' Rule 1: "Be frugal"**
- You're spending $6/month on infrastructure instead of $1,500/month
- That's 99.6% cost reduction
- You can survive 12 months on KES 100,000 instead of KES 2,000,000

**Jobs' Rule 2: "Focus on the long-term"**
- The free stack lets you focus on FARMERS, not infrastructure bills
- Every shilling saved goes to field agents and farmer acquisition

**Jobs' Rule 3: "Build a great team"**
- Your "team" is: You + 2 field agents + free software
- That's the most capital-efficient team in Kenyan agritech

**Jobs' Rule 4: "Say no to 1,000 things"**
- Say no to Twilio ($42K/month)
- Say no to AWS ($5K/month)
- Say no to OpenAI API ($200/month)
- Say yes to Contabo ($6) + Evolution API (free) + Ollama (free)

**Jobs' Rule 5: "Ship great products"**
- The free stack doesn't mean cheap product
- Evolution API sends/receives WhatsApp messages just like Twilio
- Ollama generates AI responses just like OpenAI
- The farmer doesn't know (or care) which backend you use

---

## PART 9: THE BOTTOM LINE

### Old Monthly Costs (Paid Stack)
```
VPS:           $50
WhatsApp API:  $42,000
AI API:        $100
Voice API:     $150
Email:         $30
Database:      $15
SMS:           $90
────────────────────
TOTAL:         $42,535/month
```

### New Monthly Costs (Free Stack)
```
Contabo VPS:   $6
WhatsApp:      $0 (Evolution API)
AI:            $0 (Ollama + Mistral)
Voice:         $0 (Whisper)
Email:         $0 (Mailcow)
Database:      $0 (MySQL)
SMS:           $90 (Africa's Talking)
────────────────────
TOTAL:         $96/month
```

### Savings: **$42,439/month = KES 5,517,070/month**

That's **KES 66.2 million per year** you're NOT spending on infrastructure.

With that money, you can:
- Hire 10 field agents instead of 2
- Run for 5 years on bootstrap funding instead of 6 months
- Never need VC money until you choose to

**The free stack isn't just cheaper. It's strategically better.** It lets you run lean, move fast, and stay alive long enough to win.

---

*This document proves you don't need to be rich to build something great. You need to be smart about what you pay for.*
