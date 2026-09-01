<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_attendances', function (Blueprint $table) {
            $table->id();

            // Links back to the recurring booking (member ↔ class weekly slot).
            // We do NOT touch class_bookings itself — one row here per actual
            // weekly occurrence, so history across weeks is preserved.
            $table->foreignId('class_booking_id')->constrained()->cascadeOnDelete();

            // The specific calendar date this occurrence happened on
            // (class_bookings has no date — only schedule_day/start_time/end_time
            // via the related GymClass — so this is what makes a session unique).
            $table->date('occurred_on');

            $table->enum('status', ['pending', 'present', 'absent', 'permission'])
                ->default('pending');

            // Optional reason, e.g. admin/trainer notes when marking 'permission' or 'absent'.
            $table->text('note')->nullable();

            // Who marked it (trainer or admin) and when — null while still 'pending'.
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();

            $table->timestamps();

            // Prevent duplicate rows for the same booking on the same day
            // (e.g. accidental double-submit from the roster screen).
            $table->unique(['class_booking_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_attendances');
    }
};