# Deploy Guide — Soluusi B2B (mission branch → prod)

> W8. Dokumen ini menggambarkan TOPOLOGI PROD SAAT INI (improvement-live),
> checklist pra-deploy, dan langkah aktivasi scheduler. Ini BUKAN otomatis:
> semua eksekusi deploy adalah keputusan owner.

## ⚠️ KRITIS: ini BASE SWAP, bukan patch

Mission branch (`w8/qa-deploy-docs` dan merge-nya) dibangun di atas
`upgrade/laravel-12` (baseline 66 test, Laravel 12 full skeleton). Tree prod
yang jalan sekarang adalah **improvement-live** (Laravel 11-style skeleton,
556 test baseline berbeda, plus fix prod spesifik). Deploy branch mission ke
prod = mengganti base kode sepenuhnya, bukan menambah patch. 199 test hijau
di mission branch BUKAN jaminan 556 test improvement-live tetap relevan.

**Keputusan deploy sepenuhnya di tangan owner.** Jika dipilih jalur base
swap, jalankan checklist di bawah tanpa melewati satu pun langkah.

## Topologi prod (terverifikasi 2026-11-22)

- Tree prod = working tree langsung (`soluusi-b2b-klinik`) — push `main` ke
  tree itu = deploy (git reset --hard + `php artisan migrate --force`), tanpa CI.
- Web: `php -S 127.0.0.1:8099` via systemd user unit `soluusi-b2b.service`
  (`PHP_CLI_SERVER_WORKERS=6`, ea-php83), cloudflared tunnel →
  https://soluusi.tokengateway.ai.id.
- Queue: `soluusi-worker.service` (`queue:work database`, default queue).
- DB: sqlite `database/database.sqlite`.
- Scheduler: **TIDAK ADA cron/timer yang menjalankan `schedule:run`** — semua
  entry Kernel::$schedule dead code di prod sampai unit W8 diaktifkan (di bawah).

## Checklist pra-deploy

1. **Backup DB sebelum migrate**: `cp database/database.sqlite database/database.sqlite.bak.$(date +%F)` —
   satu-satunya rollback nyata untuk sqlite. Jangan pernah migrate tanpa backup.
2. **Verifikasi migrasi additive & reversible**: semua migrasi mission
   (2026_11_20_000001..000007, 2026_11_22_*) additive + guarded
   (`hasColumn`/`hasTable`), `down()` jalan. Rollback 9 langkah sudah
   diverifikasi test `W1RollbackCheckTest`.
3. **Jalankan suite di tree deploy** dengan env test terisolasi — bukti hijau,
   bukan klaim (saat tulis ini: 199 test, 798 assertions, mission branch).
4. **Cherry-pick fix prod yang TIDAK ada di mission branch** (daftar di bawah).
5. **Aktifkan scheduler timer** (di bawah) — tanpa ini, sweep no-show, expiry
   sweep, dual-confirm fallback, dan chat prune TIDAK berjalan.

## Cherry-picks WAJIB sebelum deploy branch ini ke prod

Fix berikut hidup hanya di `improvement-live` (prod) dan belum ada di base
mission. Ambil dengan `git cherry-pick <sha>` di branch mission, atau
re-apply manual:

| Sha | Isi | File tersentuh | Catatan kompatibilitas L12 |
|---|---|---|---|
| `44bed0d` | classmap-mtime self-heal `php -S` + polyfill `finfo` | `public/index.php`, `app/Support/finfo-polyfill.php`, `composer.json` (autoload.files) | `public/index.php` mission belum berisi guard ini (beda konten) → merge manual, jangan blanket. Polyfill aman (PHP 8.2, `finfo` internal). |
| `52954db` | Job `RestartWebServer` (restart unit systemd via `exec`) + debug line guard | `app/Jobs/RestartWebServer.php`, `public/index.php` | Aman di L12; job pakai `exec systemctl --user restart` — hanya jalan di host prod. |
| `3d48aed` | strip prefix `/dev` dari `REQUEST_URI` di `index.php` | `public/index.php` (8 baris) | Khusus cloudflared `/dev` path. Tambahkan SETELAH 44bed0d supaya guard self-heal tetap di posisi awal request. |

Catatan: ketiganya menyentuh `public/index.php` yang beda konten antara dua
base — resolve konflik manual dengan urutan: (1) 44bed0d, (2) 52954db,
(3) 3d48aed. Verifikasi pasca-pick: request via tunnel jalan, log
`/tmp/soluusi-guard-debug.log` terisi saat classmap stale.

## Aktivasi scheduler (systemd user units W8)

File unit ada di `deploy/` repo ini: `soluusi-scheduler.service` +
`soluusi-scheduler.timer` (placeholder WorkingDirectory + `/usr/local/bin/php`
— sesuaikan saat aktivasi; prod memakai ea-php83 path absolut).

Langkah (manual, oleh owner):

```
cp deploy/soluusi-scheduler.service deploy/soluusi-scheduler.timer ~/.config/systemd/user/
# edit WorkingDirectory + path php di service
systemctl --user daemon-reload
systemctl --user enable --now soluusi-scheduler.timer
systemctl --user list-timers soluusi-scheduler.timer   # verifikasi
```

## Entry scheduler yang hidup setelah timer aktif

| Entry | Frekuensi | Efek |
|---|---|---|
| Expiry sweep (`MidtransController::updateExpiredPayments`) | tiap 5 menit | payment waiting/expired → canceled |
| Dual-confirm fallback | tiap jam | sesi `konsultan_done_at` >24h tanpa verify → `needs_ops_review` (tidak auto-complete) |
| `sessions:sweep-no-show` (W2/K2) | tiap jam | sesi scheduled >24h tanpa check-in → no_show + ledger `forfeited` |
| `model:prune` (W7/K11) | harian 03:00 | purge chat message >90 hari post-sesi |

## Post-deploy smoke

1. Login tiap role (employee / konsultan / HR) via tunnel.
2. Dashboard HR menampilkan data perusahaan sendiri saja (cross-tenant matrix
   test = regresi net).
3. Buat 1 booking + 1 chat end-to-end, lalu cek `consultation_quota_ledger`
   berisi event `reserved` (dan `used` setelah sesi selesai).
4. `systemctl --user list-timers` — timer scheduler terjadwal menit depan.
