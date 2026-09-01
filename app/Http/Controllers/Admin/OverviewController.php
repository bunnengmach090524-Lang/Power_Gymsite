<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OverviewController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $range = $request->query('range', 'monthly');

        $totalMembers = Member::where('tenant_id', $tenantId)->count();
        $activeSubscriptions = MemberSubscription::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        // Day tab for the "Today's Classes" card — defaults to today,
        // but the admin can flip through mon..sun without leaving the page.
        $day = $request->query('day', strtolower(now()->format('D')));
        $validDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        if (! in_array($day, $validDays, true)) {
            $day = strtolower(now()->format('D'));
        }

        return inertia('Admin/Overview', [
            'stats' => [
                'totalMembers' => $totalMembers,
                'expiringSoon' => MemberSubscription::where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->whereDate('end_date', '<=', now()->addDays(7))
                    ->count(),
                // 👇 whereNull('refunded_at') — refunded payments no longer
                // count toward revenue, matching the payments list's status.
                'monthlyRevenue' => round(Payment::where('tenant_id', $tenantId)
                    ->whereNull('refunded_at')
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->sum('amount'), 2),
                'activeSubscriptions' => $activeSubscriptions,
            ],
            'recentMembers' => Member::where('tenant_id', $tenantId)->with('subscriptions')->latest()->take(5)->get(),
            'range' => $range,
            'chartData' => $this->chartData($tenantId, $range),
            'nextExpiring' => $this->nextExpiring($tenantId),
            'renewalRate' => $totalMembers > 0 ? round(($activeSubscriptions / $totalMembers) * 100) : 0,
             'membersByPlan' => $this->membersByPlan($tenantId),
            'todayClassSpotlight' => $this->todayClassSpotlight($tenantId),
            'todayClasses' => $this->classesForDay($tenantId, $day),
            'selectedDay' => $day,
        ]);
    }

    /**
     * Random class scheduled today that has a photo, for the dashboard
     * hero banner background. Prefers classes with images; if none of
     * today's classes have one, returns null so the banner falls back to
     * its plain gradient (no image, no crash).
     */
    private function todayClassSpotlight(int $tenantId): ?array
    {
        $todayAbbr = strtolower(now()->format('D'));

        $class = GymClass::where('tenant_id', $tenantId)
            ->where('schedule_day', $todayAbbr)
            ->whereNotNull('image_url')
            ->inRandomOrder()
            ->first(['id', 'name', 'start_time', 'end_time', 'image_url']);

        if (! $class) {
            return null;
        }

        return [
            'name' => $class->name,
            'startTime' => $class->start_time,
            'endTime' => $class->end_time,
            'imageUrl' => $class->image_url,
        ];
    }

    /**
     * All classes scheduled on a given day (mon..sun), for the "Classes"
     * list card. Ordered by start time so the schedule reads top-to-bottom.
     */
    private function classesForDay(int $tenantId, string $dayAbbr)
    {
        return GymClass::where('tenant_id', $tenantId)
            ->where('schedule_day', $dayAbbr)
            ->with('trainer:id,name')
            ->orderBy('start_time')
            ->get(['id', 'trainer_id', 'name', 'start_time', 'end_time', 'capacity'])
            ->map(fn ($class) => [
                'id' => $class->id,
                'name' => $class->name,
                'startTime' => $class->start_time,
                'endTime' => $class->end_time,
                'capacity' => $class->capacity,
                'trainerName' => $class->trainer?->name,
            ]);
    }

    /**
     * All classes scheduled today, for the "Today's Classes" list card.
     * Ordered by start time so the schedule reads top-to-bottom.
     */
    private function todayClasses(int $tenantId)
    {
        $todayAbbr = strtolower(now()->format('D'));

        return GymClass::where('tenant_id', $tenantId)
            ->where('schedule_day', $todayAbbr)
            ->with('trainer:id,name')
            ->orderBy('start_time')
            ->get(['id', 'trainer_id', 'name', 'start_time', 'end_time', 'capacity'])
            ->map(fn ($class) => [
                'id' => $class->id,
                'name' => $class->name,
                'startTime' => $class->start_time,
                'endTime' => $class->end_time,
                'capacity' => $class->capacity,
                'trainerName' => $class->trainer?->name,
            ]);
    }

    private function chartData(int $tenantId, string $range): array
    {
        if ($range === 'daily') {
            $points = collect(range(13, 0))->map(fn ($i) => now()->subDays($i));
            $labels = $points->map(fn ($d) => $d->translatedFormat('d M'))->values();
            $revenue = $points->map(fn ($d) => (float) Payment::where('tenant_id', $tenantId)
                ->whereNull('refunded_at')
                ->whereDate('paid_at', $d)->sum('amount'))->values();
            $newMembers = $points->map(fn ($d) => Member::where('tenant_id', $tenantId)
                ->whereDate('created_at', $d)->count())->values();
        } elseif ($range === 'yearly') {
            $points = collect(range(4, 0))->map(fn ($i) => now()->subYears($i));
            $labels = $points->map(fn ($y) => $y->format('Y'))->values();
            $revenue = $points->map(fn ($y) => (float) Payment::where('tenant_id', $tenantId)
                ->whereNull('refunded_at')
                ->whereYear('paid_at', $y->year)->sum('amount'))->values();
            $newMembers = $points->map(fn ($y) => Member::where('tenant_id', $tenantId)
                ->whereYear('created_at', $y->year)->count())->values();
        } else { // monthly (default)
            $points = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));
            $labels = $points->map(fn ($m) => $m->translatedFormat('M Y'))->values();
            $revenue = $points->map(fn ($m) => (float) Payment::where('tenant_id', $tenantId)
                ->whereNull('refunded_at')
                ->whereMonth('paid_at', $m->month)->whereYear('paid_at', $m->year)->sum('amount'))->values();
            $newMembers = $points->map(fn ($m) => Member::where('tenant_id', $tenantId)
                ->whereMonth('created_at', $m->month)->whereYear('created_at', $m->year)->count())->values();
        }

        return compact('labels', 'revenue', 'newMembers');
    }

    private function nextExpiring(int $tenantId): ?array
    {
        $row = MemberSubscription::query()
            ->join('members', 'member_subscriptions.member_id', '=', 'members.id')
            ->join('membership_plans', 'member_subscriptions.membership_plan_id', '=', 'membership_plans.id')
            ->where('member_subscriptions.tenant_id', $tenantId)
            ->where('member_subscriptions.status', 'active')
            ->where('member_subscriptions.end_date', '>=', now())
            ->orderBy('member_subscriptions.end_date', 'asc')
            ->select('members.name as member_name', 'membership_plans.name as plan_name', 'member_subscriptions.end_date')
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'memberName' => $row->member_name,
            'planName' => $row->plan_name,
            'endDate' => $row->end_date,
            'daysLeft' => now()->diffInDays($row->end_date, false),
        ];
    }

    private function membersByPlan(int $tenantId)
    {
        return MemberSubscription::query()
            ->join('membership_plans', 'member_subscriptions.membership_plan_id', '=', 'membership_plans.id')
            ->where('member_subscriptions.tenant_id', $tenantId)
            ->where('member_subscriptions.status', 'active')
            ->selectRaw('membership_plans.name as label, COUNT(*) as count')
            ->groupBy('membership_plans.id', 'membership_plans.name')
            ->orderByDesc('count')
            ->get();
    }
}