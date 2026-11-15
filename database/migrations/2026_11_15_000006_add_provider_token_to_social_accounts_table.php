<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// AuthController@handleProvideCallback menulis provider_token tapi kolom belum
// ada — hilang silent karena guarded=[]. Tambahkan kolom nullable string.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->string('provider_token')->nullable()->after('provider_name');
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn('provider_token');
        });
    }
};
