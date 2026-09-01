<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 8, 2);
            $table->enum('status', ['pending', 'verified', 'rejected', 'expired'])->default('pending');
            $table->text('khqr_qr_string')->nullable();
            $table->string('khqr_md5')->nullable();
            $table->enum('verified_method', ['bakong_api', 'manual_admin', 'simulation'])->nullable();
            // Staff/admin who confirmed a cash payment or generated a QR on
            // the member's behalf. Null when the member paid via self-checkout.
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            // Staff/admin who created this order for a member (phone/walk-in
            // booking). Null when the member built the cart themselves.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_orders');
    }
};