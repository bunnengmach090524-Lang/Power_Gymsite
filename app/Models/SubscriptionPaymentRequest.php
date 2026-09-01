<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPaymentRequest extends Model
{
    protected $fillable = [
        'tenant_id', 'member_id', 'membership_plan_id', 'promotion_id',
        'amount', 'khqr_md5', 'khqr_qr_string', 'status',
        'verified_method', 'approved_by', 'receipt_path', 'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function member() { return $this->belongsTo(Member::class); }
    public function plan() { return $this->belongsTo(MembershipPlan::class, 'membership_plan_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}