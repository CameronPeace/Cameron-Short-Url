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
        Schema::create('short_url', function (Blueprint $table) {
            $table->id();
            $table->string('code', length: 10);
            $table->string('domain', length: 253)->nullable();
            $table->text('redirect_url');
            $table->softDeletes();
            $table->timestamps();
            $table->primary(['domain', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_url');
    }
};
