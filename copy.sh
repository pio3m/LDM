#!/bin/bash

# Ścieżka do katalogu, który chcesz skopiować
LOCAL_DIR="C:\Users\piotr\projects\LDM"

# Adres serwera i katalog docelowy
REMOTE_USER="root"
REMOTE_HOST="161.35.214.6"
REMOTE_DIR="/srv"

# Data i godzina do nazwy backupu
BACKUP_DATE=$(date +"%Y%m%d_%H%M%S")
# Tworzenie backupu na serwerze (tar)
ssh "$REMOTE_USER@$REMOTE_HOST" "cd $REMOTE_DIR && tar -czf backup_${BACKUP_DATE}.tar.gz ./*"

# Kopiowanie zawartości katalogu za pomocą scp
scp -r "$LOCAL_DIR"/* "$REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR"

# Informacja o zakończeniu
if [ $? -eq 0 ]; then
    echo "Kopiowanie zakończone pomyślnie."
else
    echo "Wystąpił błąd podczas kopiowania."
fi