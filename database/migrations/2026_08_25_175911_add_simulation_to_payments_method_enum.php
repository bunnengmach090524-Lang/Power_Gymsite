<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL doesn't support altering an enum column's allowed values
        // directly via Schema::table — raw SQL MODIFY COLUMN is required.
        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('cash', 'aba_payway', 'bakong_khqr', 'simulation') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('cash', 'aba_payway', 'bakong_khqr') NOT NULL");
    }
};