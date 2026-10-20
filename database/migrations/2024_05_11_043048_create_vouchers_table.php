<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('type');
            $table->decimal('nominal', 15, 2)->default(0);
            $table->integer('kuota')->default(0);
            $table->integer('usage')->default(0);
            $table->timestampTz('start_date')->nullable();
            $table->timestampTz('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('min_transaction', 15, 2)->default(0);
            $table->decimal('max_discount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vouchers');
    }
};
