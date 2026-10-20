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
        Schema::create('consultant_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultant_id');
            $table->unsignedBigInteger('skill_id');
            $table->foreign('consultant_id')->references('id')->on('consultants')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('skill_id')->references('id')->on('skills')->onDelete('restrict')->onUpdate('cascade');
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
        Schema::dropIfExists('consultant_skills');
    }
};
