#!/bin/bash
# ============================================
# Wangari VPS — PostgreSQL Remote Access Setup
# ============================================
# Run this on your VPS as root or with sudo:
#   ssh lewis@20.164.18.34
#   sudo bash setup-vps-db.sh

set -e

echo "=== Step 1: Enable remote PostgreSQL connections ==="
# Find PostgreSQL version
PG_VERSION=$(ls /etc/postgresql/ | head -1)
echo "PostgreSQL version: $PG_VERSION"

# Backup configs
cp /etc/postgresql/$PG_VERSION/main/postgresql.conf /etc/postgresql/$PG_VERSION/main/postgresql.conf.bak
cp /etc/postgresql/$PG_VERSION/main/pg_hba.conf /etc/postgresql/$PG_VERSION/main/pg_hba.conf.bak

# Listen on all interfaces
sed -i "s/#listen_addresses = 'localhost'/listen_addresses = '*'/" /etc/postgresql/$PG_VERSION/main/postgresql.conf
sed -i "s/listen_addresses = 'localhost'/listen_addresses = '*'/" /etc/postgresql/$PG_VERSION/main/postgresql.conf

echo "=== Step 2: Allow remote connections ==="
# Allow remote connections with password
echo "host    all    all    0.0.0.0/0    md5" >> /etc/postgresql/$PG_VERSION/main/pg_hba.conf

echo "=== Step 3: Create production database user ==="
sudo -u postgres psql -c "CREATE USER wangari_prod WITH PASSWORD 'WangariProd2026!';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE wangari_db TO wangari_prod;"
sudo -u postgres psql -c "ALTER USER wangari_prod CREATEDB SUPERUSER;"

# Also grant schema permissions
sudo -u postgres psql -d wangari_db -c "GRANT ALL ON SCHEMA public TO wangari_prod;"
sudo -u postgres psql -d wangari_db -c "GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO wangari_prod;"
sudo -u postgres psql -d wangari_db -c "GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO wangari_prod;"

echo "=== Step 4: Restart PostgreSQL ==="
systemctl restart postgresql
echo "PostgreSQL restarted."

echo "=== Step 5: Open firewall ==="
if command -v ufw &> /dev/null; then
    ufw allow 5432/tcp
    ufw reload
    echo "UFW firewall updated."
else
    echo "UFW not installed — check Azure/AWS security group to allow port 5432."
fi

echo "=== Step 6: Verify ==="
pg_isready
echo ""
echo "Public IP:"
curl -s ifconfig.me
echo ""
echo ""
echo "=== DONE ==="
echo ""
echo "Your DATABASE_URL for Vercel:"
PUBLIC_IP=$(curl -s ifconfig.me)
echo "postgresql://wangari_prod:WangariProd2026!@${PUBLIC_IP}:5432/wangari_db?schema=public&sslmode=require"
echo ""
echo "IMPORTANT: Also open port 5432 in your Azure Network Security Group if applicable!"
