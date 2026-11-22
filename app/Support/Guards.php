<?php

namespace App\Support;

use App\Models\Perusahaan\Perusahaan;

/**
 * Guard helper konsisten untuk kode BARU (mission K15) — menggantikan ad-hoc
 * abort_* bercelah. Kode lama tidak di-refactor (surgical).
 */
class Guards
{
    /**
     * K8: lock request konsultan per perusahaan — guard SERVER-SIDE.
     * Route POST request konsultasi wajib lewat guard ini.
     */
    public static function ensureRequestEnabled(Perusahaan $perusahaan): void
    {
        abort_unless((bool) $perusahaan->consultant_request_enabled, 403,
            'Permintaan konsultasi dikunci admin. Pilih jadwal lewat menu booking.');
    }
}
