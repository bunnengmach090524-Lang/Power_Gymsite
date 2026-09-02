<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Central place for resolving/deleting media files (avatars, trainer
 * photos, gallery images). Every model stores a BARE relative path
 * (e.g. "avatars/xyz.jpg") in the database — never a full URL — so that
 * switching MEDIA_DISK from "public" (local) to "r2"/"cloudinary" in
 * .env is the ONLY change needed to move storage to the cloud.
 *
 * Legacy rows created before this refactor may still contain a full URL
 * (old MemberAccountController/MediaImageController behavior) — resolve()
 * detects that and returns it as-is instead of double-resolving it.
 *
 * Cloudinary quirk: uploading with a file extension in the path (e.g.
 * "avatars/xyz.jpg") produces a doubled-extension URL
 * (".../xyz.jpg.jpg") because Cloudinary auto-detects and appends the
 * real format on delivery. To avoid touching every DB row, we strip the
 * extension only at URL-build time when the active disk is "cloudinary"
 * — uploads must also be stored WITHOUT the extension on that disk
 * (see the media-migration command).
 */
class MediaUrl
{
    public static function disk(): FilesystemAdapter
    {
        return Storage::disk(config('filesystems.media_disk', 'public'));
    }

    public static function resolve(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Legacy full URL already stored (old rows) — pass through unchanged.
        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        $diskName = config('filesystems.media_disk', 'public');
        $urlPath = $diskName === 'cloudinary'
            ? static::stripExtension($path)
            : $path;

        return static::disk()->url($urlPath);
    }

    /**
     * Delete a stored file by its RAW path (never pass the resolved URL —
     * use $model->getRawOriginal('column') when calling this).
     */
    public static function delete(?string $rawPath): void
    {
        if (! $rawPath) {
            return;
        }

        // Legacy full URL rows — we don't know which disk they came from,
        // so skip deletion rather than risk an error. New rows are always
        // bare paths and delete cleanly.
        if (Str::startsWith($rawPath, ['http://', 'https://'])) {
            return;
        }

        $rawPath = ltrim(str_replace('/storage/', '', $rawPath), '/');

        $diskName = config('filesystems.media_disk', 'public');
        if ($diskName === 'cloudinary') {
            $rawPath = static::stripExtension($rawPath);
        }

        static::disk()->delete($rawPath);
    }

    private static function stripExtension(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension !== ''
            ? Str::beforeLast($path, '.'.$extension)
            : $path;
    }
}