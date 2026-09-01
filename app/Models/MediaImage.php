<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\TenantScope;
use App\Support\MediaUrl;

class MediaImage extends Model
{
    protected $fillable = [
        'tenant_id',
        'type',
        'image_url',
        'display_order',
        'category',
        'caption',
        'video_url',
        'media_kind',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Column now stores a bare relative path (e.g. "gym-images/xyz.jpg")
     * instead of a full URL — resolved through MediaUrl so it follows
     * MEDIA_DISK. Legacy rows with a full URL already stored still work
     * (MediaUrl::resolve detects and passes them through unchanged).
     */
    public function getImageUrlAttribute(?string $value): ?string
    {
        return MediaUrl::resolve($value);
    }

    /**
     * Same resolution rule as image_url, for video-kind rows.
     */
    public function getVideoUrlAttribute(?string $value): ?string
    {
        return $value ? MediaUrl::resolve($value) : null;
    }
}