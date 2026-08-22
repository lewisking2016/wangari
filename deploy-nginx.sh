#!/bin/bash
sudo cp /tmp/wangari-domain.conf /etc/nginx/sites-enabled/wangari-domain
sudo rm -f /etc/nginx/sites-enabled/wangari-ssl
sudo rm -f /etc/nginx/sites-enabled/wangari-domain.bak2
sudo nginx -t 2>&1 && sudo systemctl reload nginx
echo "--- API test ---"
curl -s https://wangari.imeantech.com/api/health.php | head -5
echo "--- Login test ---"
curl -sI https://wangari.imeantech.com/login | head -5
