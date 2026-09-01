<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TeamInviteMail;
use App\Models\StaffProfile;
use App\Models\Trainer;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $profiles = StaffProfile::with('salaryPayments')
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name, // uses payable() accessor
                'photo_url' => $p->photo_url,
                'payable_type' => $p->payable_type,
                'invitation_accepted_at' => $p->payable_type === 'user' ? $p->payable()?->invitation_accepted_at : null,
                // email of the underlying trainer/user — used to
                // auto-fill the "Invite to Login" modal so admins don't
                // have to retype an email the trainer already has on file.
                'email' => $p->payable_type === 'trainer'
                    ? Trainer::withoutGlobalScopes()->where('tenant_id', $tenantId)->find($p->payable_id)?->email
                    : User::where('tenant_id', $tenantId)->find($p->payable_id)?->email,
                'position' => $p->position,
                'salary_type' => $p->salary_type,
                'base_salary' => $p->base_salary,
                'hourly_rate' => $p->hourly_rate,
                'commission_percent' => $p->commission_percent,
                'active' => $p->active,
                'hire_date' => $p->hire_date,
            ]);

        // People eligible to become a staff profile but don't have one yet —
        // Users with role staff/manager, and Trainers, minus whoever's
        // already linked via an existing StaffProfile.
        $linkedUserIds = StaffProfile::where('tenant_id', $tenantId)
            ->where('payable_type', 'user')->pluck('payable_id');
        $linkedTrainerIds = StaffProfile::where('tenant_id', $tenantId)
            ->where('payable_type', 'trainer')->pluck('payable_id');

        $availableUsers = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['staff', 'manager'])
            ->whereNotIn('id', $linkedUserIds)
            ->get(['id', 'name', 'email', 'role']);

        // FIX: Trainer has no automatic TenantScope — this query was leaking
        // every gym's trainers into every other gym's "available to hire" list.
        $availableTrainers = Trainer::where('tenant_id', $tenantId)
            ->whereNotIn('id', $linkedTrainerIds)
            ->get(['id', 'name', 'specialty']);

        return inertia('Admin/Staff/Index', [
            'profiles' => $profiles,
            'availableUsers' => $availableUsers,
            'availableTrainers' => $availableTrainers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payable_type' => ['required', 'in:user,trainer'],
            'payable_id' => ['required', 'integer'],
            'position' => ['required', 'string', 'max:255'],
            'salary_type' => ['required', 'in:fixed,hourly,commission,fixed_commission'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_source' => ['nullable', 'in:pt_session,class_booking,payment_referred'],
            'hire_date' => ['nullable', 'date'],
        ]);

        $tenantId = $request->user()->tenant_id;

        // Verify the chosen payable actually belongs to this tenant, and
        // isn't already linked to another StaffProfile.
        $exists = StaffProfile::where('tenant_id', $tenantId)
            ->where('payable_type', $validated['payable_type'])
            ->where('payable_id', $validated['payable_id'])
            ->exists();

        abort_if($exists, 422, 'Staff profile already exists for this person.');

        if ($validated['payable_type'] === 'user') {
            $owner = User::where('tenant_id', $tenantId)->findOrFail($validated['payable_id']);
            abort_unless(in_array($owner->role, ['staff', 'manager'], true), 422, 'Invalid user role.');
        } else {
            // FIX: Trainer is NOT covered by TenantScope — must filter by
            // tenant_id explicitly, otherwise any tenant can link any
            // other tenant's trainer into their own staff roster.
            Trainer::where('tenant_id', $tenantId)->findOrFail($validated['payable_id']);
        }

        StaffProfile::create([
            'tenant_id' => $tenantId,
            ...$validated,
        ]);

        return back()->with('success', 'Staff profile ត្រូវបានបង្កើត');
    }

    public function edit(Request $request, StaffProfile $staffProfile)
    {
        abort_unless($staffProfile->tenant_id === $request->user()->tenant_id, 403);

        return inertia('Admin/Staff/Edit', [
            'profile' => [
                'id' => $staffProfile->id,
                'name' => $staffProfile->name,
                'photo_url' => $staffProfile->photo_url,
                'payable_type' => $staffProfile->payable_type,
                'position' => $staffProfile->position,
                'salary_type' => $staffProfile->salary_type,
                'base_salary' => $staffProfile->base_salary,
                'hourly_rate' => $staffProfile->hourly_rate,
                'commission_percent' => $staffProfile->commission_percent,
                'commission_source' => $staffProfile->commission_source,
                'hire_date' => $staffProfile->hire_date?->toDateString(),
                'active' => $staffProfile->active,
                'telegram_connected' => (bool) $staffProfile->telegram_chat_id,
            ],
        ]);
    }

    public function update(Request $request, StaffProfile $staffProfile)
    {
        abort_unless($staffProfile->tenant_id === $request->user()->tenant_id, 403);

        $validated = $request->validate([
            'position' => ['required', 'string', 'max:255'],
            'salary_type' => ['required', 'in:fixed,hourly,commission,fixed_commission'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_source' => ['nullable', 'in:pt_session,class_booking,payment_referred'],
            'hire_date' => ['nullable', 'date'],
            'active' => ['boolean'],
        ]);

        $staffProfile->update($validated);

        return back()->with('success', 'Staff profile ត្រូវបានកែប្រែ');
    }

    public function destroy(Request $request, StaffProfile $staffProfile)
    {
        abort_unless($staffProfile->tenant_id === $request->user()->tenant_id, 403);

        $staffProfile->delete();

        return back()->with('success', 'Staff profile ត្រូវបានលុប');
    }

    public function qrCode(Request $request, StaffProfile $staffProfile)
    {
        abort_unless($staffProfile->tenant_id === $request->user()->tenant_id, 403);

        if (! $staffProfile->qr_token) {
            $staffProfile->qr_token = Str::random(24);
            $staffProfile->save();
        }

        return response(
            QrCode::size(300)->generate($staffProfile->qr_token)
        )->header('Content-Type', 'image/svg+xml')
        ->header('Content-Disposition', 'inline; filename="staff-' . $staffProfile->id . '-qr.svg"');
    }

    public function connectTelegram(Request $request, StaffProfile $staffProfile)
    {
        abort_unless($staffProfile->tenant_id === $request->user()->tenant_id, 403);

        $token = Str::random(20);
        $staffProfile->update(['telegram_link_token' => $token]);

        $botUsername = config('services.telegram.bot_username');
        $link = "https://t.me/{$botUsername}?start={$token}";

        return redirect()->away($link)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function resendTelegramQr(Request $request, StaffProfile $staffProfile, TelegramService $telegram)
    {
        abort_unless($staffProfile->tenant_id === $request->user()->tenant_id, 403);

        if (! $staffProfile->telegram_chat_id) {
            return back()->withErrors(['telegram' => 'This staff member has not connected Telegram yet.']);
        }

        if (! $staffProfile->qr_token) {
            $staffProfile->update(['qr_token' => Str::random(24)]);
        }

        $qrUrl = route('dashboard.staff.qr', $staffProfile);

        $telegram->sendMessage(
            $staffProfile->telegram_chat_id,
            "Here is your check-in QR code link: {$qrUrl}\n\n(Open this link and screenshot it, or ask your admin to print it.)"
        );

        return back()->with('success', 'QR code link sent via Telegram.');
    }

    public function selfServiceQr(Request $request, StaffProfile $staffProfile)
    {
        abort_unless($staffProfile->tenant_id === $request->user()->tenant_id, 403);
        abort_unless($staffProfile->payable_type === 'trainer', 422, 'Only trainer-type staff use this link.');

        // FIX: explicit tenant filter — $staffProfile is already tenant-checked
        // above, but keep the Trainer lookup consistent/defensive.
        $trainer = Trainer::where('tenant_id', $staffProfile->tenant_id)
            ->findOrFail($staffProfile->payable_id);

        if (! $trainer->self_service_token) {
            $trainer->self_service_token = Str::random(48);
            $trainer->save();
        }

        $url = route('trainer.self.login', $trainer->self_service_token);

        return response(
            QrCode::size(300)->generate($url)
        )->header('Content-Type', 'image/svg+xml');
    }

    public function inviteToLogin(Request $request, StaffProfile $staffProfile)
    {
        abort_unless($staffProfile->tenant_id === $request->user()->tenant_id, 403);
        abort_unless($staffProfile->payable_type === 'trainer', 422, 'Only trainer-type staff need this.');

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
        ]);

        // FIX: explicit tenant filter on the Trainer lookup.
        $trainer = Trainer::where('tenant_id', $staffProfile->tenant_id)
            ->findOrFail($staffProfile->payable_id);

        $user = User::create([
            'tenant_id' => $staffProfile->tenant_id,
            'role' => 'trainer',
            'name' => $trainer->name,
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(32)), // set at invite acceptance
        ]);

        // Re-point this StaffProfile to the new User login account.
        // The old Trainer record stays untouched (still used for classes, etc.)
        $staffProfile->update([
            'payable_type' => 'user',
            'payable_id' => $user->id,
        ]);

        Mail::to($user->email)->send(new TeamInviteMail(
            $user,
            $request->user()->name,
            $request->user()->tenant->name ?? 'GymSite'
        ));

        return back()->with('success', 'ការអញ្ជើញត្រូវបានផ្ញើទៅ ' . $user->email);
    }
}