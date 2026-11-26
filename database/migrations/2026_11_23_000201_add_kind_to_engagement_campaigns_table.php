<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// wv4: feature D — campaign "kind" category (metadata only, tidak memengaruhi
// send flow): announcement|edukasi|pengumuman|reminder.
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('engagement_campaigns', 'kind')) {
            return;
        }
        Schema::table('engagement_campaigns', function (Blueprint $table) {
            $table->string('kind', 20)->default('announcement')->after('channel');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('engagement_campaigns', 'kind')) {
            return;
        }
        Schema::table('engagement_campaigns', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
