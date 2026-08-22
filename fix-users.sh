#!/bin/bash
# Fix Wangari users — set correct passwords and roles

DB="wangari_db"
USER="wangari"
PASS="Wangari2026!"

# Generate correct password hashes
ADMIN_HASH=$(php -r "echo password_hash('Wangari@123', PASSWORD_DEFAULT);")
FARM_HASH=$(php -r "echo password_hash('admin123', PASSWORD_DEFAULT);")

echo "=== Fixing admin password to Wangari@123 ==="
mysql -u "$USER" -p"$PASS" "$DB" -e "UPDATE users SET password='$ADMIN_HASH' WHERE username='admin';"

echo "=== Creating farm_manager account (admin / admin123) ==="
mysql -u "$USER" -p"$PASS" "$DB" -e "
INSERT IGNORE INTO users (username, email, password, full_name, role, is_active, created_at)
VALUES ('farm_admin', 'farm@wangari.com', '$FARM_HASH', 'Farm Administrator', 'farm_manager', 1, NOW());
"

echo "=== Creating stock_manager account ==="
STOCK_HASH=$(php -r "echo password_hash('stock123', PASSWORD_DEFAULT);")
mysql -u "$USER" -p"$PASS" "$DB" -e "
INSERT IGNORE INTO users (username, email, password, full_name, role, is_active, created_at)
VALUES ('stock_mgr', 'stock@wangari.com', '$STOCK_HASH', 'Stock Manager', 'stock_manager', 1, NOW());
"

echo "=== Creating sales account ==="
SALES_HASH=$(php -r "echo password_hash('sales123', PASSWORD_DEFAULT);")
mysql -u "$USER" -p"$PASS" "$DB" -e "
INSERT IGNORE INTO users (username, email, password, full_name, role, is_active, created_at)
VALUES ('sales1', 'sales@wangari.com', '$SALES_HASH', 'Sales Staff', 'sales_staff', 1, NOW());
"

echo "=== Final user list ==="
mysql -u "$USER" -p"$PASS" "$DB" -e "SELECT id, username, role, full_name, is_active FROM users ORDER BY id;"

echo "=== DONE ==="
