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
        Schema::create('category_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('skill_id')->constrained('skills');
            $table->foreignId('consultant_id')->constrained('consultants');
            $table->timestamps();
        });

        // set unique constraint
        Schema::table('category_skills', function (Blueprint $table) {
            $table->unique(['category_id', 'skill_id', 'consultant_id']);
        });

        // set indexing
        Schema::table('category_skills', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('skill_id');
            $table->index('consultant_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('category_skills');
    }
};
