<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_images', function (Blueprint $table) {
            // Free-text tag for the gallery filter tabs (e.g. "equipment", "classes", "events").
            // Deliberately separate from `type`, which stays reserved for
            // hero_banner / gallery / trainer_photo / logo placement.
            $table->string('category')->nullable()->after('type');

            // Optional short caption shown under the thumbnail / in the lightbox.
            $table->string('caption')->nullable()->after('image_url');

            // Optional video path (relative, resolved via MediaUrl like image_url).
            $table->string('video_url')->nullable()->after('caption');

            // Distinguishes an image row from a video row without overloading `type`.
            $table->enum('media_kind', ['image', 'video'])->default('image')->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('media_images', function (Blueprint $table) {
            $table->dropColumn(['category', 'caption', 'video_url', 'media_kind']);
        });
    }
};