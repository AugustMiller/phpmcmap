<?php

namespace App\Console\Commands;

use App\Helpers\Coordinates;
use App\Models\Chunk;
use App\Models\DbRegion;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports data from new and updated region files.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fs = Storage::disk('region');
        $files = $fs->files();
        $count = count($files);

        $this->info("Found {$count} region files...");

        foreach ($files as $file) {
            $vec = Coordinates::fromFilename($file);
            $fileMod = Carbon::createFromTimestamp($fs->lastModified($file));

            $dbRegion = DbRegion::firstOrCreate([
                'x' => $vec->x,
                'z' => $vec->z,
            ]);

            $needsUpdate = $dbRegion->last_modified === null || $fileMod->gt($dbRegion->last_modified);

            if (!$needsUpdate) {
                $this->info("Region [{$dbRegion->x}, {$dbRegion->z}] has not been modified.");

                continue;
            }

            $this->info("Region [{$dbRegion->x}, {$dbRegion->z}] needs an update.");

            if ($dbRegion->last_modified === null) {
                $this->info("  -> There is no history in the database.");
            } else {
                $this->info("  -> The database was last updated at {$dbRegion->last_modified->toAtomString()}.");
            }

            $this->info("  -> The region file was last modified at {$fileMod->toAtomString()}");

            $region = new Region($vec->x, $vec->z);
            $dbRegion->refreshFrom($region);

            // Update the timestamp:
            $dbRegion->last_modified = $fileMod;
            $dbRegion->save();
        }

        return 0;
    }
}
