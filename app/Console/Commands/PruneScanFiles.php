<?php

namespace App\Console\Commands;

use App\Models\Scan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('scans:prune-files {--dry-run : List orphaned files without deleting them}')]
#[Description('Delete stored scan photos that no scan row references anymore')]
class PruneScanFiles extends Command
{
    /**
     * Scan photos live on the public disk under "scans/". When scan rows are
     * removed the files can be left behind, so this reclaims that space.
     * Profile pictures live under "profile-pictures/" and are never touched.
     */
    public function handle(): int
    {
        $disk = Storage::disk('public');
        $referenced = Scan::query()->pluck('image_path')->filter()->flip();
        $dryRun = (bool) $this->option('dry-run');
        $removed = 0;

        foreach ($disk->files('scans') as $file) {
            if ($referenced->has($file)) {
                continue;
            }

            if (! $dryRun) {
                $disk->delete($file);
            }

            $removed++;
        }

        $this->info(($dryRun ? 'Would delete' : 'Deleted')." {$removed} orphaned scan photo(s).");

        return self::SUCCESS;
    }
}
