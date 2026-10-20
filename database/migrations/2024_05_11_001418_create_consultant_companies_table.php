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
        Schema::create('consultant_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
            $table->foreignId('company_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
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
        Schema::dropIfExists('consultant_companies');
    }
};
