#!/bin/bash

# Webshop Backup Script
# Creates backups of database and uploaded files

set -e

# Configuration
BACKUP_DIR="/backups/webshop"
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="webshop_backup_${DATE}"

# Load environment variables
if [ -f .env ]; then
    export $(cat .env | grep -v '#' | awk '/=/ {print $1}')
fi

# Create backup directory
mkdir -p "${BACKUP_DIR}/${BACKUP_NAME}"

echo "🗄️  Starting webshop backup..."

# Database backup
echo "📊 Backing up database..."
mysqldump -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" > "${BACKUP_DIR}/${BACKUP_NAME}/database.sql"

if [ $? -eq 0 ]; then
    echo "✅ Database backup completed"
else
    echo "❌ Database backup failed"
    exit 1
fi

# Files backup
echo "📁 Backing up uploaded files..."
if [ -d "storage/uploads" ]; then
    cp -r storage/uploads "${BACKUP_DIR}/${BACKUP_NAME}/"
    echo "✅ Uploads backup completed"
fi

if [ -d "public/assets/images/uploads" ]; then
    mkdir -p "${BACKUP_DIR}/${BACKUP_NAME}/public_uploads"
    cp -r public/assets/images/uploads/* "${BACKUP_DIR}/${BACKUP_NAME}/public_uploads/" 2>/dev/null || true
    echo "✅ Public uploads backup completed"
fi

# Configuration backup
echo "⚙️  Backing up configuration..."
cp .env.example "${BACKUP_DIR}/${BACKUP_NAME}/"
if [ -f composer.json ]; then
    cp composer.json "${BACKUP_DIR}/${BACKUP_NAME}/"
fi

# Create archive
echo "🗜️  Creating compressed archive..."
cd "${BACKUP_DIR}"
tar -czf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}"
rm -rf "${BACKUP_NAME}"

# Cleanup old backups (keep last 7 days)
echo "🧹 Cleaning up old backups..."
find "${BACKUP_DIR}" -name "webshop_backup_*.tar.gz" -mtime +7 -delete

# Calculate backup size
BACKUP_SIZE=$(du -h "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" | cut -f1)

echo "✅ Backup completed successfully!"
echo "📦 Backup file: ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz"
echo "📏 Backup size: ${BACKUP_SIZE}"

# Optional: Upload to cloud storage
if [ ! -z "${BACKUP_S3_BUCKET}" ]; then
    echo "☁️  Uploading to S3..."
    aws s3 cp "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" "s3://${BACKUP_S3_BUCKET}/webshop/"
    if [ $? -eq 0 ]; then
        echo "✅ S3 upload completed"
    else
        echo "⚠️  S3 upload failed"
    fi
fi

# Send notification (optional)
if [ ! -z "${BACKUP_WEBHOOK_URL}" ]; then
    curl -X POST "${BACKUP_WEBHOOK_URL}" \
         -H "Content-Type: application/json" \
         -d "{\"text\":\"Webshop backup completed: ${BACKUP_NAME}.tar.gz (${BACKUP_SIZE})\"}" \
         > /dev/null 2>&1
fi

echo "🎉 Backup process finished!"
