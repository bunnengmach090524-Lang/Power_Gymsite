<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function create(): \Inertia\Response
    {
        return inertia('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'gym_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $tenant = Tenant::create([
            'name' => $request->gym_name,
            'slug' => Str::slug($request->gym_name) . '-' . Str::random(4),
            'subscription_status' => 'trial',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'gym_admin',
        ]);

        event(new Registered($user));

        // No auto-login - the person must authenticate explicitly through
        // /login. This keeps login as the single gate that role/tenant
        // checks pass through, rather than trusting a session created
        // directly by the registration flow.
        return redirect()->route('login')->with('success', 'គណនីត្រូវបានបង្កើត! សូមចូលគណនី។');
    }
}