<?php
namespace App\Services\ProductImport\Importers;
class BuzzImporter extends StructuredProductImporter {public function getStoreName():string{return 'Buzz Sneakers';}public function getStoreSlug():string{return 'buzz';}public function getWebsiteUrl():string{return 'https://www.buzzsneakers.ba';}protected function listingUrls():array{return ['https://www.buzzsneakers.ba/patike/page-1'];}}
