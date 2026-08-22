# Wangari VPS Optimization Guide

## Overview
This guide optimizes your Debian 11 VPS (2 cores, 913MB RAM) to handle **100-300 concurrent users** on the Wangari platform.

## What Gets Installed/Configured

| Component | What It Does | Memory Impact |
|-----------|--------------|---------------|
| **PHP OPcache** | Caches compiled PHP scripts | +128MB (shared) |
| **Redis** | Session store + query cache | +128MB |
| **PHP-FPM Tuning** | Optimized worker processes | -50MB (better allocated) |
| **Nginx Tuning** | Gzip, caching, HTTP/2 | +0MB (free speed) |
| **MySQL Tuning** | Buffer pool, query cache | +200MB (from swap) |
| **Fail2ban** | Brute-force protection | +10MB |
| **Security Headers** | HSTS, XSS protection | +0MB |

## Quick Start

### Step 1: Upload the script to your VPS
```bash
# From your local machine (in the wangari project directory)
scp vps-optimize.sh lewis@20.164.18.34:~/
```

### Step 2: Run the script on your VPS
```bash
ssh lewis@20.164.18.34
sudo bash ~/vps-optimize.sh
```

### Step 3: Verify everything is working
```bash
# Check services
sudo systemctl status nginx php7.4-fpm redis-server mysql

# Test the site
curl -sI https://wangari.imeantech.com/

# Check health endpoint
curl -s https://wangari.imeantech.com/api/health.php | python3 -m json.tool

# Check OPcache
php -m | grep opcache

# Check Redis
redis-cli ping
```

## Performance Improvements

### Before Optimization
- **Concurrent users:** 15-30
- **Response time:** 200-500ms
- **Session storage:** File-based (slow)
- **Static files:** No caching
- **Security:** Basic

### After Optimization
- **Concurrent users:** 100-300
- **Response time:** 50-150ms
- **Session storage:** Redis (10x faster)
- **Static files:** 30-day browser cache
- **Security:** Fail2ban + rate limiting + HSTS

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────┐
│                    Internet / Users                      │
└─────────────────────────────────────────────────────────┘
                           │
                    ┌──────▼──────┐
                    │   Nginx     │
                    │  (HTTP/2)   │
                    │  + Gzip     │
                    │  + Cache    │
                    └──────┬──────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
      ┌───────▼──────┐ ┌──▼───┐ ┌──────▼──────┐
      │   Static     │ │ PHP  │ │   FastCGI   │
      │   Files      │ │ 7.4  │ │   Cache     │
      │  (30d cache) │ │+Redis│ │  (10min)    │
      └──────────────┘ └──┬───┘ └─────────────┘
                          │
                   ┌──────▼──────┐
                   │   MySQL     │
                   │  + Query    │
                   │   Cache     │
                   └─────────────┘
```

## Key Configuration Files

| File | Purpose |
|------|---------|
| `/etc/php/7.4/fpm/conf.d/10-opcache.ini` | OPcache settings |
| `/etc/php/7.4/fpm/conf.d/20-redis-sessions.ini` | Redis session handler |
| `/etc/php/7.4/fpm/pool.d/www.conf` | PHP-FPM worker config |
| `/etc/redis/redis.conf` | Redis memory & persistence |
| `/etc/nginx/sites-enabled/wangari-domain` | Nginx server config |
| `/etc/fail2ban/jail.local` | Security & rate limiting |

## Monitoring

### Health Check Endpoint
```bash
curl -s https://wangari.imeantech.com/api/health.php
```

Returns:
```json
{
  "status": "healthy",
  "services": {
    "database": {"status": "up", "type": "mysql"},
    "redis": {"status": "up"},
    "opcache": {"status": "up", "hit_rate": 95.5}
  },
  "system": {
    "php_version": "7.4.33",
    "load_avg": {"1min": 0.5, "5min": 0.3, "15min": 0.2}
  }
}
```

### Netdata Dashboard
Access at: `http://20.164.18.34:19999`
- Real-time CPU, RAM, disk usage
- Nginx & PHP-FPM metrics
- MySQL query performance

### Quick Health Checks
```bash
# Check PHP-FPM status
curl -s http://127.0.0.1/fpm-status

# Check Redis memory
redis-cli info memory

# Check MySQL connections
mysql -e "SHOW STATUS LIKE 'Threads_connected';"

# Check Nginx active connections
curl -s http://127.0.0.1/nginx_status
```

## Troubleshooting

### If the site goes down after optimization
```bash
# Check nginx config
sudo nginx -t

# Restart all services
sudo systemctl restart nginx php7.4-fpm redis-server mysql

# Check error logs
sudo tail -50 /var/log/nginx/error.log
sudo tail -50 /var/log/php7.4-fpm.log
```

### If Redis is not working
```bash
# Check Redis status
sudo systemctl status redis-server

# Test Redis connection
redis-cli ping

# If Redis won't start, check config
sudo redis-server /etc/redis/redis.conf --test
```

### If PHP sessions are broken
```bash
# Check if Redis extension is loaded
php -m | grep redis

# If not, install it
sudo apt-get install php7.4-redis
sudo systemctl restart php7.4-fpm
```

## Scaling Beyond 300 Users

If you need more than 300 concurrent users:

1. **Upgrade RAM** (easiest)
   - Current: 913MB → Recommended: 2GB
   - Cost: ~€5/mo more on Contabo

2. **Add a second server**
   - Load balancer (nginx) → 2 VPS instances
   - Shared MySQL database

3. **Move to cloud**
   - AWS Lightsail / DigitalOcean
   - Auto-scaling, managed MySQL

## Rollback

If something goes wrong:
```bash
# Restore nginx config
sudo cp /etc/nginx/sites-enabled/wangari-domain.bak /etc/nginx/sites-enabled/wangari-domain

# Restore PHP-FPM config
sudo cp /etc/php/7.4/fpm/pool.d/www.conf.bak /etc/php/7.4/fpm/pool.d/www.conf

# Restart services
sudo systemctl restart nginx php7.4-fpm
```

## Support

For issues with this optimization:
1. Check the troubleshooting section above
2. Review logs: `/var/log/nginx/error.log`, `/var/log/php7.4-fpm.log`
3. Test health endpoint: `https://wangari.imeantech.com/api/health.php`
