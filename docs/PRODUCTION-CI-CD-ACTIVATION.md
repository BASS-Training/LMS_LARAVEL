# Aktivasi Production CI/CD

Rilis pertama membawa workflow `.github/workflows/production.yml`, skrip deploy, dan perbaikan test untuk gate CI. Jangan mengaktifkan deploy push sebelum uji manual berhasil.

## GitHub, oleh pemilik BASS-Training

1. Pastikan `MR-Munggaran` mendapat akses repo yang diperlukan. Buat Environment `production` dengan required reviewers `BASS-Training` dan `MR-Munggaran`; matikan **Prevent self-review**. Satu persetujuan reviewer cukup.
2. Batasi deployment branch Environment ke `main`.
3. Simpan secret **Environment**: `SSH_HOST`, `SSH_USER`, `SSH_PORT` (opsional, default 22), `SSH_PRIVATE_KEY`, `SSH_KNOWN_HOSTS`, dan `DEPLOY_PATH`. `DEPLOY_PATH` harus path absolut tanpa spasi. Verifikasi fingerprint VPS di panel/out-of-band sebelum mengisi `SSH_KNOWN_HOSTS`; jangan menggunakan `ssh-keyscan` sebagai bukti identitas.
4. Biarkan variabel repository `PRODUCTION_DEPLOY_ENABLED` kosong atau `false` sampai deploy manual lulus.

## VPS BASS LMS, oleh pemegang akses

Catat host, port, user deploy, path absolut aplikasi, dan fingerprint SSH dari panel VPS BASS LMS. Buat kunci SSH khusus deploy; pasang public key pada `authorized_keys` user VPS dan simpan private key hanya di secret GitHub. Jangan mengirim private key melalui chat atau commit.

Pastikan checkout aplikasi berada di branch `main`, bersih, memiliki `.env`, dan dapat `git fetch origin main` tanpa interaksi. Siapkan PHP, Composer, Node/npm, `mysqldump`, `gzip`, worker queue, serta program Supervisor `lms-reverb-server`. User deploy perlu izin `sudo -n supervisorctl restart lms-reverb-server`. Periksa kapasitas disk untuk backup di `storage/app/backups` dan kebijakan retensinya.

## Uji rilis pertama

1. Merge PR CI/CD ke `main` setelah Composer, build Vite, dan test selain grup `quarantine` hijau.
2. Jalankan workflow `Production CI/CD` melalui `workflow_dispatch` dari `main`; setujui job Environment `production`.
3. Periksa SHA yang di-deploy, log deploy, backup `.sql.gz`, hasil migrasi, `https://lms.basstrainingacademy.com/katalog`, queue, dan status Reverb di Supervisor. Skrip deploy melakukan backup sebelum mengganti kode; salinan skrip sementara di VPS dibersihkan setelah eksekusi.
4. Setelah deploy manual berhasil, set variabel repository `PRODUCTION_DEPLOY_ENABLED=true`. Push berikutnya ke `main` tetap menunggu approval Environment.

Dua test pemeriksaan role dalam grup `quarantine` adalah pengecualian sementara. Perbaiki lalu keluarkan dari grup tersebut pada PR terpisah.