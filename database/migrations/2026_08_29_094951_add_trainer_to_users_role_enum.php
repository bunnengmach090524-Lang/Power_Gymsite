<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) ទាញយក enum definition បច្ចុប្បន្នពី information_schema
        $row = DB::selectOne("
            SELECT COLUMN_TYPE as type
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'role'
        ");

        if (!$row) {
            throw new \RuntimeException("Column users.role not found.");
        }

        // COLUMN_TYPE នឹងមានទម្រង់ enum('a','b','c')
        preg_match("/^enum\((.*)\)$/i", $row->type, $matches);
        $existingValues = array_map(function ($v) {
            return trim($v, "'");
        }, str_getcsv($matches[1], ',', "'"));

        // 2) បន្ថែម 'trainer' បើមិនទាន់មាន
        if (!in_array('trainer', $existingValues)) {
            $existingValues[] = 'trainer';
        }

        $enumList = "'" . implode("','", $existingValues) . "'";

        DB::statement("ALTER TABLE users MODIFY role ENUM($enumList) NOT NULL");
    }

    public function down(): void
    {
        // Revert: ដកយក 'trainer' ចេញវិញ (ត្រូវប្រយ័ត្ន — បើមាន user ណាកំពុងប្រើ role='trainer' ស្រាប់ វានឹង fail)
        $row = DB::selectOne("
            SELECT COLUMN_TYPE as type
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'role'
        ");

        preg_match("/^enum\((.*)\)$/i", $row->type, $matches);
        $existingValues = array_map(function ($v) {
            return trim($v, "'");
        }, str_getcsv($matches[1], ',', "'"));

        $existingValues = array_filter($existingValues, fn($v) => $v !== 'trainer');
        $enumList = "'" . implode("','", $existingValues) . "'";

        DB::statement("ALTER TABLE users MODIFY role ENUM($enumList) NOT NULL");
    }
};