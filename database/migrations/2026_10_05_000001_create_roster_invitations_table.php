<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roster_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('roster_id');
            $table->string('email');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'roster_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_invitations');
    }
};
