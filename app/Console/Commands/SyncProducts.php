<?php

namespace App\Console\Commands;

use App\Services\ProductImport\Importers\AstraImporter;
use App\Services\ProductImport\Importers\BuzzImporter;
use App\Services\ProductImport\Importers\DeichmannImporter;
use App\Services\ProductImport\Importers\DjakSportImporter;
use App\Services\ProductImport\Importers\IntersportImporter;
use App\Services\ProductImport\Importers\NSportImporter;
use App\Services\ProductImport\Importers\OfficeShoesImporter;
use App\Services\ProductImport\Importers\PlanikaImporter;
use App\Services\ProductImport\Importers\SportRealityImporter;
use App\Services\ProductImport\Importers\SportVisionImporter;
use App\Services\ProductImport\Importers\TheSpotImporter;
use App\Services\ProductImport\Importers\UnderArmourImporter;
use App\Services\ProductImport\ImportManager;
use Illuminate\Console\Command;

class SyncProducts extends Command
{
    protected $signature = 'products:sync {--store=} {--missing : Backfill missing product URLs without refreshing existing offers}';

    protected $description = 'Import sneakers only from allowed BiH store pages (0 limits mean full catalog)';

    public function handle(ImportManager $manager): int
    {
        $importers = collect([
            app(SportVisionImporter::class),
            app(BuzzImporter::class),
            app(SportRealityImporter::class),
            app(IntersportImporter::class),
            app(TheSpotImporter::class),
            app(NSportImporter::class),
            app(PlanikaImporter::class),
            app(UnderArmourImporter::class),
            app(DeichmannImporter::class),
            app(OfficeShoesImporter::class),
            app(AstraImporter::class),
            app(DjakSportImporter::class),
        ]);

        if ($slug = $this->option('store')) {
            $importers = $importers->filter(fn ($importer) => $importer->getStoreSlug() === $slug);
        }

        if ($importers->isEmpty()) {
            $this->error('Unknown or disabled store.');

            return self::FAILURE;
        }

        $failed = false;
        foreach ($importers as $importer) {
            $importer->onlyMissing((bool) $this->option('missing'));
            $this->info('Syncing '.$importer->getStoreName());
            $run = $manager->import($importer);
            $this->table(['Found', 'Masters', 'Offers', 'Updated', 'Failed'], [[
                $run->products_found, $run->products_created, $run->offers_created, $run->offers_updated, $run->failed_products,
            ]]);
            if ($run->errors || (! $run->products_found && ! $this->option('missing'))) {
                $failed = true;
                $this->warn('Incomplete import: '.count($run->errors ?? []).' errors. Full details in import_runs #'.$run->id);
                foreach (array_slice($run->errors ?? [], 0, 3) as $error) {
                    $this->warn($error);
                }
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
