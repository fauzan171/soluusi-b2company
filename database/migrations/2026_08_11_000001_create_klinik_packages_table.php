<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Paket klinik B2B: dibuat oleh Soluusi (super admin) bersama mitra — beda
// dengan B2C yang paketnya dibuat konsultan. Harga sesuai budget mitra.
return new class extends Migration {
    public function up()
    {
        Schema::create('klinik_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klinik_id')->constrained('kliniks')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->string('mode')->default('online'); // online/offline
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('klinik_packages');
    }
};
