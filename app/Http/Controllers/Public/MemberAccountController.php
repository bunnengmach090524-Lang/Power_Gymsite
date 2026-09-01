<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ClassOrderItem;
use App\Models\GymClass;
use App\Models\Tenant;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\CheckIn;
use App\Models\ClassBooking;
use App\Models\Notification;
use App\Models\WebsiteSetting;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class MemberAccountController extends Controller
{
    protected array $dayMap = [
        'mon' => Carbon::MONDAY, 'tue' => Carbon::TUESDAY, 'wed' => Carbon::WEDNESDAY,
        'thu' => Carbon::THURSDAY, 'fri' => Carbon::FRIDAY, 'sat' => Carbon::SATURDAY, 'sun' => Carbon::SUNDAY,
    ];

    private function resolveMember(Request $request, Tenant $tenant): Member
    {
        $user = $request->user();

        abort_unless($user->role === 'member', 403);

        // ⚠️ FIX: កុំ firstOrFail() ភ្លាមៗលើ (user_id + tenant_id) តែម្នាក់ឯង។
        //
        // Root cause នៃ 404 ដែលធ្លាប់កើតឡើង (Lyna): GoogleAuthController::
        // redirectAfterLogin() resolve URL slug ដោយប្រើ $user->tenant_id
        // ជាមុនគេ (source of truth), ប៉ុន្តែ resolveMember() ដើមនៅតែ match
        // ដោយ Member.tenant_id ។ បើ column ទាំងពីរមិន sync គ្នា (data
        // legacy / race condition ចំពេល OAuth) — redirect ជោគជ័យ ប៉ុន្តែ
        // member page 404 ភ្លាមៗ ព្រោះគ្មាន Member row ណាមួយ match ព្រម
        // ទាំង (user_id = នេះ) AND (tenant_id = tenant នៃ URL)។
        //
        // ដូច្នេះ progressively fallback ដូចគ្នានឹង memberTodayClasses()
        // ក្នុង HandleInertiaRequests, ព្រោះ Member.user_id តែឯង គឺជា
        // identity ត្រឹមត្រូវបំផុត។ រាល់ fallback ដែលរកឃើញ member ត្រូវ
        // self-heal column ដែលមិន sync ភ្លាមៗ ដើម្បីកុំឲ្យ inconsistency
        // កើតឡើងម្តងទៀតលើ request បន្ទាប់។
        $member = Member::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($member) {
            return $member;
        }

        // Fallback ១: មាន Member ភ្ជាប់នឹង user នេះ ប៉ុន្តែ tenant_id
        // របស់វាមិនត្រូវនឹង tenant នៃ URL — ចាត់ទុក user_id ជាការពិត
        // (ព្រោះនេះមកពី auth session ផ្ទាល់) ហើយ self-heal tenant_id។
        $member = Member::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->first();

        if ($member) {
            if ($member->tenant_id !== $tenant->id) {
                $member->tenant_id = $tenant->id;
                $member->save();
            }

            return $member;
        }

        // Fallback ២: គ្មាន Member.user_id ភ្ជាប់ទាល់តែសោះ — ព្យាយាម
        // ស្វែងរកតាម (tenant_id + email) ដូចគ្នានឹង memberTodayClasses(),
        // រួច self-heal ភ្ជាប់ user_id ។
        $member = Member::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $user->email)
            ->first();

        if ($member) {
            $member->user_id = $user->id;
            $member->save();

            return $member;
        }

        // គ្មាន Member record ណាមួយសមស្របទាល់តែសោះ — នេះទើបជា 404 ពិត
        // (member ថ្មីទាំងស្រុងដែលមិនធ្លាប់មាន Member row ត្រូវបានបង្កើត)។
        abort(404);
    }

    public function show(Request $request, string $slug)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();
        $member = $this->resolveMember($request, $tenant);

        $activeSubscription = MemberSubscription::where('member_id', $member->id)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->with('plan')
            ->latest('end_date')
            ->first();

        $recentCheckIns = CheckIn::where('member_id', $member->id)
            ->latest('checked_in_at')
            ->limit(10)
            ->get(['id', 'checked_in_at']);

        $bookings = ClassBooking::where('member_id', $member->id)
            ->with('gymClass:id,name,schedule_day,start_time,end_time')
            ->latest('booked_at')
            ->get();

        $upcomingBookings = $bookings->map(function ($booking) {
            $class = $booking->gymClass;
            if (! $class) return null;

            $nextDate = Carbon::now()->next($this->dayMap[$class->schedule_day]);
            if (Carbon::now()->dayOfWeekIso === Carbon::now()->next($this->dayMap[$class->schedule_day])->dayOfWeekIso
                && Carbon::now()->format('H:i:s') < $class->start_time) {
                $nextDate = Carbon::now()->startOfDay();
            }

            $nextOccurrence = Carbon::parse($nextDate->toDateString() . ' ' . $class->start_time);
            if ($nextOccurrence->isPast()) {
                $nextOccurrence->addWeek();
            }

            return [
                'booking_id' => $booking->id,
                'class_id' => $class->id,
                'class_name' => $class->name,
                'schedule_day' => $class->schedule_day,
                'start_time' => $class->start_time,
                'end_time' => $class->end_time,
                'next_occurrence' => $nextOccurrence->toIso8601String(),
            ];
        })->filter()->sortBy('next_occurrence')->values();

        // class_id (not gym_class_id) — matches ClassBooking::$fillable.
        $bookedClassIds = $bookings->pluck('class_id')->all();

        $availableClasses = GymClass::where('tenant_id', $tenant->id)
            ->withCount('bookings')
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($class) => [
                'id' => $class->id,
                'name' => $class->name,
                'schedule_day' => $class->schedule_day,
                'start_time' => $class->start_time,
                'end_time' => $class->end_time,
                'capacity' => $class->capacity,
                'spots_left' => max(0, $class->capacity - $class->bookings_count),
                'is_booked' => in_array($class->id, $bookedClassIds, true),
            ]);

        return Inertia::render('Client/GymMemberAccount', [
            'tenant' => $tenant->only('id', 'name', 'slug'),
            'settings' => WebsiteSetting::where('tenant_id', $tenant->id)
                        ->with('logoImage')
                        ->first(),
            'member' => $member->only('id', 'name', 'email', 'phone', 'photo_url', 'joined_date'),
            'activeSubscription' => $activeSubscription,
            'recentCheckIns' => $recentCheckIns,
            'upcomingBookings' => $upcomingBookings,
            'availableClasses' => $availableClasses,
        ]);
    }

    public function update(Request $request, string $slug)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();
        $member = $this->resolveMember($request, $tenant);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        if ($request->hasFile('photo')) {
            MediaUrl::delete($member->getRawOriginal('photo_url'));
            $validated['photo_url'] = $request->file('photo')->store('avatars', config('filesystems.media_disk', 'public'));
        }

        $member->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? $member->phone,
            'photo_url' => $validated['photo_url'] ?? $member->getRawOriginal('photo_url'),
        ]);

        return back()->with('success', 'ព័ត៌មានត្រូវបានកែប្រែជោគជ័យ');
    }

    /**
     * Member self-enrolls into a class. No capacity check by design —
     * spots_left is informational only; staff/admin retain override
     * judgment for walk-ins/VIPs. firstOrCreate prevents duplicate rows.
     */
    public function bookClass(Request $request, string $slug, GymClass $class)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();
        $member = $this->resolveMember($request, $tenant);

        abort_unless($class->tenant_id === $tenant->id, 404);

        $booking = ClassBooking::firstOrCreate(
            ['member_id' => $member->id, 'class_id' => $class->id],
            ['booked_at' => now()]
        );

        // 👇 បន្ថែម notification សម្រាប់ admin — ត្រឹមតែពេលបង្កើតថ្មីប៉ុណ្ណោះ
        if ($booking->wasRecentlyCreated) {
            Notification::create([
                'tenant_id' => $tenant->id,
                'type' => 'class_booking',
                'title' => 'ការកក់ Class ថ្មី',
                'message' => "{$member->name} បានកក់ថ្នាក់ {$class->name}",
                'link' => route('dashboard.classes.roster', $class),
            ]);
            // 👇 Notification សម្រាប់ member ខ្លួនឯង — confirmation ថាការកក់ជោគជ័យ
            Notification::create([
                'tenant_id' => $tenant->id,
                'member_id' => $member->id,
                'type' => 'class_booking_confirmed',
                'title' => 'ការកក់ Class ជោគជ័យ',
                'message' => "អ្នកបានកក់ថ្នាក់ {$class->name} ជោគជ័យ",
                'link' => null,
            ]);
        }

        return back()->with('success', 'អ្នកបានចុះឈ្មោះចូលរួម class នេះជោគជ័យ');
    }

    public function unbookClass(Request $request, string $slug, GymClass $class)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();
        $member = $this->resolveMember($request, $tenant);

        $deleted = ClassBooking::where('member_id', $member->id)
            ->where('class_id', $class->id)
            ->delete();

        if ($deleted) {
            $isPaid = $class->isPaid();

            // ពិនិត្យថាតើ member ធ្លាប់ទូទាត់ paid class នេះមែនទេ (verified order)
            $wasPaidOrder = $isPaid && ClassOrderItem::where('class_id', $class->id)
                ->whereHas('classOrder', fn ($q) => $q->where('member_id', $member->id)->where('status', 'verified'))
                ->exists();

            Notification::create([
                'tenant_id' => $tenant->id,
                'type' => 'class_unbooking',
                'title' => 'សមាជិកចាកចេញពី Class',
                'message' => $wasPaidOrder
                    ? "{$member->name} បានចាកចេញពីថ្នាក់ {$class->name} (បង់ថ្លៃរួច — សូមពិនិត្យថាតើត្រូវ refund ដែរឬទេ)"
                    : "{$member->name} បានចាកចេញពីថ្នាក់ {$class->name}",
                'link' => route('dashboard.classes.roster', $class),
            ]);
        }

        return back()->with('success', 'អ្នកបានលុបចេញពី class នេះជោគជ័យ');
    }

     /**
     * Member marks a single notification as read. Scoped to their own
     * member_id — a member must never be able to mark another member's
     * (or an admin-only, member_id=null) notification as read.
     */
    public function markNotificationRead(Request $request, string $slug, \App\Models\Notification $notification)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();
        $member = $this->resolveMember($request, $tenant);

        abort_unless($notification->member_id === $member->id, 403);

        $notification->update(['read_at' => now()]);

        return back();
    }

    public function markAllNotificationsRead(Request $request, string $slug)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();
        $member = $this->resolveMember($request, $tenant);

        \App\Models\Notification::where('member_id', $member->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }

    public function qrCode(Request $request, string $slug)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();
        $member = $this->resolveMember($request, $tenant);

        if (! $member->qr_token) {
            $member->qr_token = \Illuminate\Support\Str::random(24);
            $member->save();
        }

        return response(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::size(280)->generate($member->qr_token)
        )->header('Content-Type', 'image/svg+xml')
        ->header('Content-Disposition', 'inline; filename="member-' . $member->id . '-qr.svg"');
    }
}