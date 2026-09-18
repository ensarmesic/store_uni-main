<?php
namespace App\Services\ProductImport\Importers;
class AstraImporter extends StructuredProductImporter {public function getStoreName():string{return 'Astra & Borovo';}public function getStoreSlug():string{return 'astra';}public function getWebsiteUrl():string{return 'https://astra.ba';}protected function listingUrls():array{return ['https://astra.ba/shop/muskarci/obuca/patike','https://astra.ba/shop/zene/obuca/patike'];}}
