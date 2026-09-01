<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * phone belongs on StaffProfile itself (not User/Trainer) because it's
     * staff-specific contact info independent of the underlying payable
     * account type — User and Trainer models don't have a `phone` column
     * at all, and adding it there would mean touching two separate
     * schemas depending on payable_type instead of one.
     */
    public function up(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};