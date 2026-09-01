<?php

namespace App\Policies;

use App\Models\MediaImage;
use App\Models\User;

class MediaImagePolicy
{
    public function create(User $user): bool
    {
        return $user->role === 'gym_admin';
    }

    public function delete(User $user, MediaImage $mediaImage): bool
    {
        return $user->tenant_id === $mediaImage->tenant_id && $user->role === 'gym_admin';
    }
}