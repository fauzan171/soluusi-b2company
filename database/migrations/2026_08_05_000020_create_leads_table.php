<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Lead capture dari landing page B2B. Follow-up manual oleh associate
     *  (§15.6 DECIDED — no self-serve signup). */
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('org_name');
            $table->enum('vertical', ['klinik', 'sekolah', 'perusahaan']);
            $table->string('email');
            $table->string('phone');
            $table->text('message')->nullable();
            $table->timestamp('contacted_at')->nullable(); // associate follow-up log
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leads');
    }
};
