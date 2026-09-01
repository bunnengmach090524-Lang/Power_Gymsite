<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebsiteSettingsController extends Controller
{
    public function edit()
    {
        $tenant = auth()->user()->tenant;

        return inertia('Admin/WebsiteEditor', [
            'settings' => $tenant->websiteSetting,
            'tenant' => $tenant->only(['address', 'latitude', 'longitude']),
            'logoImages' => $tenant->mediaImages()->where('type', 'logo')->orderBy('display_order')->get(),
            'heroImages' => $tenant->mediaImages()->where('type', 'hero_banner')->orderBy('display_order')->get(),
            'galleryImages' => $tenant->mediaImages()->where('type', 'gallery')->orderBy('display_order')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'tagline' => 'nullable|string|max:255',
            'about_text' => 'nullable|string',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'logo_image_id' => 'nullable|exists:media_images,id',
            'hero_banner_image_id' => 'nullable|exists:media_images,id',
            'random_hero_banner' => 'nullable|boolean', 
            'virtual_tour_url' => 'nullable|url|max:255',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:255',
            'social_links.instagram' => 'nullable|url|max:255',
            'social_links.tiktok' => 'nullable|url|max:255',
            'social_links.telegram' => 'nullable|url|max:255',
            'social_links.youtube' => 'nullable|url|max:255',
            'social_links.whatsapp' => 'nullable|url|max:255',
            'social_links.twitter' => 'nullable|url|max:255',
            'social_links.linkedin' => 'nullable|url|max:255',
            // Location fields live on the Tenant model, not WebsiteSetting —
            // validated here alongside the rest since they're one form on the frontend.
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $tenant = auth()->user()->tenant;

        $tenant->update([
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        $websiteSettingFields = collect($validated)
            ->except(['address', 'latitude', 'longitude'])
            ->toArray();

        $tenant->websiteSetting()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            $websiteSettingFields
        );

        return back()->with('success', 'Website updated.');
    }
}