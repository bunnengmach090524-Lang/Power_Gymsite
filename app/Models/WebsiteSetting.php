<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteSetting extends Model
{
    protected $fillable = [
        'tenant_id', 'template_id', 'primary_color', 'secondary_color',
        'tagline', 'about_text', 'hero_banner_image_id', 'logo_image_id',
        'contact_email', 'contact_phone', 'social_links', 'virtual_tour_url',
        'random_hero_banner', 
    ];

    protected $casts = [
        'social_links' => 'array',
        'random_hero_banner' => 'boolean', 
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function heroBannerImage(): BelongsTo
    {
        return $this->belongsTo(MediaImage::class, 'hero_banner_image_id');
    }

    public function logoImage(): BelongsTo
    {
        return $this->belongsTo(MediaImage::class, 'logo_image_id');
    }
}