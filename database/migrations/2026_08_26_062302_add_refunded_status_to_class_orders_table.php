<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE class_orders MODIFY COLUMN status ENUM('pending','verified','rejected','expired','refunded') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // ចំណាំ: បើមាន row ណាមួយប្រើ 'refunded' រួច ការ downgrade នេះនឹងបរាជ័យ
        // លុះត្រាតែប្តូរ row ទាំងនោះទៅតម្លៃផ្សេងជាមុនសិន។
        DB::statement("ALTER TABLE class_orders MODIFY COLUMN status ENUM('pending','verified','rejected','expired') NOT NULL DEFAULT 'pending'");
    }
};