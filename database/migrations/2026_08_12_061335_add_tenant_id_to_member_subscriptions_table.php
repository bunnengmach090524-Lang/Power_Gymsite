<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('member_subscriptions', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
            $table->index('tenant_id');
        });

        // Backfill ពី member's tenant_id ដើម្បីកុំឲ្យ subscription ចាស់បាត់ក្រោយពេលបន្ថែម TenantScope
        DB::statement('
            UPDATE member_subscriptions ms
            JOIN members m ON m.id = ms.member_id
            SET ms.tenant_id = m.tenant_id
            WHERE ms.tenant_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};