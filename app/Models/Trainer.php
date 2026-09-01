<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\TenantScope;
use Illuminate\Support\Str;

class Trainer extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'email', 'bio', 'photo_url', 'specialty', 'shift_start_time',
        'qr_token', 'telegram_chat_id', 'telegram_link_token',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(GymClass::class, 'trainer_id');
    }

    /**
     * The column stores a bare relative path (e.g. "trainers/xyz.jpg").
     * Resolve it to a full URL here, same as MediaImage does, so every
     * consumer (admin panel, public site, API) gets a ready-to-use URL
     * instead of each having to know the storage convention.
     */
    public function getPhotoUrlAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '/'])) {
            return $value;
        }

        return asset('storage/' . $value);
    }
}