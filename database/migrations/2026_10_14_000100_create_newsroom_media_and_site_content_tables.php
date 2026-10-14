<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Newsroom, media library and admin-managed site content (portfolio,
 * programmes, team, partners, impact figures, text blocks). Same shapes as
 * kuartal-id/kuartalgroup's newsroom so the code stays shareable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->string('kind', 10); // image | pdf
            $table->unsignedBigInteger('size');
            $table->string('sha256', 64);
            $table->string('visibility', 10)->default('public');
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['visibility', 'kind']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('body')->nullable(); // sanitised HTML
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('category', 40)->index();
            $table->json('tags')->nullable();
            $table->string('status', 12)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('meta_title', 160)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('site_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index(); // fund | portfolio | person | partner | metric | block
            $table->string('group', 60)->nullable(); // fund slug, person group, or block key
            $table->string('slug', 80)->nullable()->unique(); // funds: public page /programmes/{slug}
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('body')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('location')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->integer('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->index(['type', 'is_published', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_entries');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('media');
    }
};
