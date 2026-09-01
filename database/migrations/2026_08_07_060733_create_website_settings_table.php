<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('template_id')->default('default');
            $table->string('primary_color')->default('#1D9E75');
            $table->string('secondary_color')->default('#0F172A');
            $table->string('tagline')->nullable();
            $table->text('about_text')->nullable();
            $table->foreignId('hero_banner_image_id')->nullable();
            $table->foreignId('logo_image_id')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->json('social_links')->nullable();
            $table->string('virtual_tour_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_settings');
    }
};