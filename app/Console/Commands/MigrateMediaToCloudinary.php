<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateMediaToCloudinary extends Command
{
    protected $signature = 'media:migrate-to-cloudinary {--dry-run : List files without uploading}';

    protected $description = 'Upload every file currently on the "public" disk to the "cloudinary" disk, stripping extensions to avoid Cloudinary\'s double-extension URL issue.';

    public function handle(): int
    {
        $files = Storage::disk('public')->allFiles();

        if (empty($files)) {
            $this->info('No files found on the public disk. Nothing to do.');
            return self::SUCCESS;
        }

        $this->info(count($files).' file(s) found on local storage.');

        $dryRun = $this->option('dry-run');
        $success = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $path) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $cloudinaryPath = $extension !== ''
                ? Str::beforeLast($path, '.'.$extension)
                : $path;

            if ($dryRun) {
                $this->line("\nWould upload: {$path}  ->  cloudinary:{$cloudinaryPath}");
                $bar->advance();
                continue;
            }

            try {
                $contents = Storage::disk('public')->get($path);
                Storage::disk('cloudinary')->put($cloudinaryPath, $contents);
                $success++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("\nFailed: {$path} — {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info('Dry run complete. Re-run without --dry-run to actually upload.');
        } else {
            $this->info("Done. Uploaded: {$success}, Failed: {$failed}");
        }

        return self::SUCCESS;
    }
}   