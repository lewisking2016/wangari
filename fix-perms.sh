#!/bin/bash
cd /var/www/wangari
sudo chown -R lewis:lewis .
git checkout -- Backend/config/migration_v2.sql Backend/config/migration_v2_business.sql Backend/config/schema.sql Backend/config/settings.sql
git pull origin main 2>&1
echo "--- Verify no busia references ---"
grep -rli 'busia' Backend/ Frontend/ --include='*.php' --include='*.js' 2>/dev/null | head -5 || echo "CLEAN - no busia in PHP/JS"
echo "--- Test login ---"
curl -sI https://wangari.imeantech.com/login | head -3
echo "--- Test admin login ---"
curl -sI https://wangari.imeantech.com/Frontend/admin/login.php | head -3
echo "--- Test analytics (wangari-charts.js) ---"
curl -s https://wangari.imeantech.com/Frontend/admin/analytics.php 2>&1 | grep -o 'wangari-charts.js' | head -1
