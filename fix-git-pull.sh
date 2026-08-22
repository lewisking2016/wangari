#!/bin/bash
cd /var/www/wangari
sudo chown -R lewis:lewis .git
git config --global pull.rebase false
git pull origin main 2>&1
echo "--- Git pull done ---"
echo "--- Testing login.php directly ---"
curl -sI https://wangari.imeantech.com/Frontend/pages/login.php | head -5
echo "--- Testing /login ---"
curl -sI https://wangari.imeantech.com/login | head -5
echo "--- Error log (last 5) ---"
tail -5 /var/log/nginx/error.log
