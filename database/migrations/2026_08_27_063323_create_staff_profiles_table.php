<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Polymorphic-style payee: 'user' (staff/manager with login) or
            // 'trainer' (Trainer profile, no login required). Kept as plain
            // string + id pair (not Eloquent morphs) so we control the two
            // allowed values explicitly rather than trusting free-form
            // model class strings.
            $table->enum('payable_type', ['user', 'trainer']);
            $table->unsignedBigInteger('payable_id');

            // Job title — free text so gyms can label roles their own way
            // (cashier, front_desk, cleaner, trainer, ...) without us
            // maintaining a fixed enum that will inevitably be too rigid.
            $table->string('position');

            $table->enum('salary_type', ['fixed', 'hourly', 'commission', 'fixed_commission']);
            $table->decimal('base_salary', 10, 2)->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('commission_percent', 5, 2)->nullable();
            $table->enum('commission_source', ['pt_session', 'class_booking', 'payment_referred'])->nullable();

            $table->date('hire_date')->nullable();
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique(['payable_type', 'payable_id'], 'staff_profiles_payable_unique');
            $table->index(['tenant_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};