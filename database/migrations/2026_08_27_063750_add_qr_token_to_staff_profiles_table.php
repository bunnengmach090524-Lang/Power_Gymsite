<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            // QR badge for attendance scan-in/out — same pattern as
            // Trainer.qr_token. Lives on staff_profiles (not users/trainers)
            // so the unified attendance system doesn't need to touch either
            // of those tables.
            $table->string('qr_token')->nullable()->unique()->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn('qr_token');
        });
    }
};