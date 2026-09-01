<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Response;

class MemberRegistrationController extends Controller
{
    /**
     * IdentifyTenant middleware already resolved + shared the tenant as
     * a request attribute ('tenant'), so we just read it back here rather
     * than re-querying by slug.
     */
    private function resolveTenant(Request $request): Tenant
    {
        return $request->attributes->get('tenant')
            ?? Tenant::where('slug', $request->route('slug'))->firstOrFail();
    }

    public function create(Request $request): Response
    {
        $tenant = $this->resolveTenant($request);

        return inertia('Client/MemberRegister', [
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->resolveTenant($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // ⚠️ FIX: User::create() និង Member::firstOrCreate() ត្រូវរត់ក្នុង
        // DB transaction តែមួយ។ មុននេះ ២ជំហាននេះរត់ដាច់ដោយឡែកពីគ្នា —
        // បើ Member::firstOrCreate() (ឬសូម្បីតែ event(Registered) listener
        // ណាមួយ) throw exception ណាមួយ, User row ដែលបានបង្កើតរួចហើយនៅ
        // ជំហានទី ១ នៅតែ persist ជា "orphan": User មាន role=member ប៉ុន្តែ
        // គ្មាន Member ត្រូវគ្នា — នេះជា root cause ដែលធ្វើឲ្យ member ថ្មីខ្លះ
        // ជួប 404 លើ /account ជានិច្ច ព្រោះ resolveMember() រកមិនឃើញ Member
        // row ណាមួយសម្រាប់ពួកគេទាល់តែសោះ។ ការ wrap ក្នុង transaction
        // ធានាថា បើផ្នែកណាមួយបរាជ័យ — User row ក៏ត្រូវ rollback ដែរ, មិន
        // ទុកចោល orphan ថ្មីទៀតឡើយ។
        [$user, $member] = DB::transaction(function () use ($validated, $tenant) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'member',
                // ⚠️ FIX: កំណត់ tenant_id ត្រង់នេះឲ្យដូចគ្នានឹង
                // GoogleAuthController::callback() (ផ្លូវ "new member
                // joining via Google") ជំនួសឲ្យ null ។ HandleInertiaRequests
                // និង GoogleAuthController::redirectAfterLogin() ទាំងពីរ
                // ចាត់ទុក User.tenant_id ជា source of truth ចម្បងសម្រាប់
                // member — ទុកវាជា null ធ្វើឲ្យ sidebarCounts/notifications/
                // todayStats ទាំងអស់ return ទទេសម្រាប់ member ដែលចុះឈ្មោះ
                // តាមទម្រង់នេះ ទោះបីជា Member row ត្រូវបានបង្កើតត្រឹមត្រូវក៏ដោយ។
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
            ]);

            $member = Member::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $validated['email']],
                [
                    'name' => $validated['name'],
                    'phone' => $validated['phone'] ?? null,
                    'joined_date' => now(),
                ]
            );
            $member->forceFill(['user_id' => $user->id])->save();

            return [$user, $member];
        });

        // event() ត្រូវនៅក្រៅ transaction ដោយចេតនា — listeners (ឧ. ផ្ញើ
        // welcome email) មិនគួរធ្វើឲ្យ User/Member creation rollback ទេ
        // បើ mail server មានបញ្ហា (ជាទូទៅកើតញឹកញាប់ក្នុង local dev)។
        // User + Member ត្រូវបានធានាថាមានស្រាប់ត្រឹមត្រូវរួចហើយនៅចំណុចនេះ។
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            report($e);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('member.account', $tenant->slug);
    }
}