<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Partner registration: klinik butuh lokasi (filter konsultan sedaerah)
     *  + approval flow (ops approve sebelum mitra aktif). */
    public function up()
    {
        Schema::table('kliniks', function (Blueprint $table) {
            $table->unsignedBigInteger('regency_id')->nullable()->after('gcal_id');
            $table->timestamp('approved_at')->nullable()->after('mou_at');
        });
    }

    public function down()
    {
        Schema::table('kliniks', function (Blueprint $table) {
            $table->dropColumn('regency_id');
            $table->dropColumn('approved_at');
        });
    }
};
