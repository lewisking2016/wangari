#!/bin/bash
sudo rm -f /etc/nginx/sites-enabled/wangari-api
sudo rm -f /etc/nginx/sites-enabled/wangari-domain.bak2
sudo nginx -t 2>&1
sudo systemctl reload nginx
echo "--- Testing API ---"
curl -s https://wangari.imeantech.com/api/health.php 2>&1 | head -5
