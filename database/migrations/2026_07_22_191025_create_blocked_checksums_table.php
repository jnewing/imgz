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
        Schema::create('blocked_checksums', function (Blueprint $table) {
            $table->id();
            // SHA-256 of an image that has been removed and must not be
            // re-uploaded.
            $table->string('checksum', 64)->unique();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_checksums');
    }
};
