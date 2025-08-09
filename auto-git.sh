#!/bin/bash

# Путь к корню репозитория — если запускаешь скрипт из папки проекта, то:
REPO_DIR="$(pwd)"

cd "$REPO_DIR" || exit 1

# Проверяем есть ли изменения
if ! git diff --quiet || ! git diff --cached --quiet; then
  # Есть изменения
  echo "Изменения найдены, коммитим и пушим..."

  # Добавляем все изменения
  git add .

  # Коммит с текущим временем
  git commit -m "Авто-коммит: $(date '+%Y-%m-%d %H:%M:%S')"

  # Узнаем текущую ветку
  CURRENT_BRANCH=$(git branch --show-current)

  # Пушим изменения в текущую ветку
  git push origin "$CURRENT_BRANCH"
else
  echo "Изменений нет"
fi
