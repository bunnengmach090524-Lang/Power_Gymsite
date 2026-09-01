<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TeamInviteMail;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $members = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['gym_admin', 'staff'])
            ->orderByRaw("is_owner desc")
            ->orderByRaw("role = 'gym_admin' desc")
            ->latest()
            ->get(['id', 'name', 'email', 'role', 'avatar', 'is_owner', 'invitation_accepted_at', 'created_at']);

        return inertia('Admin/Team/Index', ['members' => $members]);
    }

    public function invite(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', 'in:gym_admin,staff'],
        ]);

        $user = User::create([
            'tenant_id' => $request->user()->tenant_id,
            'role' => $validated['role'],
            'name' => Str::before($validated['email'], '@'),
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(32)),
        ]);

        Mail::to($user->email)->send(new TeamInviteMail(
            $user,
            $request->user()->name,
            $request->user()->tenant->name ?? 'GymSite'
        ));

        return back()->with('success', 'ការអញ្ជើញត្រូវបានផ្ញើទៅ ' . $user->email);
    }

    public function resend(Request $request, User $member)
    {
        abort_unless($member->tenant_id === $request->user()->tenant_id, 403);
        abort_if($member->invitation_accepted_at, 400, 'Member ត្រូវបាន accept រួចហើយ');

        Mail::to($member->email)->send(new TeamInviteMail(
            $member,
            $request->user()->name,
            $request->user()->tenant->name ?? 'GymSite'
        ));

        return back()->with('success', 'ការអញ្ជើញត្រូវបានផ្ញើឡើងវិញ');
    }

    public function updateRole(Request $request, User $member)
    {
        abort_unless($member->tenant_id === $request->user()->tenant_id, 403);

        // Owner មិនអាចត្រូវ demote ដោយអ្នកណាបានទេ (រួមទាំងខ្លួនឯង)
        if ($member->is_owner) {
            return back()->withErrors(['role' => 'មិនអាចប្តូរ role របស់ Owner បានទេ']);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:gym_admin,staff'],
        ]);

        $member->update(['role' => $validated['role']]);

        return back()->with('success', 'Role ត្រូវបានកែប្រែ');
    }

    public function destroy(Request $request, User $member)
    {
        abort_unless($member->tenant_id === $request->user()->tenant_id, 403);
        abort_if($member->id === $request->user()->id, 400, 'មិនអាចលុបខ្លួនឯងបានទេ');

        // Owner មិនអាចត្រូវ remove ដោយអ្នកណាបានទេ
        if ($member->is_owner) {
            return back()->withErrors(['member' => 'មិនអាចដក Owner ចេញបានទេ']);
        }

        $member->delete();

        return back()->with('success', 'Member ត្រូវបានដកចេញ');
    }

}