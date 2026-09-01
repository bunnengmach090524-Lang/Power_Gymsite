<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\MediaUrl;

// ⚠️ FIX: បន្ថែម 'google_id' និង 'email_verified_at' ទៅ $fillable —
// GoogleAuthController::callback() ធ្វើ User::create([..., 'google_id' => ...,
// 'email_verified_at' => now()]) នៅ ២ កន្លែង (member branch និង gym_admin
// register branch)។ ដោយសារ column ទាំង២នេះមិនធ្លាប់នៅក្នុង $fillable ពីមុន
// មក Laravel នឹង throw MassAssignmentException ឬ silently drop តម្លៃ
// ទាំងនេះ (អាស្រ័យលើ preventSilentlyDiscardingAttributes() setting) —
// មានន័យថា user ថ្មីអាចត្រូវបានបង្កើតដោយគ្មាន google_id/email_verified_at
// ត្រូវបានរក្សាទុកសោះ ទោះបីជា forceFill() នៅកន្លែងផ្សេងជួយ patch
// google_id វិញនៅពេល login លើកក្រោយក៏ដោយ។
#[Fillable(['tenant_id', 'role', 'name', 'email', 'password', 'avatar', 'is_owner', 'google_id', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class, 'user_id')->withoutGlobalScopes();
    }

    /**
     * Staff/admin avatar — same bare-path + MediaUrl pattern as Trainer
     * and Member. Fixes the previous hardcoded `/storage/${user.avatar}`
     * assumption in the Vue frontend.
     */
    public function getAvatarAttribute(?string $value): ?string
    {
        return MediaUrl::resolve($value);
    }
}