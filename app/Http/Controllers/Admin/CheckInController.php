<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Member;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $todayCheckIns = CheckIn::where('tenant_id', $tenantId)
            ->whereDate('checked_in_at', today())
            ->with(['member:id,name,phone', 'staff:id,name'])
            ->latest('checked_in_at')
            ->get();

        $totalActiveMembers = Member::where('tenant_id', $tenantId)
            ->whereHas('subscriptions', fn ($q) => $q->where('status', 'active')->where('end_date', '>=', now()))
            ->count();

        return inertia('Admin/CheckIn/Index', [
            'todayCheckIns' => $todayCheckIns,
            'todayCount' => $todayCheckIns->count(),
            'checkInRate' => $totalActiveMembers > 0
                ? round(($todayCheckIns->pluck('member_id')->unique()->count() / $totalActiveMembers) * 100)
                : 0,
            'totalActiveMembers' => $totalActiveMembers,
            'weeklyTrend' => $this->checkInTrend($tenantId, now()->subDays(6), now()),
            'monthlyTrend' => $this->checkInTrend($tenantId, now()->startOfMonth(), now()->endOfMonth()),
        ]);
    }
    private function checkInTrend(string $tenantId, \Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        $counts = CheckIn::where('tenant_id', $tenantId)
            ->whereBetween('checked_in_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('DATE(checked_in_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day');

        $result = [];
        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($cursor->lte($endDay)) {
            $key = $cursor->format('Y-m-d');
            $result[] = [
                'date' => $key,
                'count' => (int) ($counts[$key] ?? 0),
            ];
            $cursor->addDay();
        }

        return $result;
    }

    public function scanPage(Request $request)
    {
        return inertia('Admin/CheckIn/Scan');
    }

    public function search(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $query = $request->query('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $members = Member::where('tenant_id', $tenantId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->withExists(['subscriptions as has_active_subscription' => function ($q) {
                $q->where('status', 'active')->where('end_date', '>=', now());
            }])
            ->with(['subscriptions' => function ($q) {
                $q->where('status', 'active')
                    ->orderByDesc('end_date')
                    ->limit(1);
            }])
            ->limit(8)
            ->get(['id', 'name', 'phone', 'photo_url']);

        // Mark whether already checked in today
        $checkedInTodayIds = CheckIn::where('tenant_id', $tenantId)
            ->whereDate('checked_in_at', today())
            ->pluck('member_id');

        $members->each(function ($member) use ($checkedInTodayIds) {
            $member->already_checked_in = $checkedInTodayIds->contains($member->id);

            $activeSub = $member->subscriptions->first();
            $status = $this->membershipStatusFromSubscription($activeSub);

            $member->subscription_status = $status['status'];
            $member->subscription_days_left = $status['daysLeft'];

            // Drop the raw relation from JSON — the frontend only needs the computed fields above
            $member->unsetRelation('subscriptions');
        });

        return response()->json($members);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
        ]);

        $tenantId = $request->user()->tenant_id;

        $member = Member::where('tenant_id', $tenantId)->findOrFail($validated['member_id']);

        $alreadyCheckedIn = CheckIn::where('tenant_id', $tenantId)
            ->where('member_id', $member->id)
            ->whereDate('checked_in_at', today())
            ->exists();

        if ($alreadyCheckedIn) {
            return back()->withErrors(['member_id' => 'សមាជិកនេះបាន check-in រួចហើយថ្ងៃនេះ']);
        }

        $status = $this->membershipStatus($member);

        // Block check-in entirely if membership has expired
        if ($status['status'] === 'expired') {
            return back()->withErrors([
                'member_id' => "❌ {$member->name} membership ផុតកំណត់ហើយ។ សូមបន្ត subscription មុននឹង check-in។",
            ]);
        }

        CheckIn::create([
            'tenant_id' => $tenantId,
            'member_id' => $member->id,
            'checked_in_by' => $request->user()->id,
            'checked_in_at' => now(),
        ]);

        $warningMessage = null;
        if ($status['status'] === 'expiring') {
            $warningMessage = $status['daysLeft'] <= 0
                ? "⚠️ {$member->name} membership ផុតកំណត់ថ្ងៃនេះ"
                : "⚠️ {$member->name} membership ជិតផុតកំណត់ក្នុងរយៈពេល {$status['daysLeft']} ថ្ងៃ";
        } elseif ($status['status'] === 'none') {
            $warningMessage = "⚠️ {$member->name} គ្មាន subscription សកម្មទេ";
        }

        return back()
            ->with('success', $member->name . ' បាន check-in ជោគជ័យ')
            ->with('warning', $warningMessage)
            ->with('checkedInMember', [
                'name' => $member->name,
                'photo_url' => $member->photo_url,
                'status' => $status['status'],
                'daysLeft' => $status['daysLeft'],
            ]);
    }

    public function destroy(Request $request, CheckIn $checkIn)
    {
        abort_unless($checkIn->tenant_id === $request->user()->tenant_id, 403);

        $checkIn->delete();

        return back()->with('success', 'Check-in ត្រូវបានលុប');
    }

    public function scan(Request $request)
    {
        $validated = $request->validate(['qr_token' => 'required|string']);

        $tenantId = $request->user()->tenant_id;

        $member = Member::where('tenant_id', $tenantId)
            ->where('qr_token', $validated['qr_token'])
            ->first();

        if (! $member) {
            return back()->withErrors(['qr_token' => 'QR Code មិនត្រឹមត្រូវ ឬមិនមែនជារបស់សមាជិកគម ណាមួយ']);
        }

        $alreadyCheckedIn = CheckIn::where('tenant_id', $tenantId)
            ->where('member_id', $member->id)
            ->whereDate('checked_in_at', today())
            ->exists();

        if ($alreadyCheckedIn) {
            return back()->withErrors(['qr_token' => $member->name . ' បាន check-in រួចហើយថ្ងៃនេះ']);
        }

        $status = $this->membershipStatus($member);

        // Block check-in entirely if membership has expired
        if ($status['status'] === 'expired') {
            return back()->withErrors([
                'qr_token' => "❌ {$member->name} membership ផុតកំណត់ហើយ។ សូមបន្ត subscription មុននឹង check-in។",
            ]);
        }

        CheckIn::create([
            'tenant_id' => $tenantId,
            'member_id' => $member->id,
            'checked_in_by' => $request->user()->id,
            'checked_in_at' => now(),
        ]);

        $warningMessage = null;
        if ($status['status'] === 'expiring') {
            $warningMessage = $status['daysLeft'] <= 0
                ? "⚠️ {$member->name} membership ផុតកំណត់ថ្ងៃនេះ"
                : "⚠️ {$member->name} membership ជិតផុតកំណត់ក្នុងរយៈពេល {$status['daysLeft']} ថ្ងៃ";
        } elseif ($status['status'] === 'none') {
            $warningMessage = "⚠️ {$member->name} គ្មាន subscription សកម្មទេ";
        }

        return back()
            ->with('success', $member->name . ' បាន check-in ជោគជ័យ')
            ->with('warning', $warningMessage)
            ->with('checkedInMember', [
                'name' => $member->name,
                'photo_url' => $member->photo_url,
                'status' => $status['status'],
                'daysLeft' => $status['daysLeft'],
            ]);
    }

    /**
     * Compute a member's current membership status.
     * - active: no expiry concern
     * - expiring: active but ending within 7 days
     * - expired: past end_date
     * - none: never had an active subscription
     */
    private function membershipStatus(Member $member): array
    {
        $activeSub = $member->subscriptions()
            ->where('status', 'active')
            ->orderByDesc('end_date')
            ->first();

        return $this->membershipStatusFromSubscription($activeSub);
    }

    private function membershipStatusFromSubscription($activeSub): array
    {
        if (! $activeSub) {
            return ['status' => 'none', 'daysLeft' => null];
        }

        $daysLeft = (int) now()->startOfDay()->diffInDays($activeSub->end_date->startOfDay(), false);

        if ($daysLeft < 0) {
            return ['status' => 'expired', 'daysLeft' => $daysLeft];
        }

        if ($daysLeft <= 7) {
            return ['status' => 'expiring', 'daysLeft' => $daysLeft];
        }

        return ['status' => 'active', 'daysLeft' => $daysLeft];
    }
}