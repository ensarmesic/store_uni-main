<?php
namespace App\Services\ProductImport\Importers;
class SportRealityImporter extends StructuredProductImporter {public function getStoreName():string{return 'Sport Reality';}public function getStoreSlug():string{return 'sportreality';}public function getWebsiteUrl():string{return 'https://www.sportreality.ba';}protected function listingUrls():array{return ['https://www.sportreality.ba/patike/page-1'];}}
