<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaImageController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('create', MediaImage::class);

        // Defaults to 'image' so existing upload forms that don't send
        // media_kind at all keep working exactly as before.
        $mediaKind = $request->input('media_kind', 'image');

        $request->validate([
            'type' => 'required|in:hero_banner,gallery,trainer_photo,logo',
            'media_kind' => 'nullable|in:image,video',
            'category' => 'nullable|string|max:100',
            'caption' => 'nullable|string|max:255',

            // Single-image uploads (logo / hero_banner, or a video's poster).
            'image' => 'nullable|image|max:5120',

            // Batch upload — gallery can send several images at once.
            'images' => 'nullable|array|max:20',
            'images.*' => 'image|max:5120',

            // Video row: video file is required (mp4/mov/webm, up to 50MB).
            'video' => $mediaKind === 'video'
                ? 'required|file|mimetypes:video/mp4,video/quicktime,video/webm|max:51200'
                : 'nullable',
        ]);

        if ($mediaKind === 'video') {
            $videoPath = $request->file('video')->store('gym-videos', 'public');

            $imageUrl = null;
            if ($request->hasFile('image')) {
                $posterPath = $request->file('image')->store('gym-images', 'public');
                $imageUrl = Storage::url($posterPath);
            }

            MediaImage::create([
                'tenant_id' => auth()->user()->tenant_id,
                'type' => $request->type,
                'image_url' => $imageUrl,
                'display_order' => MediaImage::where('type', $request->type)->count(),
                'category' => $request->category,
                'caption' => $request->caption,
                'video_url' => Storage::url($videoPath),
                'media_kind' => 'video',
            ]);

            return back()->with('success', 'Video uploaded.');
        }

        // Image mode: accept either a batch (`images[]`) or a single `image`,
        // so existing single-image logo/hero_banner forms keep working.
        $files = $request->hasFile('images')
            ? $request->file('images')
            : ($request->hasFile('image') ? [$request->file('image')] : []);

        if (empty($files)) {
            return back()->withErrors(['image' => 'Please choose at least one image.']);
        }

        $order = MediaImage::where('type', $request->type)->count();
        foreach ($files as $file) {
            $path = $file->store('gym-images', 'public');

            MediaImage::create([
                'tenant_id' => auth()->user()->tenant_id,
                'type' => $request->type,
                'image_url' => Storage::url($path),
                'display_order' => $order++,
                'category' => $request->category,
                'caption' => $request->caption,
                'video_url' => null,
                'media_kind' => 'image',
            ]);
        }

        $count = count($files);

        return back()->with('success', $count > 1 ? "{$count} images uploaded." : 'Image uploaded.');
    }

    public function destroy(MediaImage $mediaImage)
    {
        $this->authorize('delete', $mediaImage);

        $mediaImage->delete();

        return back()->with('success', 'Image removed.');
    }
}