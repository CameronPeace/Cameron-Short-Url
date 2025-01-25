<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('short_url_click', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_url_id')->index();
            $table->timestamp('occurred_at');
            $table->string('ip_address', length: 45)->nullable();
            $table->string('user_agent', length: 120)->nullable();
            $table->timestamps();
            $table->foreign('short_url_id')->references('id')->on('short_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_url_click');
    }
};
