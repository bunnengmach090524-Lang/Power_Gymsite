<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return inertia('Admin/Profile/Edit', [
            'userProfile' => auth()->user()->only('id', 'name', 'email', 'avatar'),
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'avatar' => 'nullable|image|max:3072',
        ]);

        if ($request->hasFile('avatar')) {
            MediaUrl::delete($user->getRawOriginal('avatar'));
            $validated['avatar'] = $request->file('avatar')->store('avatars', config('filesystems.media_disk', 'public'));
        } else {
            unset($validated['avatar']);
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('success', __('profile_update_success'));
    }
}