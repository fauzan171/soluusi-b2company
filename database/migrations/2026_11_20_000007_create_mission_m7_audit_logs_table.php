<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// M7 (MASTER-PLAN K16): audit log minimal (bukan spatie — minim dependency).
// Untuk akses catatan klinis & event privacy-sensitive. Tanpa FK ke users
// (actor boleh null saat system event), subject polymorphic via kolom plain.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('actor_id')->nullable(); // users.id, tanpa FK (log survive user delete)
                $table->string('action', 100);                      // e.g. consultation_note.view
                $table->string('subject_type', 100)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->json('meta')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['subject_type', 'subject_id']);
                $table->index(['actor_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
