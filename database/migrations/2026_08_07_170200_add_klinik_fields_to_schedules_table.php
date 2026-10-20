<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('klinik_id')->nullable()->after('consultant_id')->constrained('kliniks')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('created_by')->nullable()->after('klinik_id')->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->string('mode')->default('online')->after('latest_updated_by');
            $table->string('status')->default('confirmed')->after('mode');
            $table->string('location')->nullable()->after('status');
            $table->string('meeting_url')->nullable()->after('location');
            $table->text('notes')->nullable()->after('meeting_url');
        });
    }

    public function down()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('klinik_id');
            $table->dropColumn(['mode', 'status', 'location', 'meeting_url', 'notes']);
        });
    }
};
