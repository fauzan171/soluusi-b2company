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
        Schema::create('consultant_education', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultant_id');
            $table->unsignedBigInteger('education_id');
            $table->foreign('consultant_id')->references('id')->on('consultants')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('education_id')->references('id')->on('education')->onDelete('restrict')->onUpdate('cascade');
            $table->string('degree');
            $table->timestampTz('start_date')->nullable();
            $table->timestampTz('end_date')->nullable();
            $table->unsignedBigInteger('departement_id')->nullable();
            $table->foreign('departement_id')->references('id')->on('departements')->onDelete('restrict')->onUpdate('cascade');
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
        Schema::dropIfExists('consultant_education');
    }
};
