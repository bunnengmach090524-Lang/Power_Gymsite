<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSubscription extends Model
{
    protected $fillable = [
        'member_id', 'membership_plan_id', 'promotion_id',
        'start_date', 'end_date', 'final_price', 'status',
        'paused_from', 'paused_until', 'tenant_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'paused_from' => 'date',
        'paused_until' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (MemberSubscription $subscription) {
            if (empty($subscription->tenant_id) && auth()->check()) {
                $subscription->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}