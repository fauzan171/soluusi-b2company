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
        $isMysql = Schema::getConnection()->getDriverName() === 'mysql';

        // SQLite/Doctrine DBAL kehilangan kolom yang baru ditambahkan ketika
        // ADD + DROP dijalankan dalam satu table-alter rebuild. Pisahkan DROP
        // lama dan ADD kolom baru agar fresh install menghasilkan schema utuh.
        if (!$isMysql) {
            Schema::table('consultation_sessions', function (Blueprint $table) {
                $table->dropColumn('consultant_packages');
            });

            Schema::table('consultation_sessions', function (Blueprint $table) {
                $table->foreignId('consultant_id')->constrained('consultants')->restrictOnDelete()->cascadeOnUpdate();
                $table->decimal('amount', 10, 2);
                $table->foreignId('consultant_package_id')->constrained('consultant_packages')->restrictOnDelete()->cascadeOnUpdate();
            });

            return;
        }

        Schema::table('consultation_sessions', function (Blueprint $table) use ($isMysql) {
            $table->foreignId('consultant_id')->after('user_id')->constrained('consultants')->onDelete('restrict')->onUpdate('cascade');
            $table->decimal('amount', 10, 2)->after('order_number');
            $table->dropForeign(['consultant_packages']);
            $table->dropColumn('consultant_packages');
            $table->foreignId('consultant_package_id')->after('consultant_id')->constrained('consultant_packages')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consultation_sessions', function (Blueprint $table) {
            $table->dropForeign(['consultant_id']);
            $table->dropColumn('consultant_id');
            $table->dropColumn('amount');
            $table->dropForeign(['consultant_package_id']);
        });
    }
};
