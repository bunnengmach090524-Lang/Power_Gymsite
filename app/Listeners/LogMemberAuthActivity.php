<?php

namespace App\Listeners;

use App\Models\Notification;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogMemberAuthActivity
{
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        if ($user->role !== 'member') {
            return;
        }

        $member = $user->member ?? \App\Models\Member::where('tenant_id', $user->tenant_id)
            ->where('email', $user->email)
            ->first();

        if (! $member) {
            return;
        }

        // Admin-side: tenant-wide notification (member_id null)
        Notification::create([
            'tenant_id' => $user->tenant_id,
            'type' => 'member_login',
            'title' => 'សមាជិកចូលប្រើប្រាស់',
            'message' => "{$member->name} បាន login ចូល account",
            'link' => "/dashboard/members/{$member->id}",
        ]);

        // Member-side: personal notification
        Notification::create([
            'tenant_id' => $user->tenant_id,
            'member_id' => $member->id,
            'type' => 'member_login_self',
            'title' => 'ការចូលប្រើប្រាស់ថ្មី',
            'message' => 'អ្នកបាន login ចូល account ជោគជ័យ',
            'link' => null,
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if (! $user || $user->role !== 'member') {
            return;
        }

        $member = $user->member ?? \App\Models\Member::where('tenant_id', $user->tenant_id)
            ->where('email', $user->email)
            ->first();

        if (! $member) {
            return;
        }

        Notification::create([
            'tenant_id' => $user->tenant_id,
            'type' => 'member_logout',
            'title' => 'សមាជិកចាកចេញ',
            'message' => "{$member->name} បាន logout ចេញពី account",
            'link' => "/dashboard/members/{$member->id}",
        ]);

        Notification::create([
            'tenant_id' => $user->tenant_id,
            'member_id' => $member->id,
            'type' => 'member_logout_self',
            'title' => 'ការចាកចេញ',
            'message' => 'អ្នកបាន logout ចេញពី account ជោគជ័យ',
            'link' => null,
        ]);
    }
}   