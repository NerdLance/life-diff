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
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('repository_id')->constrained('repositories')->cascadeOnDelete();
            $table->string('version', 32);
            $table->string('release_type', 24)->default('patch');
            $table->string('state', 20)->default('draft');
            $table->string('title', 160);
            $table->text('body')->nullable();
            $table->string('visibility', 20)->default('private');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // A soft-deleted version remains reserved; see the Phase 1 contract.
            $table->unique(['repository_id', 'version']);
            $table->index(['repository_id', 'state', 'published_at']);
            $table->index(['visibility', 'state', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
