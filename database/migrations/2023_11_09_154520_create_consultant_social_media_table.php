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
        Schema::create('consultant_social_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultant_id');
            $table->unsignedBigInteger('social_media_id');
            $table->string('url');
            $table->foreign('consultant_id')->references('id')->on('consultants')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('social_media_id')->references('id')->on('social_media')->onDelete('restrict')->onUpdate('cascade');
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
        Schema::dropIfExists('consultant_social_media');
    }
};
