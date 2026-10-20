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
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable()->change();
                // Drop the foreign key constraint first
                $table->dropForeign(['role_id']);

                // Modify the column to be nullable
                $table->unsignedBigInteger('role_id')->nullable()->change();

                // Re-apply the foreign key constraint
                $table->foreign('role_id')->references('id')->on('roles')->restrictOnDelete()->cascadeOnUpdate();
            });
        } else {
            // ponytail: SQLite — dbal recreate preserves FK role_id; cukup ->change().
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable()->change();
                $table->unsignedBigInteger('role_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
