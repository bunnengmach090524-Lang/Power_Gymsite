<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\GoogleLoginOtpMail;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google.
     *
     * Optional `?tenant=slug` marks this as a member login/register attempt
     * scoped to that gym. Optional `?intent=register` marks this as a brand
     * new gym-owner registration (from the main Register page's Google
     * button) rather than a login attempt. Optional `?redirect_to=path`
     * lets a caller (e.g. the pricing page) send the user somewhere other
     * than the default post-login destination once they're verified — we
     * stash it as Laravel's own `url.intended` session key so the existing
     * `redirect()->intended(...)` calls in redirectAfterLogin() pick it up
     * automatically without any extra plumbing. All three are carried
     * through Google via session and read back in callback().
     */
    public function redirect(Request $request): RedirectResponse
    {
        if ($request->filled('tenant')) {
            $request->session()->put('google_auth_tenant_slug', $request->string('tenant'));
        } else {
            $request->session()->forget('google_auth_tenant_slug');
        }

        if ($request->filled('redirect_to')) {
            $request->session()->put('url.intended', $request->string('redirect_to'));
        }

        if ($request->query('intent') === 'register') {
            $request->session()->put('google_auth_intent', 'register');
        } else {
            $request->session()->forget('google_auth_intent');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        // ជៀសវាង duplicate request (browser preload/prefetch) ដែលហៅ callback URL
        // ២ដងជាមួយ state ដូចគ្នា — បើ user ត្រូវបាន login រួចហើយពី request មុន សូម redirect ភ្លាមៗ
        if (Auth::check()) {
            $user = Auth::user();
            return $this->redirectAfterLogin($user);
        }

        $googleUser = Socialite::driver('google')->user();
        $tenantSlug = $request->session()->pull('google_auth_tenant_slug');
        $intent = $request->session()->pull('google_auth_intent');

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            if (! $user->google_id) {
                $user->forceFill(['google_id' => $googleUser->getId()])->save();
            }

            // ជំនួសនឹង Auth::login ភ្លាមៗ — ផ្ញើ OTP code ទៅ email ជាមុនសិន
            //
            // ⚠️ FIX: មិនត្រូវ pass $tenantSlug ពី session ទទេ ៗ ទៅ
            // startOtpVerification ទេ ពេល user ជា existing member —
            // បើ member login តាមទំព័រ /login ទូទៅ (គ្មាន ?tenant=slug)
            // $tenantSlug នឹងជា null ដែលធ្វើឲ្យ redirectAfterLogin()
            // គ្មានផ្លូវណាមួយសម្រាប់ដឹងថា member នេះជារបស់ gym ណា។
            // ដូច្នេះ fallback ទៅ $user->tenant_id (column ផ្ទាល់លើ User,
            // ត្រូវបានកំណត់ជានិច្ចនៅពេល create() មិនថាតាមផ្លូវណា) ជាមុនគេ។
            $resolvedTenantSlug = $tenantSlug ?? $user->tenant?->slug;

            return $this->startOtpVerification($request, $user, $resolvedTenantSlug);
        }

        if ($tenantSlug) {
            // Existing gym, new member joining via Google.
            $tenant = Tenant::where('slug', $tenantSlug)->firstOrFail();

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'Member',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => null,
                'role' => 'member',
                'email_verified_at' => now(),
            ]);

            Member::firstOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $googleUser->getEmail()],
                ['user_id' => $user->id, 'name' => $user->name, 'joined_date' => now()]
            )->forceFill(['user_id' => $user->id])->save();

            return $this->startOtpVerification($request, $user, $tenant->slug);
        }

        if ($intent === 'register') {
            // Brand new gym owner registering via Google from the main
            // Register page. No tenant yet — created afterwards on the
            // setup-gym step once OTP is verified. Role is 'gym_admin' to
            // match the role middleware used throughout dashboard.* routes.
            $user = User::create([
                'tenant_id' => null,
                'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'Owner',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => null,
                'role' => 'gym_admin',
                'email_verified_at' => now(),
            ]);

            return $this->startOtpVerification($request, $user, null);
        }

        // Plain login attempt (from the Login page) with no matching account.
        return redirect()->route('login')
            ->withErrors(['email' => 'រកមិនឃើញគណនីដែលភ្ជាប់ជាមួយ Google account នេះទេ។']);
    }

    /**
     * Generate a 6-digit code, email it to the user, and stash the pending
     * user id in session while we wait for them to enter it on the verify page.
     */
    private function startOtpVerification(Request $request, User $user, ?string $tenantSlugForIntended): RedirectResponse
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'otp_code' => $code,
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        Mail::to($user->email)->send(new GoogleLoginOtpMail($code));

        $request->session()->put('google_otp_user_id', $user->id);
        $request->session()->put('google_otp_tenant_slug', $tenantSlugForIntended);

        return redirect()->route('google.verify');
    }

    /**
     * Show the "enter your code" page. Requires an in-progress OTP session
     * from startOtpVerification() — otherwise there's nothing to verify.
     */
    public function verifyPage(Request $request): Response|RedirectResponse
    {
        $userId = $request->session()->get('google_otp_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget(['google_otp_user_id', 'google_otp_tenant_slug']);
            return redirect()->route('login');
        }

        return inertia('Auth/VerifyGoogleCode', [
            'maskedEmail' => $this->maskEmail($user->email),
        ]);
    }

    /**
     * Check the submitted code, log the user in on success, and clean up
     * the OTP fields so the code can't be reused.
     */
    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $userId = $request->session()->get('google_otp_user_id');

        if (! $userId) {
            return redirect()->route('login')
                ->withErrors(['code' => 'សម័យផ្ទៀងផ្ទាត់បានផុតកំណត់។ សូម login ជាមួយ Google ម្តងទៀត។']);
        }

        $user = User::find($userId);

        if (! $user || ! $user->otp_code || ! $user->otp_expires_at || $user->otp_expires_at->isPast()) {
            $request->session()->forget(['google_otp_user_id', 'google_otp_tenant_slug']);

            return redirect()->route('login')
                ->withErrors(['code' => 'លេខកូដផុតកំណត់ហើយ។ សូម login ជាមួយ Google ម្តងទៀត។']);
        }

        if (! hash_equals($user->otp_code, $validated['code'])) {
            return back()->withErrors(['code' => 'លេខកូដមិនត្រឹមត្រូវទេ។ សូមព្យាយាមម្តងទៀត។']);
        }

        $tenantSlug = $request->session()->pull('google_otp_tenant_slug');
        $request->session()->forget('google_otp_user_id');

        $user->forceFill(['otp_code' => null, 'otp_expires_at' => null])->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->redirectAfterLogin($user, $tenantSlug);
    }

    /**
     * Re-send a fresh code to the same pending user (e.g. if the first one
     * expired or never arrived).
     */
    public function resend(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('google_otp_user_id');
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            return redirect()->route('login');
        }

        $tenantSlug = $request->session()->get('google_otp_tenant_slug');

        return $this->startOtpVerification($request, $user, $tenantSlug)
            ->with('success', 'បានផ្ញើលេខកូដថ្មីទៅ email របស់អ្នករួចហើយ។');
    }

    /**
     * Central place to decide where a logged-in user should land: an owner
     * who hasn't created a gym yet goes to the setup step, members go to
     * their tenant's member area, everyone else goes to the dashboard.
     *
     * redirect()->intended(...) will use session('url.intended') if it was
     * set (either by auth middleware bouncing a guest off a protected route,
     * or by us manually in redirect() above via ?redirect_to=), falling back
     * to the given default route otherwise.
     */
    private function redirectAfterLogin(User $user, ?string $tenantSlug = null): RedirectResponse
    {
        if ($user->role === 'gym_admin' && ! $user->tenant_id) {
            return redirect()->route('setup-gym');
        }

        if ($user->role === 'member') {
            // ⚠️ FIX: លំដាប់ fallback ត្រូវបានផ្លាស់ប្តូរ —
            // $user->tenant_id/tenant គឺជាប្រភពដែលអាចទុកចិត្តបានបំផុត
            // ព្រោះវាត្រូវបានកំណត់ដោយផ្ទាល់លើ User record ជានិច្ចនៅពេល
            // create() (មិនថាតាមផ្លូវ callback() ណាមួយ) ។ វាមិនអាស្រ័យលើ
            // $user->member relation (ដែលអាចជា null បើ Member record
            // មិនទាន់ត្រូវបានបង្កើត ឬ user_id មិនទាន់ភ្ជាប់ត្រឹមត្រូវ)
            // ហើយក៏មិនអាស្រ័យលើ session variable ដែលងាយបាត់បង់ បើ member
            // login តាមទំព័រ /login ទូទៅដោយគ្មាន ?tenant=slug ។
            $slug = $user->tenant?->slug
                ?? $user->member?->tenant?->slug
                ?? $tenantSlug;

            if (! $slug) {
                // Data inconsistency ពិតប្រាកដ — member account គ្មាន
                // tenant ភ្ជាប់ទាល់តែសោះ។ មិនគួរ redirect ទៅ route ដែល
                // តម្រូវ slug ដោយគ្មានតម្លៃទេ ព្រោះនឹង throw error ដដែល។
                abort(500, 'Member account is missing a tenant association.');
            }

            return redirect()->intended(route('member.account', $slug));
        }

        return redirect()->intended(route('dashboard.overview'));
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        $visible = mb_substr($name, 0, 2);

        return $visible . str_repeat('*', max(mb_strlen($name) - 2, 1)) . '@' . $domain;
    }
}