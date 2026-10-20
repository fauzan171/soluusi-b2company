<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * B2B-only: checkout_links (Build-NEW #5). Admin klinik pick konsultan +
     * schedule + package → signed white-label link → customer guest bayar.
     * markup_snapshot = harga locked saat link dibuat (Spec §5.4 lock).
     */
    public function up()
    {
        Schema::create('checkout_links', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('klinik_id')->constrained('kliniks')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('consultant_id')->constrained('consultants')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('schedule_id')->constrained('schedules')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('consultant_package_id')->constrained('consultant_packages')->restrictOnDelete()->cascadeOnUpdate();
            // markup snapshot (locked at generation)
            $table->unsignedInteger('net_price');       // Soluusi net (dari package.price)
            $table->unsignedInteger('markup_amount');   // margin klinik
            $table->unsignedInteger('final_price');     // customer pays = net + markup
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();   // satu link = satu sesi
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('checkout_links');
    }
};
