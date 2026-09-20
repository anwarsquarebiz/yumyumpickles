<?php

namespace App\Console\Commands\Catalog;

use App\Services\Catalog\ShopifyCatalogImporter;
use Illuminate\Console\Command;

class ImportShopifyCatalogCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'catalog:import-shopify
                            {url=https://worldonlinemeds.com : Public Shopify storefront URL}
                            {--dump= : Import from a local catalog.json dump instead of fetching live}
                            {--refresh-images : Re-download product images even when local images already exist}
                            {--insecure : Skip TLS certificate verification (local Windows CA issues)}';

    /**
     * @var string
     */
    protected $description = 'Import products, variants, collections and images from a public Shopify storefront';

    public function handle(ShopifyCatalogImporter $importer): int
    {
        $dump = $this->option('dump');

        if (is_string($dump) && $dump !== '') {
            $this->info("Importing catalog from dump {$dump}…");

            $result = $importer->importFromDump(
                dumpPath: $dump,
                refreshImages: (bool) $this->option('refresh-images'),
            );
        } else {
            $url = (string) $this->argument('url');

            $this->info("Importing catalog from {$url}…");

            $result = $importer->import(
                storeUrl: $url,
                refreshImages: (bool) $this->option('refresh-images'),
                verifySsl: ! (bool) $this->option('insecure'),
            );
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Products created', $result['products_created']],
                ['Products updated', $result['products_updated']],
                ['Images imported', $result['images_imported']],
                ['Collections synced', $result['collections_synced']],
            ],
        );

        $this->info('Import finished.');

        return self::SUCCESS;
    }
}
