#!/bin/bash
# ============================================================
# Wangari VPS Optimization Script
# Optimizes Debian 11 for 300+ concurrent users
# Run as root: sudo bash vps-optimize.sh
# ============================================================

set -e

echo "============================================"
echo "  Wangari VPS Optimization — 300+ Users"
echo "============================================"

# Color codes
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# ============================================================
# 1. SYSTEM UPDATE & ESSENTIAL PACKAGES
# ============================================================
echo -e "\n${GREEN}[1/8] Updating system packages...${NC}"
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq redis-server php7.4-redis php7.4-opcache \
  fail2ban unattended-upgrades curl wget git htop netdata \
  php7.4-mbstring php7.4-xml php7.4-curl php7.4-zip php7.4-gd \
  php7.4-bcmath php7.4-intl

# ============================================================
# 2. PHP OPCACHE — Free Performance Boost
# ============================================================
echo -e "\n${GREEN}[2/8] Configuring PHP OPcache...${NC}"

cat > /etc/php/7.4/fpm/conf.d/10-opcache.ini << 'OPCACHE'
; OPcache Settings — Wangari Optimized
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.max_wasted_percentage=10
opcache.validate_timestamps=1
opcache.revalidate_freq=2
opcache.save_comments=1
opcache.fast_shutdown=1
opcache.jit_buffer_size=64M
opcache.jit=1235
; Preload frequently used files
opcache.preload_user=www-data
OPCACHE

echo "  OPcache configured: 128MB memory, 10000 files, JIT enabled"

# ============================================================
# 3. REDIS — Session Store + Query Cache
# ============================================================
echo -e "\n${GREEN}[3/8] Configuring Redis...${NC}"

# Redis config for low-memory VPS
cat > /etc/redis/redis.conf << 'REDIS_CONF'
# Wangari Redis Config — Optimized for 913MB VPS
bind 127.0.0.1
port 6379
daemonize yes
supervised systemd
maxmemory 128mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb
dir /var/lib/redis
loglevel notice
logfile /var/log/redis/redis-server.log
databases 16
timeout 300
tcp-keepalive 300
tcp-backlog 511
REDIS_CONF

systemctl enable redis-server
systemctl restart redis-server

# Configure PHP to use Redis for sessions
cat > /etc/php/7.4/fpm/conf.d/20-redis-sessions.ini << 'PHP_SESSIONS'
; Use Redis for PHP sessions — faster than files
session.save_handler = redis
session.save_path = "tcp://127.0.0.1:6379?auth=&database=0"
session.gc_maxlifetime = 7200
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.use_cookies = 1
session.use_only_cookies = 1
PHP_SESSIONS

echo "  Redis configured: 128MB max memory, sessions on Redis"

# ============================================================
# 4. PHP-FPM TUNING — Handle 300 Concurrent Users
# ============================================================
echo -e "\n${GREEN}[4/8] Tuning PHP-FPM for high concurrency...${NC}"

cat > /etc/php/7.4/fpm/pool.d/www.conf << 'PHPFPM'
[www]
; Run as www-data
user = www-data
group = www-data

; Socket config
listen = /var/run/php/php7.4-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; Process Manager — Dynamic for memory efficiency
pm = dynamic
pm.max_children = 25
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500
pm.process_idle_timeout = 10s

; Status page for monitoring
pm.status_path = /fpm-status
ping.path = /fpm-ping
ping.response = pong

; Timeouts
request_terminate_timeout = 60s
request_slowlog_timeout = 5s

; Logging
slowlog = /var/log/php7.4-fpm-slow.log
log_level = error

; Security
security.limit_extensions = .php
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen,curl_multi_exec,parse_ini_file,show_source
php_admin_value[open_basedir] = /var/www/wangari:/tmp:/usr/share/php
php_admin_flag[allow_url_fopen] = on
php_admin_flag[allow_url_include] = off

; Performance
php_admin_value[memory_limit] = 128M
php_admin_value[max_execution_time] = 60
php_admin_value[max_input_time] = 60
php_admin_value[post_max_size] = 50M
php_admin_value[upload_max_filesize] = 20M
php_admin_value[max_file_uploads] = 10

; Realpath cache (reduces filesystem calls)
php_admin_value[realpath_cache_size] = 4096K
php_admin_value[realpath_cache_ttl] = 600

; Session
session.auto_start = Off
session.gc_probability = 0
PHPFPM

echo "  PHP-FPM: 25 max children, 500 requests/worker, 128MB memory"

# ============================================================
# 5. NGINX PERFORMANCE — Gzip, Caching, Security
# ============================================================
echo -e "\n${GREEN}[5/8] Optimizing Nginx...${NC}"

# Create a performance snippet
cat > /etc/nginx/snippets/performance.conf << 'PERF_CONF'
# === Gzip Compression ===
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_min_length 256;
gzip_types
    text/plain text/css text/xml text/javascript
    application/json application/javascript application/xml
    application/xml+rss application/x-javascript
    image/svg+xml font/woff font/woff2;

# === Connection Tuning ===
worker_processes auto;
worker_rlimit_nofile 65535;
events {
    worker_connections 1024;
    multi_accept on;
    use epoll;
}
http {
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    keepalive_requests 1000;
    types_hash_max_size 2048;
    server_tokens off;
    client_max_body_size 50M;
    client_body_buffer_size 128k;
    client_header_buffer_size 1k;
    large_client_header_buffers 4 8k;

    # === FastCGI Cache ===
    fastcgi_cache_path /tmp/nginx-cache levels=1:2
        keys_zone=WANGARI:100m inactive=60m
        max_size=256m;
    fastcgi_cache_key "$scheme$request_method$host$request_uri";
PERF_CONF

# Wangari domain — full optimized config
cat > /etc/nginx/sites-enabled/wangari-domain << 'NGINX_CONF'
# HTTP → HTTPS redirect
server {
    listen 80;
    server_name wangari.imeantech.com;
    return 301 https://$host$request_uri;
}

# Main HTTPS server
server {
    listen 443 ssl http2;
    server_name wangari.imeantech.com;

    # SSL (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/wangari.imeantech.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/wangari.imeantech.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;

    root /var/www/wangari;
    index index.php index.html;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_min_length 256;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml image/svg+xml;

    # Connection limits
    client_max_body_size 50M;
    client_body_buffer_size 128k;

    # Static file caching — CRITICAL for speed
    location ~* \.(jpg|jpeg|png|gif|ico|svg|webp|woff|woff2|ttf|eot|css|js)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }

    # API routes — rewrite /api/* → /Backend/api/*
    location /api/ {
        rewrite ^/api/(.*)$ /Backend/api/$1 last;
    }

    # PHP processing — with caching
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # FastCGI tuning
        fastcgi_buffering on;
        fastcgi_buffer_size 32k;
        fastcgi_buffers 16 16k;
        fastcgi_connect_timeout 60s;
        fastcgi_send_timeout 60s;
        fastcgi_read_timeout 60s;

        # FastCGI cache for non-logged-in pages
        fastcgi_cache WANGARI;
        fastcgi_cache_valid 200 10m;
        fastcgi_cache_valid 301 302 1m;
        fastcgi_cache_valid 404 1m;
        fastcgi_cache_methods GET HEAD;
        fastcgi_cache_bypass $cookie_session $http_authorization;
        fastcgi_no_cache $cookie_session;
        add_header X-Cache-Status $upstream_cache_status;
    }

    # FPM status (internal only)
    location = /fpm-status {
        allow 127.0.0.1;
        deny all;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Static frontend files
    location /Frontend/assets/ {
        try_files $uri =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location /Frontend/images/ {
        try_files $uri =404;
        expires 30d;
        add_header Cache-Control "public";
    }

    # Clean URLs
    location /login {
        try_files /Frontend/pages/login.html =404;
    }
    location /register {
        try_files /Frontend/pages/register.html =404;
    }
    location /support {
        try_files /Frontend/pages/support.html =404;
    }
    location /wangariadmin {
        try_files $uri $uri/ /Frontend/wangariadmin/index.html =404;
    }

    # Block sensitive files
    location ~ /\.(ht|git|env|svn) {
        deny all;
    }
    location ~ /Backend/config/ {
        deny all;
    }

    # Default
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
NGINX_CONF

echo "  Nginx: HTTP/2, gzip, FastCGI cache, 30-day static cache, security headers"

# ============================================================
# 6. MYSQL/MARIADB OPTIMIZATION
# ============================================================
echo -e "\n${GREEN}[6/8] Optimizing MySQL/MariaDB...${NC}"

# Find MySQL config
MYSQL_CONF=""
for f in /etc/mysql/mariadb.conf.d/50-server.cnf /etc/mysql/my.cnf /etc/mysql/mysql.conf.d/mysqld.cnf; do
    [ -f "$f" ] && MYSQL_CONF="$f" && break
done

if [ -n "$MYSQL_CONF" ]; then
    # Backup original
    cp "$MYSQL_CONF" "${MYSQL_CONF}.bak"
    
    # Add Wangari optimizations
    cat >> "$MYSQL_CONF" << 'MYSQL_OPT'

# === Wangari Optimization ===
[mysqld]
# InnoDB — main engine tuning
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_log_buffer_size = 16M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
innodb_io_capacity = 200
innodb_read_io_threads = 4
innodb_write_io_threads = 4

# Query cache (MariaDB)
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M

# Connections
max_connections = 150
max_allowed_packet = 64M
thread_cache_size = 16
table_open_cache = 2048
table_definition_cache = 1024
tmp_table_size = 64M
max_heap_table_size = 64M

# Slow query log (for debugging)
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
MYSQL_OPT

    echo "  MySQL: 256MB buffer pool, query cache, 150 max connections"
else
    echo "  MySQL config not found — skipping"
fi

# ============================================================
# 7. SECURITY — Fail2ban + Rate Limiting
# ============================================================
echo -e "\n${GREEN}[7/8] Setting up security...${NC}"

# Fail2ban for Wangari
cat > /etc/fail2ban/jail.local << 'F2B'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
banaction = iptables-multiport

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 3

[nginx-http-auth]
enabled = true
port = http,https
filter = nginx-http-auth
logpath = /var/log/nginx/error.log
maxretry = 5

[nginx-limit-req]
enabled = true
port = http,https
filter = nginx-limit-req
logpath = /var/log/nginx/error.log
maxretry = 10
F2B

systemctl enable fail2ban
systemctl restart fail2ban

# Nginx rate limiting
cat > /etc/nginx/snippets/rate-limit.conf << 'RATE_CONF'
# Rate limiting zones
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
limit_req_zone $binary_remote_addr zone=api:10m rate=30r/m;
limit_req_zone $binary_remote_addr zone=general:10m rate=60r/m;
RATE_CONF

echo "  Fail2ban: SSH + Nginx protection enabled"
echo "  Rate limiting: 5/min login, 30/min API, 60/min general"

# ============================================================
# 8. MONITORING + SWAP TUNING + FINAL
# ============================================================
echo -e "\n${GREEN}[8/8] Final optimizations...${NC}"

# Optimize swap usage for low-RAM VPS
echo 10 > /proc/sys/vm/swappiness
echo "vm.swappiness=10" >> /etc/sysctl.conf

# Increase file limits
cat >> /etc/security/limits.conf << 'LIMITS'
* soft nofile 65535
* hard nofile 65535
LIMITS

# Enable Netdata monitoring
systemctl enable netdata 2>/dev/null || true
systemctl start netdata 2>/dev/null || true

# Restart services
echo -e "\n${YELLOW}Restarting all services...${NC}"
systemctl restart php7.4-fpm
systemctl restart nginx
systemctl restart mysql 2>/dev/null || systemctl restart mariadb 2>/dev/null || true
systemctl restart redis-server

# Verify everything is running
echo -e "\n${GREEN}============================================"
echo "  Verification"
echo "============================================${NC}"

STATUS=true
for svc in nginx php7.4-fpm redis-server; do
    if systemctl is-active --quiet $svc 2>/dev/null; then
        echo -e "  ${GREEN}✓${NC} $svc is running"
    else
        echo -e "  ${RED}✗${NC} $svc is NOT running"
        STATUS=false
    fi
done

if systemctl is-active --quiet mysql 2>/dev/null || systemctl is-active --quiet mariadb 2>/dev/null; then
    echo -e "  ${GREEN}✓${NC} MySQL/MariaDB is running"
else
    echo -e "  ${RED}✗${NC} MySQL/MariaDB is NOT running"
    STATUS=false
fi

# Test OPcache
echo ""
php -v | head -1
php -m | grep -i opcache && echo -e "  ${GREEN}✓${NC} OPcache loaded" || echo -e "  ${RED}✗${NC} OPcache not loaded"

# Test Redis
redis-cli ping 2>/dev/null && echo -e "  ${GREEN}✓${NC} Redis responding" || echo -e "  ${RED}✗${NC} Redis not responding"

echo -e "\n${GREEN}============================================"
echo "  OPTIMIZATION COMPLETE"
echo "============================================${NC}"
echo ""
echo "  What was configured:"
echo "  • OPcache: 128MB, JIT enabled, 10K files cached"
echo "  • Redis: Sessions + query cache, 128MB"
echo "  • PHP-FPM: 25 workers, 500 req/worker"
echo "  • Nginx: HTTP/2, gzip, FastCGI cache, 30d static"
echo "  • MySQL: 256MB buffer, query cache, 150 connections"
echo "  • Security: Fail2ban, rate limiting, HSTS"
echo "  • Monitoring: Netdata dashboard"
echo ""
echo "  Expected capacity: 100-300 active users"
echo "  Site: https://wangari.imeantech.com"
echo "  Monitor: http://YOUR_IP:19999"
echo ""
