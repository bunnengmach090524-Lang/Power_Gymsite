<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_images', function (Blueprint $table) {
            // Video rows may have no poster/thumbnail image, so image_url
            // must be allowed to be null. Requires doctrine/dbal for ->change().
            $table->string('image_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Reverting requires every existing row to have a non-null image_url.
        // Back-fill any null rows with an empty string first if you ever
        // need to roll this back on a database that already has video rows.
        Schema::table('media_images', function (Blueprint $table) {
            $table->string('image_url')->nullable(false)->change();
        });
    }
};