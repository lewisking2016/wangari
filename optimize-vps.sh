#!/bin/bash

echo '[1/8] Installing packages...'
sudo apt-get update -qq
sudo apt-get install -y redis-server php7.4-redis php7.4-opcache fail2ban

echo '[2/8] Configuring OPcache...'
sudo tee /etc/php/7.4/fpm/conf.d/10-opcache.ini > /dev/null << 'OPCACHE'
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
opcache.fast_shutdown=1
OPCACHE

echo '[3/8] Configuring Redis sessions...'
sudo tee /etc/php/7.4/fpm/conf.d/20-redis-sessions.ini > /dev/null << 'REDIS'
session.save_handler = redis
session.save_path = tcp://127.0.0.1:6379?database=0
session.gc_maxlifetime = 7200
session.cookie_httponly = 1
session.use_strict_mode = 1
REDIS

echo '[4/8] Tuning PHP-FPM...'
sudo tee /etc/php/7.4/fpm/pool.d/www.conf > /dev/null << 'FPMCONF'
[www]
user = www-data
group = www-data
listen = /var/run/php/php7.4-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 25
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500
request_terminate_timeout = 60s
slowlog = /var/log/php7.4-fpm-slow.log
log_level = error
security.limit_extensions = .php
php_admin_value[memory_limit] = 128M
php_admin_value[realpath_cache_size] = 4096K
php_admin_value[realpath_cache_ttl] = 600
FPMCONF

echo '[5/8] Optimizing Nginx...'
sudo cp /etc/nginx/sites-enabled/wangari-domain /etc/nginx/sites-enabled/wangari-domain.bak2
sudo tee /etc/nginx/sites-enabled/wangari-domain > /dev/null << 'NGINXCONF'
server {
    listen 80;
    server_name wangari.imeantech.com;
    return 301 https://$host$request_uri;
}
server {
    listen 443 ssl http2;
    server_name wangari.imeantech.com;
    ssl_certificate /etc/letsencrypt/live/wangari.imeantech.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/wangari.imeantech.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    server_tokens off;
    root /var/www/wangari;
    index index.php index.html;
    client_max_body_size 50M;
    add_header X-Frame-Options SAMEORIGIN always;
    add_header X-Content-Type-Options nosniff always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_min_length 256;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml image/svg+xml font/woff2;
    location ~* \.(jpg|jpeg|png|gif|ico|svg|webp|woff|woff2|ttf|eot|css|js)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }
    location /api/ {
        rewrite ^/api/(.*)$ /Backend/api/$1 last;
    }
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffering on;
        fastcgi_buffer_size 32k;
        fastcgi_buffers 16 16k;
    }
    location /Frontend/assets/ {
        try_files $uri =404;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
    location /Frontend/images/ {
        try_files $uri =404;
        expires 30d;
    }
    location /login { try_files /Frontend/pages/login.html =404; }
    location /register { try_files /Frontend/pages/register.html =404; }
    location /support { try_files /Frontend/pages/support.html =404; }
    location /wangariadmin { try_files $uri $uri/ /Frontend/wangariadmin/index.html =404; }
    location ~ /\.(ht|git|env) { deny all; }
    location ~ /Backend/config/ { deny all; }
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
NGINXCONF

echo '[6/8] Tuning MySQL...'
if [ -f /etc/mysql/mariadb.conf.d/50-server.cnf ]; then
    if ! grep -q 'innodb_buffer_pool_size' /etc/mysql/mariadb.conf.d/50-server.cnf; then
        sudo bash -c 'cat >> /etc/mysql/mariadb.conf.d/50-server.cnf << MYSQLCONF

[mysqld]
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
query_cache_type = 1
query_cache_size = 64M
max_connections = 150
thread_cache_size = 16
MYSQLCONF'
    fi
fi

echo '[7/8] Setting up Fail2ban...'
sudo mkdir -p /etc/fail2ban
sudo tee /etc/fail2ban/jail.local > /dev/null << 'F2B'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
[sshd]
enabled = true
maxretry = 3
F2B

echo '[8/8] Restarting services...'
sudo systemctl enable redis-server 2>/dev/null || true
sudo systemctl restart redis-server
sudo systemctl restart php7.4-fpm
sudo nginx -t && sudo systemctl restart nginx
sudo systemctl restart mariadb 2>/dev/null || sudo systemctl restart mysql 2>/dev/null || true
sudo systemctl enable fail2ban 2>/dev/null || true
sudo systemctl restart fail2ban

echo ''
echo '========================================'
echo '  VERIFICATION'
echo '========================================'
for svc in nginx php7.4-fpm redis-server; do
    systemctl is-active --quiet $svc && echo "  OK: $svc" || echo "  FAIL: $svc"
done
php -m | grep -i opcache > /dev/null && echo '  OK: OPcache' || echo '  FAIL: OPcache'
redis-cli ping 2>/dev/null && echo '  OK: Redis' || echo '  FAIL: Redis'
curl -sI https://wangari.imeantech.com/ | head -3
echo '  DONE!'
