#!/bin/bash
# ══════════════════════════════════════════════════════════════
# Wangari Automated Backup Script
# 
# Backs up:
# 1. MySQL database (wangari_db)
# 2. Application files
# 3. WhatsApp bot session data
# 
# Schedule via cron:
#   0 2 * * * /path/to/backup.sh  (daily at 2am)
# 
# Or run manually:
#   bash tools/backup.sh
# ══════════════════════════════════════════════════════════════

set -e

# Configuration
DB_NAME="wangari_db"
DB_USER="root"
DB_PASS=""  # Set your MySQL root password here
BACKUP_DIR="/var/backups/wangari"
RETENTION_DAYS=30
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p "$BACKUP_DIR"

echo "═══════════════════════════════════════════════"
echo "  WANGARI BACKUP — $DATE"
echo "═══════════════════════════════════════════════"

# ═══════ 1. DATABASE BACKUP ═══════
echo ""
echo "📦 Backing up database..."

if [ -n "$DB_PASS" ]; then
    mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" --single-transaction --routines --triggers > "$BACKUP_DIR/db_$DATE.sql"
else
    mysqldump -u "$DB_USER" "$DB_NAME" --single-transaction --routines --triggers > "$BACKUP_DIR/db_$DATE.sql"
fi

# Compress
gzip "$BACKUP_DIR/db_$DATE.sql"
DB_SIZE=$(du -h "$BACKUP_DIR/db_$DATE.sql.gz" | cut -f1)
echo "  ✅ Database backup: db_$DATE.sql.gz ($DB_SIZE)"

# ═══════ 2. APPLICATION FILES BACKUP ═══════
echo ""
echo "📁 Backing up application files..."

tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='*.log' \
    --exclude='tmp_*' \
    -C "$(dirname "$BACKUP_DIR")" \
    "$(basename "$(dirname "$BACKUP_DIR")")" 2>/dev/null || \
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" \
    --exclude='node_modules' \
    --exclude='.git' \
    Frontend/ Backend/

FILES_SIZE=$(du -h "$BACKUP_DIR/files_$DATE.tar.gz" | cut -f1)
echo "  ✅ Files backup: files_$DATE.tar.gz ($FILES_SIZE)"

# ═══════ 3. CLEANUP OLD BACKUPS ═══════
echo ""
echo "🧹 Cleaning up backups older than $RETENTION_DAYS days..."

DELETED=$(find "$BACKUP_DIR" -name "db_*.sql.gz" -mtime +$RETENTION_DAYS -delete -print | wc -l)
DELETED2=$(find "$BACKUP_DIR" -name "files_*.tar.gz" -mtime +$RETENTION_DAYS -delete -print | wc -l)
echo "  Deleted $((DELETED + DELETED2)) old backup files"

# ═══════ 4. BACKUP STATUS ═══════
echo ""
echo "═══════════════════════════════════════════════"
echo "  BACKUP COMPLETE"
echo "═══════════════════════════════════════════════"
echo ""
echo "  Location: $BACKUP_DIR"
echo "  Database: db_$DATE.sql.gz ($DB_SIZE)"
echo "  Files:    files_$DATE.tar.gz ($FILES_SIZE)"
echo ""
echo "  Total backups: $(ls -1 $BACKUP_DIR/*.gz 2>/dev/null | wc -l)"
echo "  Total size:    $(du -sh $BACKUP_DIR | cut -f1)"
echo ""
echo "  Next backup: Tomorrow at 2am (via cron)"
echo "═══════════════════════════════════════════════"
