<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// T3: free-text opsional "Apa yang paling menekan Anda saat ini?" (max 1000 chars).
// HR hanya melihat COUNT pengisian — tidak pernah per orang (privacy).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perusahaan_assessments', function (Blueprint $table) {
            $table->text('free_text')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('perusahaan_assessments', function (Blueprint $table) {
            $table->dropColumn('free_text');
        });
    }
};
