<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// wb-T3: copy 1 kalimat per paket (research-ux §2) — editable via admin UI.
// Aditif, nullable default: paket seeder lama tanpa copy tetap valid.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('wellbeing_packages', 'description')) {
            Schema::table('wellbeing_packages', function (Blueprint $table) {
                $table->text('description')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('wellbeing_packages', 'description')) {
            Schema::table('wellbeing_packages', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
