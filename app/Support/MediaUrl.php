<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Central place for resolving/deleting media files (avatars, trainer
 * photos, gallery images). Every model stores a BARE relative path
 * (e.g. "avatars/xyz.jpg") in the database — never a full URL — so that
 * switching MEDIA_DISK from "public" (local) to "r2" (Cloudflare R2)
 * in .env is the ONLY change needed to move storage to the cloud.
 *
 * Legacy rows created before this refactor may still contain a full URL
 * (old MemberAccountController/MediaImageController behavior) — resolve()
 * detects that and returns it as-is instead of double-resolving it.
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

        return static::disk()->url($path);
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

        static::disk()->delete($rawPath);
    }
}