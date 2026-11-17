<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A5 — index filter admin_id + created_at untuk audit log viewer.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_actions', function (Blueprint $table) {
            $table->index(['admin_id', 'created_at'], 'admin_actions_admin_id_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('admin_actions', function (Blueprint $table) {
            $table->dropIndex('admin_actions_admin_id_created_at_idx');
        });
    }
};
