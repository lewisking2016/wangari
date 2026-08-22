#!/bin/bash
sudo cp /tmp/wangari-domain.conf /etc/nginx/sites-enabled/wangari-domain
sudo nginx -t 2>&1 && sudo systemctl reload nginx
echo "--- API test ---"
curl -s https://wangari.imeantech.com/api/health.php | head -5
echo "--- Login test ---"
curl -sI https://wangari.imeantech.com/login | head -5
