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
        Schema::create('images', function (Blueprint $table) {
            $table->id();

            // Public, shareable slug used in URLs (e.g. /i/aB3dE5gH7j).
            $table->string('public_id', 10)->unique();

            // Nullable: anonymous (guest) uploads have no owner.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Storage: which disk holds the file and where (hash-sharded path).
            $table->string('disk')->default('images');
            $table->string('path');
            $table->string('filename');
            $table->string('original_filename')->nullable();

            // Media metadata.
            $table->string('mime_type');
            $table->string('media_type');
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('checksum', 64)->nullable();

            // User-supplied metadata.
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            // Lifecycle.
            $table->timestamp('expires_at')->nullable();

            // Capability token that lets a guest delete their own upload.
            $table->string('delete_token', 64)->nullable()->unique();

            $table->unsignedBigInteger('views')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->index('media_type');
            $table->index('checksum');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
