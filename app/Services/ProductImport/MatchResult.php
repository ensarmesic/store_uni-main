<?php
namespace App\Services\ProductImport;
final class MatchResult {public function __construct(public readonly ?int $productId,public readonly float $confidence,public readonly string $method,public readonly string $status) {}}
