<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->decimal('package_price', 12, 2)->default(0)->after('amount');
            $table->decimal('voucher_amount', 12, 2)->default(0)->after('package_price');
        });
    }

    public function down()
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->dropColumn(['package_price', 'voucher_amount']);
        });
    }
};
