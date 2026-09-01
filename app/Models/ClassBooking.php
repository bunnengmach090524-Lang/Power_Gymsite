<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassBooking extends Model
{
    protected $fillable = ['class_id', 'member_id', 'booked_at'];

    protected $casts = [
        'booked_at' => 'datetime',
    ];

    public function gymClass() {
        return $this->belongsTo(\App\Models\GymClass::class, 'class_id'); // ឬ Classes::class អាស្រ័យ model name
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}