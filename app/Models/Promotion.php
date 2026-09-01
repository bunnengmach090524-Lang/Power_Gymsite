<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\TenantScope;

class Promotion extends Model
{
    protected $fillable = [
        'tenant_id', 'title', 'description', 'discount_type', 'discount_value',
        'applicable_plan_id', 'start_date', 'end_date', 'active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function applicablePlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'applicable_plan_id');
    }

    public function scopeCurrentlyLive($query)
    {
        return $query->where('active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now());
    }
}