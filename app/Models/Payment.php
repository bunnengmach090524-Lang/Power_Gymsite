<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\TenantScope;

class Payment extends Model
{
    protected $fillable = [
        'tenant_id', 'member_id', 'amount', 'method',
        'paid_at', 'reference_type', 'reference_id',
        'refunded_at', 'refunded_by', 'refund_note',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }


    public function refundedBy() : BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'refunded_by');
    }
}