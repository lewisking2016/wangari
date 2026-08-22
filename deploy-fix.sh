#!/bin/bash
cd /var/www/wangari
git pull origin main 2>&1
echo "--- Testing login ---"
curl -sI https://wangari.imeantech.com/Frontend/pages/login.php | head -5
echo "--- Testing admin login ---"
curl -sI https://wangari.imeantech.com/Frontend/admin/login.php | head -5
echo "--- Error log (last 3) ---"
tail -3 /var/log/nginx/error.log 2>/dev/null
