<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = ['name', 'price_per_month', 'features'];

    protected $casts = [
        'features' => 'array',
    ];
}