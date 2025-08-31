#!/bin/bash
# Скрипт резервного копирования PostgreSQL
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
mkdir -p $BACKUP_DIR

# Создаем дамп базы данных
pg_dump -h db -U messenger -F c -b -v -f "$BACKUP_DIR/messenger_$DATE.backup" messenger

# Удаляем backups старше 7 дней
find $BACKUP_DIR -name "*.backup" -mtime +7 -delete
