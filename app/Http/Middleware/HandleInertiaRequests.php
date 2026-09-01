<?php

namespace App\Http\Middleware;

use App\Models\GymClass;
use App\Models\Member;
use App\Models\MediaImage;
use App\Models\MemberSubscription;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()
                    ? [
                        ...$request->user()->only('id', 'name', 'email', 'role', 'tenant_id'),
                        'avatar' => $request->user()->role === 'member'
                            // ⚠️ FIX: fallback ទៅ $user->avatar បើ Member record
                            // មិនទាន់ភ្ជាប់ត្រឹមត្រូវ (member?->photo_url ជា null) —
                            // ជាងទុកឲ្យ avatar បាត់ទាំងស្រុងដោយគ្មានហេតុផលច្បាស់លាស់
                            ? ($request->user()->member?->photo_url ?? $request->user()->avatar ?? null)
                            : ($request->user()->avatar ?? null),
                        // ⚠️ FIX: root cause នៃ bug ដែល member ត្រូវបានបង្ហាញ
                        // ជា "guest" នៅក្នុង SiteHeader (isMemberHere = false)
                        // ទោះបីជា role = 'member' ហើយ authenticated ត្រឹមត្រូវ។
                        //
                        // ដើមឡើយកូដពឹងផ្អែកតែលើ $user->member?->tenant?->slug —
                        // ដូចគ្នាបេះបិទនឹង bug ចាស់ក្នុង
                        // GoogleAuthController::redirectAfterLogin(). បើ Member
                        // record មិនទាន់ភ្ជាប់ត្រឹមត្រូវ (user_id mismatch, data
                        // legacy មុន fix, race condition ចំពេល Google OAuth
                        // callback) នោះ member_tenant_slug ក្លាយជា null ទាំង
                        // ដែល user កំពុង authenticated ជា member ពិតប្រាកដ —
                        // ធ្វើឲ្យ Vue's `isMemberHere` computed ក្លាយជា false
                        // ហើយ UI បង្ហាញប៊ូតុង "ចូល Staff/Admin" ដែលចុះចប់ត្រង់
                        // 403 (guest middleware bounce ទៅ dashboard.overview
                        // ដែល role member មិនអាចចូលបាន)។
                        //
                        // Fallback ទៅ $user->tenant_id (column ផ្ទាល់លើ User,
                        // កំណត់ជានិច្ចនៅពេល create() មិនអាស្រ័យលើ Member
                        // relation ទាល់តែសោះ) ជាមុនគេ — ដូចគ្នានឹង fix ក្នុង
                        // GoogleAuthController.
                        'member_tenant_slug' => $request->user()->role === 'member'
                            ? ($request->user()->tenant?->slug
                                ?? $request->user()->member?->tenant?->slug)
                            : null,
                    ]
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'warning' => fn () => $request->session()->get('warning'),
                'telegramLink' => fn () => $request->session()->get('telegramLink'),
                'checkedInMember' => fn () => $request->session()->get('checkedInMember'),
            ],
            'sidebarCounts' => fn () => $this->sidebarCounts($request),
            'notifications' => fn () => $this->notifications($request),
            'unreadNotificationsCount' => fn () => $this->unreadNotificationsCount($request),
            'todayStats' => fn () => $this->todayStats($request),
            'upcomingExpiring' => fn () => $this->upcomingExpiring($request),
            'tenantBranding' => fn () => $this->tenantBranding($request),
            'todayClasses' => fn () => $this->todayClasses($request),
            'memberTodayClasses' => fn () => $this->memberTodayClasses($request),
            'memberNotifications' => fn () => $this->memberNotifications($request),
        ];  
    }

    private function sidebarCounts(Request $request): array
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            return [];
        }

        $tenantId = $user->tenant_id;
        $todayAbbr = strtolower(now()->format('D'));

        return [
            'members' => Member::where('tenant_id', $tenantId)->count(),
            'classes' => GymClass::where('tenant_id', $tenantId)
                ->where('schedule_day', $todayAbbr)
                ->count(),
            'payments' => Payment::where('tenant_id', $tenantId)
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->count(),
            'promotions' => Promotion::where('tenant_id', $tenantId)
                ->where('active', true)
                ->where('end_date', '>=', now())
                ->count(),
            'checkins' => \App\Models\CheckIn::where('tenant_id', $tenantId)
                ->whereDate('checked_in_at', today())
                ->count(),
        ];
    }

    private function notifications(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            return [];
        }

        return Notification::where('tenant_id', $user->tenant_id)
            ->latest()
            ->limit(10)
            ->get();
    }

    private function unreadNotificationsCount(Request $request): int
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            return 0;
        }

        return Notification::where('tenant_id', $user->tenant_id)
            ->unread()
            ->count();
    }

    private function todayStats(Request $request): array
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            return ['newMembers' => 0, 'revenue' => 0];
        }

        $tenantId = $user->tenant_id;

        return [
            'newMembers' => Member::where('tenant_id', $tenantId)
                ->whereDate('joined_date', now())
                ->count(),
            'revenue' => (float) Payment::where('tenant_id', $tenantId)
                ->whereDate('paid_at', now())
                ->sum('amount'),
        ];
    }

    private function upcomingExpiring(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            return [];
        }

        return MemberSubscription::with('member:id,name')
            ->where('tenant_id', $user->tenant_id)
            ->where('status', 'active')
            ->whereNull('paused_from')
            ->whereDate('end_date', '>=', now())
            ->whereDate('end_date', '<=', now()->addDays(7))
            ->orderBy('end_date')
            ->limit(5)
            ->get()
            ->map(fn ($sub) => [
                'id' => $sub->id,
                'memberId' => $sub->member_id,
                'memberName' => $sub->member->name,
                'endDate' => $sub->end_date->format('Y-m-d'),
                'daysLeft' => (int) now()->startOfDay()->diffInDays($sub->end_date->startOfDay(), false),
            ]);
    }

    private function todayClasses(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            return [];
        }

        $todayAbbr = strtolower(now()->format('D'));

        return GymClass::where('tenant_id', $user->tenant_id)
            ->where('schedule_day', $todayAbbr)
            ->orderBy('start_time')
            ->limit(6)
            ->get(['id', 'name', 'start_time']);
    }

    private function memberTodayClasses(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'member') {
            return [];
        }

        // ⚠️ FIX: fallback ទៅ tenant_id + email lookup បើ $user->member
        // ជា null (relation មិនទាន់ភ្ជាប់ត្រឹមត្រូវ) — ជៀសវាងការត្រឡប់ empty
        // array ស្ងាត់ៗដោយគ្មានហេតុផល ដែលធ្វើឲ្យ "Today's classes" widget
        // បង្ហាញទទេជានិច្ចសម្រាប់ member ដែលមាន data inconsistency ដដែល។
        $member = $user->member ?? Member::where('tenant_id', $user->tenant_id)
            ->where('email', $user->email)
            ->first();

        if (! $member) {
            return [];
        }

        $todayAbbr = strtolower(now()->format('D'));

        return \App\Models\ClassBooking::where('member_id', $member->id)
            ->whereHas('gymClass', fn ($q) => $q->where('schedule_day', $todayAbbr))
            ->with('gymClass:id,name,start_time,end_time')
            ->get()
            ->map(fn ($booking) => [
                'id' => $booking->id,
                'name' => $booking->gymClass->name,
                'start_time' => $booking->gymClass->start_time,
                'end_time' => $booking->gymClass->end_time,
            ])
            ->sortBy('start_time')
            ->values();
    }

    private function memberNotifications(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'member') {
            return [];
        }

        $member = $user->member ?? Member::where('tenant_id', $user->tenant_id)
            ->where('email', $user->email)
            ->first();

        if (! $member) {
            return [];
        }

        return Notification::where('member_id', $member->id)
            ->latest()
            ->limit(10)
            ->get();
    }

    private function tenantBranding(Request $request): array
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            return ['name' => 'GymSite', 'logoUrl' => null, 'publicUrl' => null];
        }

        $tenant = $user->tenant;
        $websiteSetting = $tenant->websiteSetting;
        $logoImageId = $websiteSetting?->logo_image_id;
        $logoUrl = null;

        if ($logoImageId) {
            $logo = MediaImage::find($logoImageId);

            if ($logo) {
                $logoUrl = $logo->image_url;
            } else {
                $websiteSetting?->update(['logo_image_id' => null]);
            }
        }

        return [
            'name' => $tenant->name ?? 'GymSite',
            'logoUrl' => $logoUrl,
            'publicUrl' => $tenant->slug ? route('public.home', $tenant->slug) : null,
            'subscriptionStatus' => $tenant->subscription_status,
        ];
    }
}