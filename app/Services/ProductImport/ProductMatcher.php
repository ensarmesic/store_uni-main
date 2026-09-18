<?php

namespace App\Services\ProductImport;

use App\Models\Product;

class ProductMatcher
{
    public function match(array $product): MatchResult
    {
        foreach (['ean', 'gtin'] as $field) {
            if (!empty($product[$field]) && ($match = Product::where($field, $product[$field])->first())) {
                return new MatchResult($match->id, 1.0, $field, 'confirmed');
            }
        }

        if (!empty($product['ean']) && Product::whereNotNull('ean')->where('ean', '!=', $product['ean'])
            ->where('brand', $product['brand'])->where('model', $product['model'])->exists()) {
            return new MatchResult(null, 0, 'conflicting_ean', 'rejected');
        }

        if (!empty($product['mpn']) && ($match = Product::where('brand', $product['brand'])->where('mpn', $product['mpn'])->first())) {
            return new MatchResult($match->id, .95, 'mpn', 'confirmed');
        }

        if (($product['allow_name_match'] ?? true) === false || trim($product['model'] ?? '') === '') {
            return new MatchResult(null, 0, 'none', 'suggested');
        }

        $query = Product::where('brand', $product['brand'])->where('model', $product['model']);
        if (!empty($product['mpn'])) $query->where(fn ($q) => $q->whereNull('mpn')->orWhere('mpn', $product['mpn']));
        if (!empty($product['gender'])) $query->where(fn ($q) => $q->whereNull('gender')->orWhere('gender', $product['gender']));
        if (!empty($product['color'])) $query->where(fn ($q) => $q->whereNull('color')->orWhere('color', $product['color']));

        return ($match = $query->first())
            ? new MatchResult($match->id, .90, 'brand_model', 'confirmed')
            : new MatchResult(null, 0, 'none', 'suggested');
    }
}
