<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['gym_admin', 'staff']);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->tenant_id === $payment->tenant_id
            && in_array($user->role, ['gym_admin', 'staff']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['gym_admin', 'staff']);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->tenant_id === $payment->tenant_id && $user->role === 'gym_admin';
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->tenant_id === $payment->tenant_id && $user->role === 'gym_admin';
    }
}