<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tag sesi yang berkontribusi ke stage pervorma HAP (brief: "tandai karena
     * dia dapat cost dari sana"). Nilai = nomor stage (1/2/3) yang sesi ini
     * bantu capai. NULL = sesi di luar window / di atas stage-3 trigger.
     */
    public function up()
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('hap_pervorma_stage')->nullable()->after('completion_verified_at')->index();
        });
    }

    public function down()
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->dropColumn('hap_pervorma_stage');
        });
    }
};
