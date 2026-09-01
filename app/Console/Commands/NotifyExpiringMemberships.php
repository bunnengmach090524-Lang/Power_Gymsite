<?php

namespace App\Console\Commands;

use App\Models\MemberSubscription;
use App\Models\Notification;
use Illuminate\Console\Command;

class NotifyExpiringMemberships extends Command
{
    protected $signature = 'notifications:membership-expiring';
    protected $description = 'ជូនដំណឹង admin/staff អំពី membership ជិតផុតកំណត់ក្នុងរយៈពេល 3 ថ្ងៃ';

    public function handle(): void
    {
        $expiring = MemberSubscription::with('member')
            ->where('status', 'active')
            ->whereDate('end_date', '<=', now()->addDays(3))
            ->whereDate('end_date', '>=', now())
            ->get();

        foreach ($expiring as $sub) {
            // ជៀសវាង notification ស្ទួន បើ command រត់ច្រើនដងក្នុងថ្ងៃតែមួយ
            $alreadyNotified = Notification::withoutGlobalScopes()
                ->where('tenant_id', $sub->member->tenant_id)
                ->where('type', 'membership_expiring')
                ->where('link', "/dashboard/members/{$sub->member_id}")
                ->whereDate('created_at', now())
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            Notification::create([
                'tenant_id' => $sub->member->tenant_id,
                'type' => 'membership_expiring',
                'title' => 'សមាជិកភាពជិតផុតកំណត់',
                'message' => "{$sub->member->name} membership ផុតកំណត់នៅថ្ងៃ {$sub->end_date->format('d/m/Y')}",
                'link' => "/dashboard/members/{$sub->member_id}",
            ]);
        }

        $this->info("Checked {$expiring->count()} expiring subscriptions.");
    }
}