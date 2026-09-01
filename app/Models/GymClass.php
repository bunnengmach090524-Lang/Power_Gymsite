<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\TenantScope;

class GymClass extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'tenant_id', 'trainer_id', 'name', 'description',
        'schedule_day', 'start_time', 'end_time', 'capacity', 'price', 'image_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class, 'class_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(ClassOrderItem::class, 'class_id');
    }

    public function getSpotsLeftAttribute(): int
    {
        return max(0, $this->capacity - $this->bookings()->count());
    }

    /**
     * Free classes (price null or 0) go through instant booking, same as
     * before this feature — no cart/checkout step. Paid classes (price > 0)
     * require the cart + payment flow (self-checkout or staff-assisted).
     */
    public function isPaid(): bool
    {
        return (float) ($this->price ?? 0) > 0;
    }

    /**
     * The column stores a bare relative path (e.g. "classes/xyz.jpg").
     * Resolve it to a full URL here, same pattern as Trainer::photo_url.
     */
    public function getImageUrlAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (\Illuminate\Support\Str::startsWith($value, ['http://', 'https://', '/'])) {
            return $value;
        }

        return asset('storage/' . $value);
    }
}