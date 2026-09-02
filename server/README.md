# Wangari VPS Backend

Standalone Node.js/Express API server for the Wangari farm management platform.

## Setup

```bash
cd server
npm install
cp .env.example .env   # configure your environment
npx prisma generate
npx prisma db push      # sync schema to database
npm run dev             # development
npm run build && npm start  # production
```

## Deployment (VPS)

### 1. Install Node.js 20+

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### 2. Clone and install

```bash
git clone <repo-url> && cd wangari/server
npm install
```

### 3. Configure environment

```bash
cp .env.example .env
# Edit .env with your DATABASE_URL, JWT_SECRET, etc.
```

### 4. Set up database

```bash
npx prisma generate
npx prisma db push
```

### 5. Run with PM2

```bash
npm run build
pm2 start dist/index.js --name wangari-api
pm2 save
pm2 startup
```

### 6. Nginx reverse proxy

```nginx
server {
    listen 443 ssl;
    server_name api.wangari.imeantech.com;

    ssl_certificate /etc/letsencrypt/live/api.wangari.imeantech.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.wangari.imeantech.com/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/auth/login` | Login |
| GET | `/api/dashboard` | Dashboard stats |
| GET/POST | `/api/flocks` | Manage flocks |
| GET/POST | `/api/customers` | Manage customers |
| GET/POST | `/api/transactions` | Manage transactions |
| GET/POST | `/api/sales` | Manage sales |
| GET/POST | `/api/workers` | Manage workers |
| GET/POST | `/api/inventory` | Manage inventory |
| GET/POST | `/api/production` | Daily production logs |
| GET/POST | `/api/vaccinations` | Vaccination schedule |
| GET/POST | `/api/attendance` | Worker attendance |
| GET | `/api/weather` | Weather data |

All endpoints (except auth) require `Authorization: Bearer <token>` header.

## Tech Stack

- **Runtime:** Node.js 20+
- **Framework:** Express 5
- **ORM:** Prisma 6
- **Database:** PostgreSQL
- **Auth:** JWT (jsonwebtoken)
- **Security:** Helmet, CORS, Rate Limiting
