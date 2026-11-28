<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profil kaya konsultan hasil sync B2C (display-only di katalog B2B).
     *
     * photo_url: URL absolut aset B2C (services.b2c.asset_base + /storage/avatars/<file>).
     * headline: consultants.specialty B2C (ringkasan satu baris).
     * profile: JSON blob read-only (lihat ponytail di bawah).
     */
    public function up(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            $table->string('photo_url')->nullable()->after('b2c_consultant_id');
            $table->string('headline')->nullable()->after('photo_url');
            // ponytail: satu kolom JSON untuk semua data relasional B2C
            // (categories/education/certificates/skills/experience/packages).
            // Cukup karena data display-only — tidak pernah di-query per-field.
            // Upgrade path: pecah ke tabel consultants_* kalau butuh filter/search.
            $table->json('profile')->nullable()->after('headline');
        });
    }

    public function down(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            $table->dropColumn(['photo_url', 'headline', 'profile']);
        });
    }
};
