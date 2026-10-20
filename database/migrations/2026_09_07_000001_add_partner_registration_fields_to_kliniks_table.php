<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('kliniks', function (Blueprint $table) {
            $table->string('facility_type')->nullable()->after('brand');
            $table->string('branch_name')->nullable()->after('facility_type');
            $table->text('address')->nullable()->after('branch_name');
            $table->string('service_interest')->nullable()->after('address');
            $table->unsignedSmallInteger('estimated_monthly_sessions')->nullable()->after('service_interest');
        });
    }

    public function down()
    {
        Schema::table('kliniks', function (Blueprint $table) {
            $table->dropColumn([
                'facility_type',
                'branch_name',
                'address',
                'service_interest',
                'estimated_monthly_sessions',
            ]);
        });
    }
};
