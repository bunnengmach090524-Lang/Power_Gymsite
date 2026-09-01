<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['gym_admin', 'staff'], true);
    }

    public function view(User $user, Member $member): bool
    {
        return $user->tenant_id === $member->tenant_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['gym_admin', 'staff'], true);
    }

    public function update(User $user, Member $member): bool
    {
        return $user->tenant_id === $member->tenant_id
            && in_array($user->role, ['gym_admin', 'staff'], true);
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->tenant_id === $member->tenant_id && $user->role === 'gym_admin';
    }
}