<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('perusahaan_id')->nullable()->index();
            $table->unsignedBigInteger('sekolah_id')->nullable()->index();
        });
        foreach (['perusahaan_sessions', 'sekolah_sessions'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('schedule_id')->nullable()->constrained('schedules');
                $table->timestamp('ended_at')->nullable();
                $table->boolean('quota_consumed')->default(true);
                $table->text('cancel_reason')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('checked_in_at')->nullable();
                $table->string('meeting_url')->nullable();
                $table->string('location')->nullable();
                $table->string('delivery_status')->default('pending');
                $table->string('no_show_party')->nullable();
            });
        }
        Schema::create('booking_claims', function (Blueprint $table) {
            $table->foreignId('schedule_id')->primary()->constrained('schedules');
            $table->string('owner_type', 30);
            $table->unsignedBigInteger('owner_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_claims');
        foreach (['perusahaan_sessions', 'sekolah_sessions'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('schedule_id');
                $table->dropColumn(['ended_at', 'quota_consumed', 'cancel_reason', 'cancelled_at', 'checked_in_at', 'meeting_url', 'location', 'delivery_status', 'no_show_party']);
            });
        }
        Schema::table('schedules', fn (Blueprint $table) => $table->dropColumn(['perusahaan_id', 'sekolah_id']));
    }
};
