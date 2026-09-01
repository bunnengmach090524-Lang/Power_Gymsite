<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Member::class);

        $search = $request->query('search');

        $members = Member::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->withCount('subscriptions')
            ->withSum('payments', 'amount')
            ->with(['subscriptions' => function ($query) {
                $query->where('status', 'active')
                    ->where('end_date', '>=', now())
                    ->latest('end_date')
                    ->limit(1);
            }])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return inertia('Admin/Members/Index', [
            'members' => $members,
            'search' => $search,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Member::class);

        return inertia('Admin/Members/Create', [
            'membershipPlans' => MembershipPlan::all(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Member::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
        ]);

        auth()->user()->tenant->members()->create([
            ...$validated,
            'joined_date' => now(),
        ]);

        return redirect()->route('dashboard.members.index')->with('success', 'Member registered.');
    }

    public function edit(Member $member)
    {
        $this->authorize('update', $member);

        return inertia('Admin/Members/Edit', ['member' => $member]);
    }

    public function update(Request $request, Member $member)
    {
        $this->authorize('update', $member);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $member->update($validated);

        return back()->with('success', 'Member updated.');
    }

    public function destroy(Member $member)
    {
        $this->authorize('delete', $member);

        // ⚠️ FIX: $member->delete() តែម្នាក់ឯង ទុក User account ដែលភ្ជាប់
        // (member.user_id) ឲ្យអណ្តែតជា "orphan" — role នៅតែ 'member',
        // tenant_id នៅតែសកម្ម, password/Google login នៅតែដំណើរការ។
        // User នោះនៅតែអាច login បានជានិច្ច ប៉ុន្តែជួប 404 រាល់ទំព័រ member
        // (account, booking, ។ល។) ដោយគ្មានហេតុផលច្បាស់លាស់ចំពោះគាត់ផ្ទាល់ —
        // ព្រោះ resolveMember() រកមិនឃើញ Member row ដើម្បីភ្ជាប់ទៀតទេ។
        //
        // ដោយសារ admin លុប Member ដោយចេតនា (បញ្ចប់ membership) ត្រូវលុប
        // User account ដែលភ្ជាប់ជាមួយផងដែរ ដើម្បីបិទការចូលប្រើទាំងស្រុង —
        // ជៀសវាង orphan User ថ្មីកើតឡើងម្តងទៀត។ Wrap ក្នុង transaction ដើម្បី
        // ធានាថា Member និង User ត្រូវលុបជាមួយគ្នាទាំងស្រុង ឬមិនលុបទាល់តែសោះ។
        //
        // ការពារបន្ថែម: លុប User តែក្នុងករណី role === 'member' ប៉ុណ្ណោះ —
        // ជៀសវាងលុប gym_admin/staff account ដោយចៃដន្យ បើ data legacy ណាមួយ
        // មាន user_id ភ្ជាប់khុសប្រភេទ។
        DB::transaction(function () use ($member) {
            $userId = $member->user_id;

            $member->delete();

            if ($userId) {
                $user = User::find($userId);

                if ($user && $user->role === 'member') {
                    $user->delete();
                }
            }
        });

        return back()->with('success', 'Member removed.');
    }

    public function show(Member $member)
    {
        $this->authorize('view', $member);

        return inertia('Admin/Members/Show', [
            'member' => $member->load('subscriptions', 'payments', 'classBookings.gymClass'),
            'tenant' => auth()->user()->tenant->only('name', 'logo_url'),
            'availableClasses' => \App\Models\GymClass::query()
                ->orderBy('schedule_day')
                ->orderBy('start_time')
                ->get(['id', 'name', 'schedule_day', 'start_time', 'end_time', 'capacity'])
                ->each->append('spots_left'),
        ]);
    }

    /**
     * Admin/staff adds a member directly into a class (walk-in registration,
     * phone sign-up, etc.) — bypasses the member self-booking flow entirely.
     */
    public function bookClass(Request $request, Member $member)
    {
        $this->authorize('update', $member);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        // Tenant-safety: the class must belong to the same tenant as the member,
        // even though GymClass's own TenantScope should already guarantee this
        // for the current admin — this is a defense-in-depth check.
        $gymClass = \App\Models\GymClass::findOrFail($validated['class_id']);
        abort_unless($gymClass->tenant_id === $member->tenant_id, 403);

        $alreadyBooked = $member->classBookings()->where('class_id', $gymClass->id)->exists();
        abort_if($alreadyBooked, 422, 'សមាជិកនេះបានកក់ class នេះរួចហើយ');

        $member->classBookings()->create([
            'class_id' => $gymClass->id,
            'booked_at' => now(),
        ]);

        return back()->with('success', 'បានបន្ថែមទៅក្នុង class ជោគជ័យ');
    }

    public function unbookClass(Member $member, \App\Models\ClassBooking $booking)
    {
        $this->authorize('update', $member);
        abort_unless($booking->member_id === $member->id, 403);

        $booking->delete();

        return back()->with('success', 'បានដកចេញពី class');
    }

    public function qrCode(Member $member)
    {
        $this->authorize('view', $member);

        if (! $member->qr_token) {
            $member->qr_token = \Illuminate\Support\Str::random(24);
            $member->save();
        }

        return response(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->generate($member->qr_token)
        )->header('Content-Type', 'image/svg+xml')
        ->header('Content-Disposition', 'inline; filename="member-' . $member->id . '-qr.svg"');
    }
    public function connectTelegram(Member $member)
    {
        $this->authorize('update', $member);

        \Illuminate\Support\Facades\Log::info('connectTelegram CALLED', ['member_id' => $member->id, 'time' => now()]);

        $token = \Illuminate\Support\Str::random(20);
        $member->update(['telegram_link_token' => $token]);

        \Illuminate\Support\Facades\Log::info('connectTelegram AFTER UPDATE', ['member_id' => $member->id, 'saved_token' => $member->fresh()->telegram_link_token]);

        $botUsername = config('services.telegram.bot_username');
        $link = "https://t.me/{$botUsername}?start={$token}";

        return redirect()->away($link);
    }

    public function resendTelegramQr(Member $member, TelegramService $telegram)
    {
        $this->authorize('update', $member);

        if (! $member->telegram_chat_id) {
            return back()->withErrors(['telegram' => 'សមាជិកនេះមិនទាន់ភ្ជាប់ Telegram ទេ']);
        }

        if (! $member->qr_token) {
            $member->update(['qr_token' => \Illuminate\Support\Str::random(24)]);
        }

        $qrUrl = route('dashboard.members.qr', $member);

        $telegram->sendMessage(
            $member->telegram_chat_id,
            "នេះជា QR check-in របស់អ្នក: {$qrUrl}\n\n(បើក link នេះ screenshot ទុក ឬស្នើសុំ admin បោះពុម្ព)"
        );

        return back()->with('success', 'QR code ត្រូវបានផ្ញើតាម Telegram');
    }
}