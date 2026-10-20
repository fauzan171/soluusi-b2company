<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * B2B-only: markup_rules (Build-NEW #8). Soluusi set net; klinik markup
     * per package, kena cap per price range (config/klinik.php markup_caps).
     */
    public function up()
    {
        Schema::create('markup_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klinik_id')->constrained('kliniks')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('consultant_package_id')->constrained('consultant_packages')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('markup_amount')->default(0); // rupiah di atas net
            $table->timestamps();

            $table->unique(['klinik_id', 'consultant_package_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('markup_rules');
    }
};
