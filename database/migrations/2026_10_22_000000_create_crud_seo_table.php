<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crud_seo', function (Blueprint $table) {
            $table->id();
            $table->string('seoable_type');
            // Supports integer, UUID and ULID parent keys.
            $table->string('seoable_id', 64);
            $table->string('locale', 35);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('canonical_url')->nullable();
            $table->string('robots')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->text('og_image')->nullable();
            $table->text('og_image_alt')->nullable();
            $table->string('og_type')->nullable();
            $table->timestamps();
            $table->unique(['seoable_type', 'seoable_id', 'locale'], 'crud_seo_entity_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crud_seo');
    }
};
