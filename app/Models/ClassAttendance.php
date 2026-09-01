<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassAttendance extends Model
{
    protected $fillable = [
        'class_booking_id', 'occurred_on', 'status', 'note', 'marked_by', 'marked_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'marked_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(ClassBooking::class, 'class_booking_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}