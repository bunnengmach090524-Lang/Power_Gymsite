<?php

namespace App\Policies;

use App\Models\GymClass;
use App\Models\User;

class GymClassPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['gym_admin', 'staff'], true);
    }

    public function view(User $user, GymClass $gymClass): bool
    {
        return $user->tenant_id === $gymClass->tenant_id
            && in_array($user->role, ['gym_admin', 'staff'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['gym_admin', 'staff'], true);
    }

    public function update(User $user, GymClass $gymClass): bool
    {
        return $user->tenant_id === $gymClass->tenant_id
            && in_array($user->role, ['gym_admin', 'staff'], true);
    }

    public function delete(User $user, GymClass $gymClass): bool
    {
        return $user->tenant_id === $gymClass->tenant_id
            && $user->role === 'gym_admin'; // 👈 admin ប៉ុណ្ណោះ
    }
}