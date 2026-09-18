<?php

namespace App\Services\ProductImport;

class SneakerFilter
{
    public function accepts(array $product): bool
    {
        $name = ($product['name_original'] ?? $product['name'] ?? '');
        $category = is_string($product['category'] ?? null) ? $product['category'] : '';
        $path = urldecode((string) parse_url($product['product_url'] ?? '', PHP_URL_PATH));
        $text = $name.' '.$category.' '.$path;

        // Explicit non-sneaker types win even when a shop calls everything footwear.
        if (preg_match('/čizm|cizm|čizm|gležnjač|gleznjac|sandale?|papuč|papuc|japank|natikač|natikac|mokasin|baletank|salonk|kopačk|kopack|\bboots?\b|\bsandals?\b|\bslides?\b|\bslippers?\b|\bloafers?\b|\bcleats?\b|espadril|štikl|stikl|majic|dukser|čarap|carap|ruksak|torba/ui', $text)) {
            return false;
        }

        return (bool) preg_match('/patik|tenisic|sneaker|\btrainers?\b|(?:running|training|basketball|tennis|trail)[ -]+shoes?/ui', $text);
    }
}
