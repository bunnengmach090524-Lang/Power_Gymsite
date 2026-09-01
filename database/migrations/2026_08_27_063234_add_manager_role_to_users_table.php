<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL/MariaDB doesn't support adding an enum value via Schema
        // Builder directly — must ALTER the column definition with raw SQL.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'gym_admin', 'manager', 'staff', 'member') NOT NULL DEFAULT 'gym_admin'");
    }

    public function down(): void
    {
        // Reverting: any users with role='manager' must be reassigned
        // BEFORE rollback, or this will fail. Safety net: demote them to 'staff'.
        DB::statement("UPDATE users SET role = 'staff' WHERE role = 'manager'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'gym_admin', 'staff', 'member') NOT NULL DEFAULT 'gym_admin'");
    }
};