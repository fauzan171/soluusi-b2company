# PHP/Composer Installation Proposal

Tanggal: 2026-11-14. Status: BLOCKED — menunggu keputusan owner.

## Kebutuhan
- PHP ≥ 8.2 (composer.json requirement)
- Composer 2.x
- Extensions: `php-curl`, `php-mbstring`, `php-sqlite3` (untuk dev), `php-mysql` (untuk prod), `php-xml`, `php-zip`

## Opsi instalasi

### Opsi 1: Laravel Herd (RECOMMENDED)
- **Download:** https://herd.laravel.com/windows
- **Versi:** PHP 8.2/8.3 bundled, Composer included
- **Install:** User-level, tidak perlu admin, tidak system-wide
- **Path:** `%USERPROFILE%\.config\herd\bin\`
- **Pros:** Laravel-optimized, includes DB (MySQL/PostgreSQL/SQLite), Valet-like URLs, easy switch PHP version
- **Cons:** ~200MB download, GUI installer

### Opsi 2: Manual ZIP dari windows.php.net
- **Download:** https://windows.php.net/download#php-8.3 (atau 8.2)
- **Versi:** PHP 8.3.x VS16 x64 Thread Safe
- **Install:** Extract ke `C:\php` atau `%USERPROFILE%\php`, tambah PATH manual
- **Composer:** Download installer terpisah dari getcomposer.org
- **Pros:** Full control, minimal footprint
- **Cons:** Manual setup, perlu edit php.ini untuk extensions

### Opsi 3: Scoop (jika sudah ada)
- **Command:** `scoop install php composer`
- **Versi:** Latest stable
- **Install:** `%USERPROFILE%\scoop\apps\`
- **Pros:** Package manager, easy update
- **Cons:** Requires Scoop installed first

## Rekomendasi
**Herd** untuk developer Laravel — paling cepat setup, paling sedikit konfigurasi. Jika tidak mau GUI, pilih **Manual ZIP**.

## Setelah install
1. Verifikasi: `php -v` dan `composer --version`
2. Di repo: `composer install`
3. Copy `.env.example` → `.env`, set `DB_CONNECTION=sqlite`
4. `php artisan key:generate`
5. `php artisan migrate`
6. `php artisan test` — verifikasi baseline

## Status
- [ ] Owner pilih opsi
- [ ] Install selesai
- [ ] `php -v` verified
- [ ] `composer install` di worktree goal
- [ ] `php artisan test` baseline hijau
