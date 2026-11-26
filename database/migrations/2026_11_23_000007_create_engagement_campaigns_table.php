<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// wv4: feature D — engagement campaign (email terjadwal / pengumuman Aktivitasku).
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('engagement_campaigns')) {
            return;
        }
        Schema::create('engagement_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perusahaan_id')->constrained('perusahaans')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('channel')->default('announcement'); // email|announcement
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('engagement_campaigns')) {
            Schema::dropIfExists('engagement_campaigns');
        }
    }
};
