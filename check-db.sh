#!/bin/bash
mysql -u root -e "SHOW TABLES FROM busia_chicken_db;" 2>/dev/null | head -10
echo "---"
mysql -u root -e "SHOW TABLES FROM wangari_db;" 2>/dev/null | head -10
echo "---"
# Check which DB has users table
mysql -u root -e "SELECT table_schema, table_name FROM information_schema.tables WHERE table_name='users';" 2>/dev/null
echo "---"
# Check users table columns
mysql -u root -e "SHOW COLUMNS FROM busia_chicken_db.users;" 2>/dev/null | grep -i pass
mysql -u root -e "SHOW COLUMNS FROM wangari_db.users;" 2>/dev/null | grep -i pass
echo "---"
# Check what database config.php is using
grep -i 'DB_NAME\|database' /var/www/wangari/Backend/config/database.php 2>/dev/null | head -5
