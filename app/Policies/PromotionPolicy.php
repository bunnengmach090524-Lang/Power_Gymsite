<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'gym_admin';
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return $user->tenant_id === $promotion->tenant_id && $user->role === 'gym_admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'gym_admin';
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $user->tenant_id === $promotion->tenant_id && $user->role === 'gym_admin';
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $user->tenant_id === $promotion->tenant_id && $user->role === 'gym_admin';
    }
}