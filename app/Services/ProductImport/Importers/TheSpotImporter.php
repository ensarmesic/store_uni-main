<?php
namespace App\Services\ProductImport\Importers;
class TheSpotImporter extends StructuredProductImporter {public function getStoreName():string{return 'The Spot BiH';}public function getStoreSlug():string{return 'thespot';}public function getWebsiteUrl():string{return 'https://www.thespot.ba';}protected function listingUrls():array{return ['https://www.thespot.ba/patike'];}}
