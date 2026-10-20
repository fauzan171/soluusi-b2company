<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename hap_commissions → hap_pervormas untuk DB yang sudah migrate
     * sebelum rename. Fresh install sudah pakai nama baru via
     * 2026_08_05_000014_create_hap_pervormas_table.php.
     */
    public function up()
    {
        if (Schema::hasTable('hap_commissions') && !Schema::hasTable('hap_pervormas')) {
            Schema::rename('hap_commissions', 'hap_pervormas');
        }
    }

    public function down()
    {
        if (Schema::hasTable('hap_pervormas') && !Schema::hasTable('hap_commissions')) {
            Schema::rename('hap_pervormas', 'hap_commissions');
        }
    }
};
