<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenant extends Model
{
    protected $fillable = [
        'name', 'slug', 'custom_domain', 'phone', 'email', 'address',
        'latitude', 'longitude', 'subscription_plan_id', 'subscription_status',
    ];

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function websiteSetting(): HasOne
    {
        return $this->hasOne(WebsiteSetting::class);
    }

    public function mediaImages(): HasMany
    {
        return $this->hasMany(MediaImage::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function membershipPlans(): HasMany
    {
        return $this->hasMany(MembershipPlan::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(GymClass::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}