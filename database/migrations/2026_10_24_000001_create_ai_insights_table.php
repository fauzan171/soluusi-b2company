<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cache insight AI per tenant (port TVS: tabel ai_insights + lease generate).
// Scope tetap 'tenant' — konteks selalu agregat, tidak per analisis individu.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->string('vertical', 20);            // perusahaan | sekolah
            $table->unsignedBigInteger('tenant_id');   // perusahaan_id | sekolah_id
            $table->string('scope', 40)->default('tenant');
            $table->string('input_hash', 64);          // perubahan data → stale
            $table->string('model', 80)->nullable();
            $table->string('status', 20)->default('empty'); // empty|running|completed|failed
            $table->longText('result_json')->nullable();
            $table->text('error_code')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            $table->unique(['vertical', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};
