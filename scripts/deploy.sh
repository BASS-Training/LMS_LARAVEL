#!/usr/bin/env bash
set -euo pipefail

app_path="${1:-}"
expected_sha="${2:-}"
db_settings_script="${3:-}"

if [[ ! "$app_path" =~ ^/[A-Za-z0-9_./-]+$ || ! -f "$db_settings_script" ]]; then
  echo 'Expected an absolute application path and database settings helper.' >&2
  exit 1
fi
cd -- "$app_path"

if [[ ! "$expected_sha" =~ ^[a-f0-9]{40}$ ]]; then
  echo 'Expected a full Git commit SHA.' >&2
  exit 1
fi

if [[ ! -f .env || "$(git branch --show-current)" != main || -n "$(git status --porcelain)" ]]; then
  echo 'Production checkout must be a clean main branch with an existing .env.' >&2
  exit 1
fi

git fetch origin main
git cat-file -e "${expected_sha}^{commit}"
git merge-base --is-ancestor "$expected_sha" origin/main
git merge-base --is-ancestor HEAD "$expected_sha"

backup_dir=storage/app/backups
mkdir -p "$backup_dir"
backup_file="$backup_dir/db-$(date -u +%Y%m%d-%H%M%S).sql.gz"
mysql_options="$(mktemp)"
maintenance_on=0

cleanup() {
  rm -f -- "$mysql_options"
  if [[ "$maintenance_on" == 1 ]]; then
    php artisan up || true
  fi
}
trap cleanup EXIT

database_name="$(php "$db_settings_script" "$mysql_options")"
echo "Backing up production database to $backup_file"
mysqldump --defaults-extra-file="$mysql_options" --no-tablespaces --single-transaction --quick --routines --triggers -- "$database_name" | gzip > "$backup_file"
test -s "$backup_file"
gzip -t "$backup_file"

php artisan down --retry=60
maintenance_on=1

git merge --ff-only "$expected_sha"
composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan storage:link || true

php artisan up
maintenance_on=0
php artisan queue:restart
sudo -n supervisorctl restart lms-reverb-server

echo "Deployed $(git rev-parse --short HEAD)."
