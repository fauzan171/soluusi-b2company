<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// B6: versi S&K saat registrasi + jabatan pendaftar perusahaan. Aditif.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perusahaans', function (Blueprint $table) {
            if (! Schema::hasColumn('perusahaans', 'registrant_position')) {
                $table->string('registrant_position', 100)->nullable()->after('terms_agreed_at');
            }
            if (! Schema::hasColumn('perusahaans', 'terms_version')) {
                $table->string('terms_version', 20)->nullable()->after('registrant_position');
            }
        });

        Schema::table('kliniks', function (Blueprint $table) {
            if (! Schema::hasColumn('kliniks', 'terms_version')) {
                $table->string('terms_version', 20)->nullable()->after('terms_agreed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perusahaans', function (Blueprint $table) {
            foreach (['registrant_position', 'terms_version'] as $col) {
                if (Schema::hasColumn('perusahaans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('kliniks', function (Blueprint $table) {
            if (Schema::hasColumn('kliniks', 'terms_version')) {
                $table->dropColumn('terms_version');
            }
        });
    }
};
