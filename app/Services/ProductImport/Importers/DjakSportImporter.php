<?php
namespace App\Services\ProductImport\Importers;
class DjakSportImporter extends StructuredProductImporter {public function getStoreName():string{return 'Đak Sport BiH';}public function getStoreSlug():string{return 'djaksport';}public function getWebsiteUrl():string{return 'https://www.djaksport.ba';}protected function listingUrls():array{return ['https://www.djaksport.ba/patike'];}}
