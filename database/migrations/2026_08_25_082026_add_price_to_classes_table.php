<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // null ឬ 0 = free (បញ្ចូលក្នុង subscription); > 0 = paid add-on class
            $table->decimal('price', 8, 2)->nullable()->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};