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
        // L11+ sqlite: dropForeign+dropColumn wajib dalam satu rebuild.
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['consultant_id']);
            $table->dropColumn(['started_at', 'ended_at', 'location', 'position', 'description', 'consultant_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestampTz('started_at');
            $table->timestampTz('ended_at')->nullable();
            $table->string('location');
            $table->string('position');
            $table->text('description')->nullable();
            $table->foreignId('consultant_id')->constrained()->onDelete('restrict')->cascadeOnUpdate();
        });
    }
};
