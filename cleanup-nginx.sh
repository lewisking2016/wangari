#!/bin/bash
# Remove old conflicting configs
rm -f /etc/nginx/sites-enabled/wangari-api
rm -f /etc/nginx/sites-enabled/wangari-domain.bak2
# Check if wangari-ssl is needed or conflicts
cat /etc/nginx/sites-enabled/wangari-ssl 2>/dev/null | head -5
echo "---"
nginx -t 2>&1
systemctl reload nginx
echo "--- Testing API ---"
curl -s http://127.0.0.1/api/health.php 2>&1 | head -3
echo "--- Testing HTTPS API ---"
curl -s https://wangari.imeantech.com/api/health.php 2>&1 | head -3
