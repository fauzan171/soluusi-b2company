<?php

namespace App\Services;

use App\Models\Consultant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tarik konsultan REAL dari DB B2C (jerichoad/soluusi, koneksi 'b2c' read-only)
 * ke DB B2B. Kunci: consultants.b2c_consultant_id (unique).
 *
 * Sumber kebenaran data identitas konsultan = B2C. B2B hanya nyimpan copy
 * (id link + user login lokal). Nama/email/kategori di-update saat sync,
 * TIDAK pernah di-edit manual di B2B.
 *
 * Sinkronisasi konsultan B2C yang status='accept' AND is_active=1 saja —
 * konsultan pending/reject/interview di B2C bukan kapasitas B2B.
 */
class B2cConsultantSync
{
    /**
     * @return array{created:int, updated:int, deactivated:int, deferred:int}
     */
    public static function sync(): array
    {
        abort_if(! config('database.connections.b2c.database'), 503,
            'Koneksi B2C belum dikonfigurasi (database.connections.b2c).');

        $count = [
            'created' => 0,
            'updated' => 0,
            'deactivated' => 0,
            'deferred' => 0,
        ];
        $consultantRoleId = DB::table('roles')->where('name', 'consultant')->value('id');
        $b2c = DB::connection('b2c'); // MySQL read-only — JANGAN pernah write.

        // Snapshot konsultan aktif B2C + kategorinya (pivot di sisi B2C).
        $rows = $b2c->table('consultants as c')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->leftJoin('consultant_categories as cc', 'cc.consultant_id', '=', 'c.id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'cc.category_id')
            ->where('c.status', 'accept')
            ->where('c.is_active', 1)
            ->whereNull('c.deleted_at')
            ->select('c.id as id', 'c.uuid as uuid', 'c.name as name', 'c.slug as slug',
                'c.email as email', 'c.about as about', 'c.profit_sharing as profit_sharing',
                'c.user_id as user_id', 'c.category as category', 'c.specialty as specialty',
                'u.avatar as avatar', 'cat.name as category_name', 'cat.code_category')
            ->get()
            ->groupBy('id') // pivot multi-kategori → 1 consultant (alias, driver-proof)
            // B2B khusus wellbeing: buang konsultan yang SEMUA kategorinya bisnis/keuangan/hukum/pajak
            // (non-wellbeing) → tak masuk $rows → ter-deactivate otomatis di bawah.
            ->reject(function ($g) {
                $names = $g->pluck('category_name')->filter();

                return $names->isNotEmpty() && $names->every(
                    fn ($n) => preg_match('/bisnis|keuangan|hukum|pajak/i', $n)
                );
            })
            ->map(fn ($g) => tap($g->first(), fn ($r) => $r->categories = $g->pluck('code_category')->filter()->values()->all()));

        // Profil kaya: batched whereIn per tabel relasi B2C (bukan N+1 per konsultan).
        $ids = $rows->keys()->map(fn ($k) => (int) $k)->all();
        $profileData = $ids ? static::loadProfileData($b2c, $ids) : [];

        foreach ($rows as $b2c) {
            $b2c->id = (int) $b2c->id; // PDO sqlite dapat string — pastikan int.
            $local = Consultant::where('b2c_consultant_id', $b2c->id)->first();

            // User login B2B: satu per konsultan B2C, key b2c_consultant_id di email
            // menghindari duplikat bila re-run. Password B2B di-hash random — login
            // konsultan via SSO dari dashboard B2C, bukan password lokal.
            // Email lokal deterministik dari id B2C: konsultan B2B login via
            // SSO dari dashboard B2C, bukan password lokal (password random).
            $localEmail = 'b2c-'.$b2c->id.'@b2b.local';
            $user = \App\Models\User::where('email', $localEmail)->first();
            if (! $user) {
                $user = \App\Models\User::create([
                    'uuid' => (string) Str::uuid(),
                    'username' => 'b2c-'.$b2c->id,
                    'email' => $localEmail,
                    'password' => bcrypt(Str::random(32)), // never used for login (SSO); random per row
                    'firstname' => $b2c->name,
                    'lastname' => '',
                    'role_id' => $consultantRoleId,
                    'is_active' => 1,
                ]);
            } else {
                $user->update(['firstname' => $b2c->name, 'is_active' => 1]);
            }

            // NB: tabel consultants B2B tidak punya kolom name/email —
            // identitas konsultan (nama tampil) diambil dari users.firstname.
            $attrs = [
                'user_id' => $user->id,
                'about' => $b2c->about ?: '',
                'uuid' => $b2c->uuid,
                'profit_sharing' => (int) $b2c->profit_sharing,
                'status' => Consultant::ACCEPT,
                'is_active' => 1,
                'photo_url' => static::photoUrl($b2c->avatar),
                'headline' => $b2c->specialty,
                'profile' => $profileData[$b2c->id] ?? null,
            ];

            if ($local) {
                $local->update($attrs);
                $count['updated']++;
            } else {
                Consultant::create($attrs + ['b2c_consultant_id' => $b2c->id]);
                $count['created']++;
            }
        }

        // Deactivate: konsultan B2C yang berhenti (bukan accept/active lagi).
        $activeB2cIds = $rows->keys()->map(fn ($k) => (int) $k)->all();
        $dropped = Consultant::whereNotNull('b2c_consultant_id')
            ->whereNotIn('b2c_consultant_id', $activeB2cIds)
            ->where('is_active', 1)
            ->get();
        // Konsultan dengan sesi terjadwal di masa depan tetap aktif sampai sesinya selesai
        // (tidak ada pembatalan mendadak ke karyawan); sync malam berikutnya mencoba lagi.
        [$deferred, $toDeactivate] = $dropped->partition(fn (Consultant $c) => \App\Models\Perusahaan\PerusahaanSession::where('consultant_id', $c->id)
            ->where('status', 'scheduled')->where('scheduled_at', '>=', now())->exists());
        $toDeactivate->each(function (Consultant $c) {
            $c->update(['is_active' => 0]);
            \App\Models\User::where('id', $c->user_id)->update(['is_active' => 0]);
        });
        $count['deactivated'] = $toDeactivate->count();
        $count['deferred'] = $deferred->count();

        return $count;
    }

    /**
     * URL absolut foto B2C dari filename avatar (B2C menyimpan filename di
     * users.avatar, file di storage/app/public/avatars, served via symlink
     * storage/avatars/<file>). asset_base kosong → null (view pakai inisial).
     */
    private static function photoUrl(?string $avatar): ?string
    {
        if (! $avatar || ! config('services.b2c.asset_base')) {
            return null;
        }

        return rtrim(config('services.b2c.asset_base'), '/').'/storage/avatars/'.$avatar;
    }

    /**
     * Data profil kaya semua konsultan B2C (whereIn batch per tabel):
     * [b2c_id => {categories, education, certificates, skills, experience, packages}].
     * Soft-deleted row B2C dikecualikan; B2C tetap read-only.
     */
    private static function loadProfileData($b2c, array $ids): array
    {
        $byId = [];
        foreach ($ids as $id) {
            $byId[$id] = [
                'categories' => [], 'education' => [], 'certificates' => [],
                'skills' => [], 'experience' => [], 'packages' => [],
            ];
        }

        // Kategori (code+name) via pivot.
        foreach ($b2c->table('consultant_categories as cc')
            ->join('categories as cat', 'cat.id', '=', 'cc.category_id')
            ->whereIn('cc.consultant_id', $ids)
            ->select('cc.consultant_id', 'cat.code_category', 'cat.name')
            ->get() as $r) {
            $byId[$r->consultant_id]['categories'][] = ['code' => $r->code_category, 'name' => $r->name];
        }

        // Pendidikan: pivot consultant_education + education + departements.
        foreach ($b2c->table('consultant_education as ce')
            ->join('education as e', 'e.id', '=', 'ce.education_id')
            ->leftJoin('departements as d', 'd.id', '=', 'ce.departement_id')
            ->whereIn('ce.consultant_id', $ids)
            ->select('ce.*', 'e.name as institution', 'd.name as department')
            ->get() as $r) {
            $byId[$r->consultant_id]['education'][] = [
                'institution' => $r->institution,
                'degree' => $r->degree,
                'department' => $r->department,
                'start' => $r->start_date,
                'end' => $r->end_date,
                'description' => $r->description,
            ];
        }

        // Sertifikasi (issuer tidak ada di B2C — hanya name/number/url/year/expired_at).
        foreach ($b2c->table('certificates')
            ->whereIn('consultant_id', $ids)
            ->select('consultant_id', 'name', 'number', 'url', 'year', 'expired_at')
            ->get() as $r) {
            $byId[$r->consultant_id]['certificates'][] = [
                'name' => $r->name, 'number' => $r->number, 'year' => $r->year,
                'expired_at' => $r->expired_at, 'url' => $r->url,
            ];
        }

        // Skills via pivot.
        foreach ($b2c->table('consultant_skills as cs')
            ->join('skills as s', 's.id', '=', 'cs.skill_id')
            ->whereIn('cs.consultant_id', $ids)
            ->select('cs.consultant_id', 's.name')
            ->get() as $r) {
            $byId[$r->consultant_id]['skills'][] = $r->name;
        }

        // Pengalaman kerja: pivot consultant_companies + companies (start/end/
        // position/description di pivot, nama + location di companies).
        foreach ($b2c->table('consultant_companies as cc')
            ->join('companies as co', 'co.id', '=', 'cc.company_id')
            ->whereIn('cc.consultant_id', $ids)
            ->select('cc.*', 'co.name as company')
            ->get() as $r) {
            $byId[$r->consultant_id]['experience'][] = [
                'company' => $r->company,
                'position' => $r->position,
                'start' => $r->start_at,
                'end' => $r->end_at,
                'description' => $r->description,
            ];
        }

        // Paket (soft-delete dikecualikan). NB: kolom FK di B2C = consultant_id
        // (typo 'consultan_id' hanya di fillable model B2C, bukan kolom DB).
        foreach ($b2c->table('consultant_packages')
            ->whereIn('consultant_id', $ids)
            ->whereNull('deleted_at')
            ->select('consultant_id', 'name', 'price', 'duration', 'quota')
            ->get() as $r) {
            $byId[$r->consultant_id]['packages'][] = [
                'name' => $r->name, 'price' => (int) $r->price,
                'duration' => $r->duration, 'quota' => $r->quota,
            ];
        }

        // Konsultan tanpa data relasional sama sekali → profile null (nullable).
        return array_filter($byId, fn ($p) => collect($p)->flatten()->isNotEmpty());
    }
}
