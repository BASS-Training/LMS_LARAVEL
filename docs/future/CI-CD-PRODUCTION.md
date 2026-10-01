# Rencana CI/CD — Auto Deploy `main` ke Produksi

> Tujuan: setiap push/merge ke branch **`main`** → test otomatis → **klik approve sekali** → kode di-deploy ke VPS produksi (`https://lms.basstrainingacademy.com`) tanpa perintah manual.
> Dokumen ini adalah rencana/ranah implementasi. Runbook manual lama tetap berlaku sebagai cadangan: `docs/DEPLOY-PEMBAYARAN-KE-PRODUKSI.md`.

---

## 1. Ringkasan alur

```
push / merge ke main
        │
        ▼
┌─────────────────────┐
│ Job "test"          │  composer install + npm build + php artisan test
│ (Linux runner)      │  GAGAL test baru → deploy batal di sini
└─────────┬───────────┘
          │ lulus
          ▼
┌─────────────────────┐
│ Job "deploy"        │  environment: production → MENUNGGU KLIK APPROVE
│ (approval 1 klik)   │  di GitHub → Actions → Environments
└─────────┬───────────┘
          │ di-approve
          ▼
SSH ke VPS → git pull --ff-only → scripts/deploy.sh → health check site
```

- **Pemicu**: `push` ke `main`, plus `workflow_dispatch` (jalankan manual kapan saja), plus `pull_request` (test saja, tidak deploy).
- **Anti-tabrakan**: satu `concurrency` group — deploy berikutnya antri, tidak pernah jalan bersamaan.
- **Cabang kerja lain** (`dev/UI`, `payment`, dll) **tidak pernah** menyentuh produksi.

---

## 2. Keputusan yang sudah dipilih

| Aspek | Keputusan |
|------|-----------|
| Hosting prod | VPS dengan akses SSH |
| Alur deploy | Otomatis + approval 1 klik (GitHub Environment `production`) |
| Migrasi DB | Otomatis + **backup mysqldump sebelum migrate** |
| Gate test | Wajib lulus, **kecuali 2 failure pre-existing** (di-quarantine) |
| Queue/Reverb | Dikelola **Supervisor** — direstart oleh deploy script |

---

## 3. File yang akan dibuat/diubah

### 3.1 `.github/workflows/deploy.yml` (BARU)

```yaml
name: CI/CD Deploy

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]
  workflow_dispatch:

concurrency:
  group: production-deploy
  cancel-in-progress: false

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: gd, pdo_sqlite, pdo_mysql, zip, intl, bcmath, exif
          coverage: none

      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: npm

      - name: Cache Composer
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}

      - name: Install PHP dependencies
        run: composer install --prefer-dist --no-progress

      - name: Siapkan .env testing
        run: cp .env.example .env && php artisan key:generate

      - name: Install & build aset
        run: npm ci && npm run build

      - name: Jalankan test (skip quarantine)
        run: php artisan test --exclude-group quarantine

  deploy:
    needs: test
    if: github.event_name != 'pull_request'
    runs-on: ubuntu-latest
    environment: production        # ← sumber approval 1 klik
    steps:
      - name: Checkout (untuk deploy script via SSH)
        uses: actions/checkout@v4

      - name: Deploy ke VPS
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.SSH_HOST }}
          username: ${{ secrets.SSH_USER }}
          port: ${{ secrets.SSH_PORT }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script_stop: true
          script: |
            cd ${{ secrets.DEPLOY_PATH }}
            git fetch origin
            git checkout main
            git pull --ff-only origin main
            bash scripts/deploy.sh

      - name: Health check
        run: |
          for i in 1 2 3 4 5; do
            if curl -fsS https://lms.basstrainingacademy.com/katalog -o /dev/null; then
              echo "OK"; exit 0
            fi
            sleep 10
          done
          echo "Site tidak sehat setelah deploy"; exit 1
```

> Catatan versi PHP: `8.3` (composer.json: `^8.2`). Samakan dengan `php -v` di VPS sebelum diaktifkan.

### 3.2 `scripts/deploy.sh` (BARU — dijalankan di VPS)

Versi otomatis dari **Bagian A** runbook pembayaran. Prinsip: `set -euo pipefail`, gagal di langkah mana pun = deploy berhenti (kode lama tetap jalan, maintenance mode dilepas di akhir).

```bash
#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."            # root aplikasi (DEPLOY_PATH)

echo "==> [1/7] Ambil kode terbaru"
git fetch origin
git checkout main
git pull --ff-only origin main

echo "==> [2/7] Backup database SEBELUM migrate"
mkdir -p storage/app/backups
DB_CONN=$(grep -E '^DB_CONNECTION=' .env | cut -d= -f2 | tr -d '"')
if [ "$DB_CONN" = "mysql" ]; then
  DB_NAME=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2 | tr -d '"')
  DB_USER=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2 | tr -d '"')
  DB_PASS=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2 | tr -d '"')
  mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
    | gzip > "storage/app/backups/db-$(date +%Y%m%d-%H%M%S).sql.gz"
  # simpan 14 backup terakhir
  ls -1t storage/app/backups/db-*.sql.gz | tail -n +15 | xargs -r rm --
  echo "    Backup tersimpan."
else
  echo "    DB bukan mysql — skip dump."
fi

echo "==> [3/7] Maintenance mode"
php artisan down --retry=30

echo "==> [4/7] Dependency PHP + build aset"
composer install --no-dev --optimize-autoloader --prefer-dist
npm ci
npm run build

echo "==> [5/7] Migrasi database"
php artisan migrate --force
php artisan storage:link || true

echo "==> [6/7] Optimasi cache"
php artisan optimize          # config + route + view cache

echo "==> [7/7] Kembali online + restart proses"
php artisan up
sudo -n supervisorctl restart lms-queue-worker:* laravel-queue:* lms-reverb-server \
  || { echo "!! supervisorctl gagal/sudo tanpa password — pakai fallback"; php artisan queue:restart; }

echo "Deploy selesai: $(git rev-parse --short HEAD)"
```

**Urutan penting**: `down` hanya selama window kritis (build+migrate+cache) supaya downtime minimal; worker di-restart **setelah** `up` sehingga pekerjaan antrian langsung diproses kode baru.

### 3.3 Edit 2 test file (quarantine)

Tambahkan atribut grup pada 2 test yang sudah gagal sejak lama (dokumentasi: `docs/COLOR-SYSTEM-PROGRESS.md`):

- `tests/Feature/NoRoleChecksInAppTest.php`
- `tests/Feature/ViewsNoRoleDirectivesTest.php`

```php
#[\PHPUnit\Framework\Attributes\Group('quarantine')]
public function test_...(): void { ... }
```

Dengan `--exclude-group quarantine` di CI:

- 2 failure lama **tidak** memblokir deploy.
- Failure **baru** apa pun → job test merah → deploy batal.

> Menyusul: perbaiki kedua test itu (lint arsitektur yang substring-nya terlalu luas, mis. `'role:'` bisa match banyak hal), lalu hapus atribut quarantine.

---

## 4. Setup manual sekali (owner / admin repo)

### 4.1 Kunci SSH
```bash
# lokal (Windows)
ssh-keygen -t ed25519 -C "github-actions-deploy" -f github_deploy_key -N '""'
# isi github_deploy_key.pub ke VPS:
ssh user@vps "mkdir -p ~/.ssh && echo '<ISI ISI github_deploy_key.pub>' >> ~/.ssh/authorized_keys"
```

### 4.2 GitHub → Settings → Secrets and variables → Actions
| Secret | Nilai |
|--------|-------|
| `SSH_HOST` | IP/domain VPS |
| `SSH_USER` | user SSH (mis. `root` atau `ubuntu`) |
| `SSH_PORT` | `22` (atau port custom) |
| `SSH_PRIVATE_KEY` | isi lengkap `github_deploy_key` (private key) |
| `DEPLOY_PATH` | path absolut root aplikasi di VPS (mis. `/var/www/lms`) |

### 4.3 GitHub → Settings → Environments → **Buat `production`**
- **Required reviewers** → tambahkan akun pemilik/maintainer.
- Inilah yang membuat deploy **berhenti menunggu klik "Approve"**.

> ⚠️ Kalau repo ini *private* dan required reviewers ternyata tidak tersedia di paket GitHub yang dipakai, fallback: pindahkan trigger deploy ke `workflow_dispatch` saja (test tetap jalan otomatis di setiap push).

### 4.4 Prasyarat VPS
- [ ] Repo ter-checkout branch `main` di `DEPLOY_PATH` (sesuai runbook A1 lama) dan `git pull` bisa jalan tanpa conflict.
- [ ] `composer` dan `node/npm` terpasang di VPS.
- [ ] User SSH punya **passwordless sudo** untuk `supervisorctl` (atau deploy pakai fallback `queue:restart` + restart Reverb manual).
- [ ] Ekstensi PHP `gd` aktif (wajib untuk phpoffice/phppspreadsheets — sesuai catatan runbook lama).
- [ ] Backup cron `mysqldump` reguler tetap ada (backup CI hanya pelengkap sebelum migrate).

---

## 5. Checklist verifikasi (saat implementasi)

1. [ ] Push ke **branch dev** → hanya job `test` yang jalan (PR), tidak ada deploy.
2. [ ] `php artisan test` lokal → konfirmasi memang hanya `NoRoleChecksInAppTest` & `ViewsNoRoleDirectivesTest` yang gagal; kalau ada failure lain, evaluasi sebelum quarantine.
3. [ ] Merge ke `main` → job `test` hijau → job `deploy` status **Waiting (review required)** → klik **Approve**.
4. [ ] Log SSH: lihat tiap langkah `[1/7]…[7/7]` berjalan, backup `.sql.gz` muncul di `storage/app/backups/`.
5. [ ] `https://lms.basstrainingacademy.com/katalog` tetap sehat; `supervisorctl status` menunjukkan worker & Reverb running dengan uptime baru.
6. [ ] Uji fitur kecil (login, buka kursus) + kirim pesan chat (melewati Reverb) untuk memastikan WebSocket jalan setelah restart.

---

## 6. Risiko & batasan

| Risiko | Mitigasi |
|--------|----------|
| Migrasi gagal setengah jalan | Backup selalu dibuat sebelum migrate; rollback = `git revert` + restore dump (manual, tidak ada auto-rollback) |
| `.env` produksi tidak boleh tersentuh CI | Deploy script **tidak pernah** menulis `.env`; hanya membaca `DB_*` |
| Worker mati kalau sudo gagal | Fallback `php artisan queue:restart` + warning mencolok di log (worker lama exit → pastikan tetap ada yang menggantikan) |
| Deploy bersamaan | `concurrency` group tanpa cancel-in-progress |
| Kode rusak lolos test | Gate test wajib lulus (selain 2 quarantine); build Vite juga jadi gate |
| Reverb/queue belum memuat kode baru | `supervisorctl restart` di akhir script, diverifikasi via uptime di checklist |
| Server Node versi beda dengan lokal | `npm ci` di VPS pakai `package-lock.json`; pastikan Node LTS terpasang |

---

## 7. Yang TIDAK diubah

- `.env` produksi & konfigurasi Midtrans — tetap dikelola manual (rahasia).
- Alur kerja branch `dev/*` — hanya `main` yang menyentuh prod.
- Runbook manual `DEPLOY-PEMBAYARAN-KE-PRODUKSI.md` — tetap dipakai sebagai jalur darurat kalau CI/CD down.

---

*Dibuat 2026-09-30 sebagai rencana implementasi CI/CD.*
