<?php

namespace App\Http\Controllers;

use App\Models\StaffProfile;
use App\Models\WebsiteSetting;
use App\Support\MediaUrl;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StaffSelfServiceController extends Controller
{
    public function index(Request $request)
    {
        $profile = $this->resolveProfile($request);

        abort_unless($profile, 403, 'No staff profile linked to this account.');

        $tenant = $profile->tenant;

        return Inertia::render('Staff/SelfService', [
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'position' => $profile->position,
                'phone' => $profile->phone,
                'photo_url' => $profile->photo_url,
            ],
            'payments' => $profile->salaryPayments()->orderByDesc('period_start')->get(),
            'attendances' => $profile->attendances()->orderByDesc('created_at')->limit(50)->get(),

            // ⚠️ FIX: previously `$tenant->only(...)` + a hand-built settings
            // array that was missing contact_phone/contact_email — that's why
            // the footer showed "Contact details coming soon." here but not
            // on Member pages. Now reusing the EXACT same query
            // MemberAccountController::show() uses, so Staff pages get a
            // byte-identical settings/tenant shape and the footer (contact
            // info, social links, logo) renders consistently everywhere.
            'tenant' => $tenant->only('id', 'name', 'slug'),
            'settings' => WebsiteSetting::where('tenant_id', $tenant->id)
                ->with('logoImage')
                ->first(),

            // staffViewer: only filled for trainer magic-link sessions (no
            // Laravel Auth::login(), so page.props.auth.user is null) — lets
            // SiteHeader render the staff dropdown instead of guest.
            'staffViewer' => $request->user() ? null : [
                'name' => $profile->name,
                'position' => $profile->position,
            ],
        ]);
    }

    /**
     * Update the staff member's own name, phone, and photo.
     *
     * `phone` lives on StaffProfile itself (staff-specific, independent of
     * payable_type). `name` and `photo` live on the underlying payable
     * account — User::avatar for payable_type=user, Trainer::photo_url
     * for payable_type=trainer — because that's where the rest of the app
     * (admin panel, class trainer listings, etc.) already reads them from.
     */
    public function update(Request $request)
    {
        $profile = $this->resolveProfile($request);

        abort_unless($profile, 403, 'No staff profile linked to this account.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        // phone: staff_profiles column, same for user- or trainer-backed staff.
        $profile->phone = $validated['phone'] ?? null;
        $profile->save();

        $payable = $profile->payable();
        abort_unless($payable, 500, 'Linked account not found.');

        $payable->name = $validated['name'];

        if ($request->hasFile('photo')) {
            $diskColumn = $profile->payable_type === 'trainer' ? 'photo_url' : 'avatar';
            $folder = $profile->payable_type === 'trainer' ? 'trainers' : 'avatars';

            MediaUrl::delete($payable->getRawOriginal($diskColumn));
            $payable->{$diskColumn} = $request->file('photo')
                ->store($folder, config('filesystems.media_disk', 'public'));
        }

        $payable->save();

        return back()->with('success', 'ព័ត៌មានត្រូវបានកែប្រែជោគជ័យ');
    }

    public function qr(Request $request)
    {
        $profile = $this->resolveProfile($request);

        abort_unless($profile, 403);

        if (! $profile->qr_token) {
            $profile->qr_token = Str::random(24);
            $profile->save();
        }

        return response(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->generate($profile->qr_token)
        )->header('Content-Type', 'image/svg+xml');
    }

    private function resolveProfile(Request $request): ?StaffProfile
{
    if ($request->user()) {
        return StaffProfile::where('payable_type', 'user')
            ->where('payable_id', $request->user()->id)
            ->first();
    }

    if ($trainerId = session('trainer_staff_id')) {
        Log::info('DEBUG trainerId=' . $trainerId . ' type=' . gettype($trainerId));

        $query = StaffProfile::withoutGlobalScopes()
            ->where('payable_type', 'trainer')
            ->where('payable_id', $trainerId);

        Log::info('DEBUG SQL: ' . $query->toSql());
        Log::info('DEBUG bindings: ' . json_encode($query->getBindings()));
        Log::info('DEBUG count: ' . $query->count());

        return $query->first();
    }

    return null;
}
}