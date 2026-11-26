<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// wv3: public /daftar-perusahaan — timestamp persetujuan S&K Perusahaan
// (mirror terms_agreed_at pada kliniks, 2026_08_19_000001). Aditif.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perusahaans', function (Blueprint $table) {
            if (! Schema::hasColumn('perusahaans', 'terms_agreed_at')) {
                $table->timestamp('terms_agreed_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perusahaans', function (Blueprint $table) {
            if (Schema::hasColumn('perusahaans', 'terms_agreed_at')) {
                $table->dropColumn('terms_agreed_at');
            }
        });
    }
};
