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
        // ponytail: SQLite = 1 dropColumn per Schema call; dropForeign MySQL-only.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('started_at');
                $table->dropColumn('ended_at');
                $table->dropColumn('location');
                $table->dropColumn('position');
                $table->dropColumn('description');
                $table->dropForeign(['consultant_id']);
                $table->dropColumn('consultant_id');
            });
        } else {
            foreach (['started_at', 'ended_at', 'location', 'position', 'description', 'consultant_id'] as $col) {
                Schema::table('companies', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
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
