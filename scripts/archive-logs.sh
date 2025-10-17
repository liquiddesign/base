#!/bin/bash
# Usage: ./archive-logs.sh /path/to/base-directory
set -e

if [ "$#" -ne 1 ]; then
  echo "Usage: $0 <base-directory>"
  exit 1
fi

BASE_DIR="$1"
LOG_DIR="$BASE_DIR/log"
BACKUP_DIR="$BASE_DIR/log_backup"
DATESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
TAR_NAME="logs_${DATESTAMP}.tar.gz"
TAR_PATH="$BACKUP_DIR/$TAR_NAME"

# Check if log and log-backup directories exist
if [ ! -d "$LOG_DIR" ]; then
  echo "Error: log directory not found at $LOG_DIR"
  exit 2
fi
if [ ! -d "$BACKUP_DIR" ]; then
  echo "Error: log_backup directory not found at $BACKUP_DIR"
  exit 3
fi

# Compress contents of log directory into tar.gz file
tar -czf "$TAR_PATH" -C "$LOG_DIR" .

# Delete all files in log directory, keep folders
find "$LOG_DIR" -type f ! -name '.gitkeep' -delete

echo "Logs archived to $TAR_PATH and log directory cleaned."
