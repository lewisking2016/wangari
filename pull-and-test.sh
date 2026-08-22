#!/bin/bash
cd /var/www/wangari
git pull origin main
echo "--- Testing login page ---"
curl -sI https://wangari.imeantech.com/login | head -5
echo "--- Testing register page ---"
curl -sI https://wangari.imeantech.com/register | head -5
echo "--- Testing API ---"
curl -s https://wangari.imeantech.com/api/health.php | head -5
echo "--- Testing admin ---"
curl -sI https://wangari.imeantech.com/wangariadmin/ | head -5
echo "--- Checking error log ---"
tail -5 /var/log/nginx/error.log
