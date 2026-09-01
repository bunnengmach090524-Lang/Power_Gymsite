<?php

namespace App\Providers;

use App\Listeners\LogMemberAuthActivity;
use App\Models\Member;
use App\Models\GymClass;
use App\Models\Promotion;
use App\Models\MediaImage;
use App\Models\Payment;
use App\Policies\MemberPolicy;
use App\Policies\GymClassPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\MediaImagePolicy;
use App\Policies\PaymentPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(GymClass::class, GymClassPolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
        Gate::policy(MediaImage::class, MediaImagePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);

        // ⚠️ FIX: Password::defaults() was never customized, so every
        // Rules\Password::defaults() call across the app (Register,
        // MemberRegistration, NewPasswordController, AcceptInvite) was
        // silently falling back to Laravel's bare minimum — just
        // 'required', no length check at all. A 1-character password
        // could pass registration. This sets a real floor everywhere
        // that rule is referenced, from one place.
        Password::defaults(function () {
            $rule = Password::min(8);

            // uncompromised() calls the haveibeenpwned k-anonymity API —
            // skip it outside production so local/dev work isn't blocked
            // by missing internet access.
            return $this->app->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });
    }
}