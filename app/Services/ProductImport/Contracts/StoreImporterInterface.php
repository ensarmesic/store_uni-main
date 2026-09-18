<?php
namespace App\Services\ProductImport\Contracts;
interface StoreImporterInterface {public function getStoreName():string;public function getStoreSlug():string;public function getWebsiteUrl():string;public function fetchProducts():iterable;public function normalize(array $product):array;}
