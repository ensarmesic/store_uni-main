<?php
namespace App\Services\ProductImport\Importers;
class SportVisionImporter extends StructuredProductImporter {public function getStoreName():string{return 'Sport Vision';}public function getStoreSlug():string{return 'sportvision';}public function getWebsiteUrl():string{return 'https://www.sportvision.ba';}protected function listingUrls():array{return ['https://www.sportvision.ba/patike/page-1'];}}
