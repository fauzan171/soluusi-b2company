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
        Schema::create('consultant_subcategories', function (Blueprint $table) {
            $table->foreignId('consultant_id')->constrained('consultants')->onDelete('cascade');
            $table->foreignId('subcategory_id')->constrained('subcategories')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['consultant_id', 'subcategory_id']);
            $table->index(['consultant_id']);
            $table->index(['subcategory_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consultant_subcategories');
    }
};
