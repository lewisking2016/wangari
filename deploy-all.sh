#!/bin/bash
cd /var/www/wangari
git pull origin main 2>&1
echo "--- Testing super admin page ---"
curl -sI https://wangari.imeantech.com/Frontend/admin/super_admin.php | head -5
echo "--- Testing API ---"
curl -s https://wangari.imeantech.com/api/super_admin.php?endpoint=overview | head -3
echo "--- Testing health ---"
curl -s https://wangari.imeantech.com/api/health.php | head -3
echo "--- Login page ---"
curl -sI https://wangari.imeantech.com/login | head -5
