<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffProfile extends Model
{
    protected $fillable = [
        'tenant_id', 'payable_type', 'payable_id', 'position', 'phone',
        'salary_type', 'base_salary', 'hourly_rate', 'commission_percent',
        'commission_source', 'hire_date', 'active', 'qr_token',
        'telegram_chat_id', 'telegram_link_token',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'active' => 'boolean',
            'base_salary' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'commission_percent' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class);
    }

    /**
     * Resolve the actual payee (User or Trainer) manually, since we
     * deliberately use a plain type+id pair instead of Eloquent's
     * polymorphic relations — see migration comment for why.
     */
    public function payable(): User|Trainer|null
    {
        return match ($this->payable_type) {
            'user' => User::find($this->payable_id),
            'trainer' => Trainer::find($this->payable_id),
            default => null,
        };
    }

    /** Display name regardless of underlying payee type. */
    public function getNameAttribute(): string
    {
        return $this->payable()?->name ?? 'Unknown';
    }

    public function getPhotoUrlAttribute(): ?string
    {
        $person = $this->payable();

        return $this->payable_type === 'trainer'
            ? $person?->photo_url
            : $person?->avatar;
    }
}