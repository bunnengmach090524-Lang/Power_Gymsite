<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Response;

/**
 * One-time step for a user who registered as an owner via Google (so had
 * no chance to type a gym name up front) to create their Tenant. Reached
 * right after successful OTP verification when the owner has no tenant_id.
 */
class TenantSetupController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = Auth::user();

        // Nothing to set up — bounce to where they belong.
        if ($user->role !== 'gym_admin' || $user->tenant_id) {
            return redirect()->route('dashboard.overview');
        }

        return inertia('Auth/SetupGym');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->role !== 'gym_admin' || $user->tenant_id) {
            return redirect()->route('dashboard.overview');
        }

        $validated = $request->validate([
            'gym_name' => 'required|string|max:255',
        ]);

        $tenant = Tenant::create([
            'name' => $validated['gym_name'],
            'slug' => Str::slug($validated['gym_name']) . '-' . Str::random(4),
            'subscription_status' => 'trial',
        ]);

        $user->forceFill(['tenant_id' => $tenant->id])->save();

        return redirect()->route('dashboard.overview');
    }
}