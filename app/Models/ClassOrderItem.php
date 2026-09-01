<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassOrderItem extends Model
{
    protected $fillable = ['class_order_id', 'class_id', 'price'];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function classOrder(): BelongsTo
    {
        return $this->belongsTo(ClassOrder::class);
    }

    public function gymClass(): BelongsTo
    {
        return $this->belongsTo(GymClass::class, 'class_id');
    }
}