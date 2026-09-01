<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('bakong_account_id')->nullable()->after('subscription_status');
            $table->string('bakong_merchant_name')->nullable()->after('bakong_account_id');
            $table->string('bakong_merchant_city')->nullable()->after('bakong_merchant_name');
            $table->text('bakong_api_token')->nullable()->after('bakong_merchant_city');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['bakong_account_id', 'bakong_merchant_name', 'bakong_merchant_city', 'bakong_api_token']);
        });
    }
};