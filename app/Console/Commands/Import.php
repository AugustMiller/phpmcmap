<?php

namespace App\Console\Commands;

use App\Helpers\Coordinates;
use App\Models\DbRegion;
use App\Models\Region;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use xPaw\SourceQuery\SourceQuery;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import {--force}';

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
        $updated = 0;

        $this->call('app:rcon', [
            'str' => 'say Starting render!',
        ]);

        $this->info("Found {$count} region files...");

        foreach ($files as $file) {
            $vec = Coordinates::fromFilename($file);
            $fileMod = Carbon::createFromTimestamp($fs->lastModified($file));

            $dbRegion = DbRegion::firstOrCreate([
                'x' => $vec->x,
                'z' => $vec->z,
            ]);

            $needsUpdate = $this->option('force') || $dbRegion->last_modified === null || $fileMod->gt($dbRegion->last_modified);

            if (!$needsUpdate) {
                $this->info("Region [{$dbRegion->x}, {$dbRegion->z}] has not been modified.");

                continue;
            }

            $updated++;

            $this->comment("Region [{$dbRegion->x}, {$dbRegion->z}] needs an update.");

            if ($dbRegion->last_modified === null) {
                $this->line("  -> There is no history in the database.");
            } else {
                $this->line("  -> The database was last updated at {$dbRegion->last_modified->toAtomString()}.");
            }

            $this->line("  -> The region file was last modified at {$fileMod->toAtomString()}");

            $timeStart = microtime(true);

            $region = new Region($vec->x, $vec->z);
            $dbRegion->refreshFrom($region, $this->option('force'));

            // Update the timestamp:
            $dbRegion->last_modified = $fileMod;
            $dbRegion->save();

            $duration = microtime(true) - $timeStart;
            $this->info("  -> Refreshed in {$duration}s!");
        }

        $this->info(sprintf('Finished updating %d of %d regions.', $updated, $count));

        $this->call('app:rcon', [
            'str' => 'say ' . sprintf('Finished updating %d of %d regions.', $updated, $count),
        ]);

        return 0;
    }
}
