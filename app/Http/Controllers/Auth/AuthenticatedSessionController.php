<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function create(): \Inertia\Response
    {
        return inertia('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        return $this->redirectAfterLogin(Auth::user());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Central redirect decision after a successful password login.
     * Mirrors GoogleAuthController::redirectAfterLogin() so both auth
     * paths (Google + password) send each role to the same place.
     */
    private function redirectAfterLogin(User $user): RedirectResponse
    {
        if ($user->role === 'member') {
            $tenantSlug = $user->member?->tenant?->slug;

            if (! $tenantSlug) {
                Auth::logout();

                return redirect()->route('login')
                    ->withErrors(['email' => 'គណនីនេះមិនទាន់ភ្ជាប់ជាមួយ gym ណាមួយទេ។']);
            }

            return redirect()->intended(route('member.account', $tenantSlug));
        }

        if ($user->role === 'gym_admin' && ! $user->tenant_id) {
            return redirect()->route('setup-gym');
        }

        // staff and gym_admin (with tenant) both land on the dashboard;
        // the dashboard UI itself is responsible for hiding edit/delete
        // controls from staff (handled in a later step).
        return redirect()->intended(route('dashboard.overview'));
    }
}