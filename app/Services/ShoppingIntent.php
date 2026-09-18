<?php

namespace App\Services;

use App\Models\Product;

class ShoppingIntent
{
    public function parse(string $text): array
    {
        $lower = mb_strtolower($text);
        $intent = ['budget' => null, 'size' => null, 'colors' => [], 'brand' => null, 'can_wait' => false, 'anchors' => [], 'warnings' => []];
        if (preg_match('/(?:budžet|budzet|do)\s*[:=]?\s*(\d+(?:[.,]\d{1,2})?)\s*(?:km|bam)?/ui', $text, $m)) $intent['budget'] = (float) str_replace(',', '.', $m[1]);
        if (preg_match('/(?:moja veličina|moja velicina|moj broj|veličina|velicina|eu)\s*[:=]?\s*(\d{2}(?:\s+[12]\/3|[.,]\d|[⅓⅔½])?)(?!\d)/ui', $text, $m) && EuSize::number($m[1]) !== null) $intent['size'] = EuSize::label(EuSize::number($m[1]));
        $intent['can_wait'] = (bool) preg_match('/(?:mogu čekati|mogu cekati|nije.*hitno|ne žuri|ne zuri)/u', $lower);
        foreach (['crn' => ['crna','black'], 'siv' => ['siva','grey','gray'], 'bijel' => ['bijela','white'], 'plav' => ['plava','blue']] as $word => $aliases) {
            if (str_contains($lower, $word)) $intent['colors'] = [...$intent['colors'], ...$aliases];
        }
        if (preg_match('/(?:želim|zelim|tražim|trazim|samo)\s+(nike|adidas|asics|puma|hoka|new balance|skechers|salomon)\b/ui', $text, $m)) $intent['brand'] = strtoupper($m[1]);
        // Existing catalogue names provide anchors, never invented product IDs.
        $anchorGroups = [];
        foreach (Product::whereNotNull('model')->get(['id','brand','model','name']) as $product) {
            $name = mb_strtolower($product->brand.' '.$product->model);
            $position = mb_strpos($lower, $name);
            if ($position === false) continue;
            $tail = mb_substr($lower, $position + mb_strlen($name), 90);
            if (! preg_match('/^\s*(?:\/|,|-)?\s*(?:broj|eu)?\s*(\d{2}(?:\s+[12]\/3|[.,]\d|[⅓⅔½])?)(?!\d)([^.;]{0,45})/u', $tail, $m)) continue;
            if (EuSize::number($m[1]) === null) continue;
            $fitText = $m[2];
            $fit = preg_match('/tijes|stiš|stis|tight/u', $fitText) ? 'tight' : (preg_match('/širo|siro|wide/u', $fitText) ? 'wide' : (str_contains($fitText,'taman') ? 'just_right' : null));
            if ($fit) $anchorGroups[$name][] = ['product_id' => $product->id, 'size' => EuSize::label(EuSize::number($m[1])), 'fit' => $fit];
        }
        // Ambiguous colourways of the same model are not silently treated as an exact reference.
        foreach ($anchorGroups as $name => $anchors) {
            if (count($anchors) === 1) $intent['anchors'][] = $anchors[0];
            else $intent['warnings'][] = 'Naziv „'.$name.'“ odgovara više zapisa. Odaberi tačan model u Fit DNA da ne pretpostavimo pogrešnu varijantu.';
        }
        if (preg_match('/nosim|stiš|stis/u', $lower) && ! $intent['anchors']) $intent['warnings'][] = 'Nismo povezali opis postojećeg para. Dodaj ga u Fit DNA ili navedi tačan naziv iz kataloga, broj i riječ „taman“, „tijesne“ ili „široke“.';
        return $intent;
    }
}
