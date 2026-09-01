<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class InvitationController extends Controller
{
    public function show(Request $request, User $user)
    {
        abort_unless($request->hasValidSignature(), 403, 'Link នេះផុតកំណត់ ឬមិនត្រឹមត្រូវ');
        abort_if($user->invitation_accepted_at, 400, 'Invitation នេះត្រូវបាន accept រួចហើយ');

        return inertia('Auth/AcceptInvite', [
            'userEmail' => $user->email,
            'userId' => $user->id,
            'signedUrl' => $request->fullUrl(),
        ]);
    }

    public function store(Request $request, User $user)
    {
        abort_unless($request->hasValidSignature(), 403, 'Link នេះផុតកំណត់ ឬមិនត្រឹមត្រូវ');
        abort_if($user->invitation_accepted_at, 400, 'Invitation នេះត្រូវបាន accept រួចហើយ');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'name' => $validated['name'],
            'password' => Hash::make($validated['password']),
            'invitation_accepted_at' => now(),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard.overview')->with('success', 'សូមស្វាគមន៍មកកាន់ GymSite!');
    }
}