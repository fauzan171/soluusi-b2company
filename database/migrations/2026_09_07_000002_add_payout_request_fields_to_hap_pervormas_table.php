<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hap_pervormas', function (Blueprint $table) {
            $table->timestamp('requested_at')->nullable()->after('status');
            $table->foreignId('requested_by')->nullable()->after('requested_at')->constrained('users')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('hap_pervormas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by');
            $table->dropColumn('requested_at');
        });
    }
};
