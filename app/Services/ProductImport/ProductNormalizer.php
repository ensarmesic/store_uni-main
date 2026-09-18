<?php

namespace App\Services\ProductImport;

class ProductNormalizer
{
    public const BRANDS = [
        'NEW BALANCE' => ['NEW BALANCE'],
        'UNDER ARMOUR' => ['UNDER ARMOUR'],
        'ASICS' => ['ASICS'],
        'ADIDAS' => ['ADIDAS'],
        'NIKE' => ['NIKE', 'JORDAN'],
        'PUMA' => ['PUMA'],
        'HOKA' => ['HOKA ONE ONE', 'HOKA'],
        'SKECHERS' => ['SKECHERS'],
        'REEBOK' => ['REEBOK'],
        'SALOMON' => ['SALOMON'],
        'MIZUNO' => ['MIZUNO'],
        'ON' => ['ON RUNNING', 'ON CLOUD'],
        'CONVERSE' => ['CONVERSE'],
        'VANS' => ['VANS'],
        'BROOKS' => ['BROOKS'],
        'SAUCONY' => ['SAUCONY'],
        'LOTTO' => ['LOTTO'],
        'KAPPA' => ['KAPPA'],
        'LACOSTE' => ['LACOSTE'],
        'FILA' => ['FILA'],
        'GEOX' => ['GEOX'],
        'ECCO' => ['ECCO'],
        'S.OLIVER' => ['S.OLIVER', 'S OLIVER'],
        'IMAC' => ['IMAC'],
        'HUGO' => ['HUGO'],
        'BOSS' => ['BOSS'],
        'PEPE JEANS' => ['PEPE JEANS'],
        'GUESS' => ['GUESS'],
        'REPLAY' => ['REPLAY'],
        'NERO GIARDINI' => ['NEROGIARDINI', 'NERO GIARDINI'],
        'DIFFERENTE JUNIOR' => ['DIFFERENTE JUNIOR'],
        'DIFFERENTE' => ['DIFFERENTE'],
        'CLAUDIA DONATELI' => ['CLAUDIA DONATELI'],
        'LUSSO' => ['LUSSO'],
        'PANDINO' => ['PANDINO GIRL', 'PANDINO BOY', 'PANDINO'],
        'ONITSUKA TIGER' => ['ONITSUKA TIGER'],
        'JOMA' => ['JOMA'],
        'HEAD' => ['HEAD'],
        'UMBRO' => ['UMBRO'],
        'DIADORA' => ['DIADORA'],
        'GIVOVA' => ['GIVOVA'],
        'LEGEA' => ['LEGEA'],
        'SLAZENGER' => ['SLAZENGER'],
        'SERGIO TACCHINI' => ['SERGIO TACCHINI'],
        'KRONOS' => ['KRONOS'],
        'KANDER' => ['KANDER'],
        'LONSDALE' => ['LONSDALE'],
    ];

    public function normalize(array $product): array
    {
        $originalName = $this->clean($product['name_original'] ?? $product['name'] ?? '');
        $brand = $this->brand($product['brand'] ?? null, $originalName);
        $gender = $product['gender'] ?? $this->gender($originalName);
        $color = $product['color'] ?? $this->color($originalName);

        return array_merge($product, [
            'name_original' => $originalName,
            'name' => $originalName,
            'brand' => $brand,
            'model' => $this->model($originalName, $brand),
            'mpn' => $product['mpn'] ?? $product['sku'] ?? null,
            'gender' => $gender,
            'color' => $color,
            'category' => $product['category'] ?? 'Obuća',
        ]);
    }

    public function brand(mixed $rawBrand, string $name): string
    {
        if (is_array($rawBrand)) {
            $rawBrand = $rawBrand['name'] ?? null;
        }

        $haystack = mb_strtoupper(trim((string) $rawBrand).' '.$name);

        foreach (self::BRANDS as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (preg_match('/\b'.preg_quote($alias, '/').'\b/u', $haystack)) {
                    return $canonical;
                }
            }
        }

        return mb_strtoupper(trim((string) $rawBrand)) ?: 'OTHER';
    }

    public function model(string $name, ?string $brand = null): string
    {
        $brand ??= $this->brand(null, $name);
        $model = mb_strtoupper($name);
        $model = preg_replace('/[™®]/u', '', $model);

        foreach (self::BRANDS[$brand] ?? [$brand] as $alias) {
            $model = preg_replace('/\b'.preg_quote($alias, '/').'\b/u', ' ', $model);
        }

        $model = preg_replace('/\b(MEN.?S|WOMEN.?S|MUŠKE|MUSKE|ŽENSKE|ZENSKE|DJEČIJE|DJEČJE|DJEČIJE|DJEČIJE|UNISEX|KIDS?)\b/u', ' ', $model);
        $model = preg_replace('/(^|\s)[MW](?=\s|$)/u', ' ', $model);
        $model = preg_replace('/\b(PATIKE|PATIKA|TENISICE|RUNNING|TRAIL|SHOES?|OBUĆA|OBUCA|KOPAČKE|KOPACKE|CIPELE|ZA TRČANJE|ZA TRCANJE|ZA FITNES|ZA FUDBAL|ZA ODRASLE)\b/u', ' ', $model);
        $model = preg_replace('/\b(CRNA|CRNE|BIJELA|BIJELE|BELA|SIVA|SIVE|PLAVA|PLAVE|ZELENA|ZELENE|ROZA|CRVENA|BEŽ|BEZ|SMEĐA|SMEDJA|LJUBIČASTA|LJUBICASTA|ŽUTA|ZUTA|NARANDŽASTA|NARANDZASTA)\b/u', ' ', $model);
        $model = preg_replace('/\b(10[0-9A-Z]{5,}(?:-[0-9A-Z]+)?)\b/u', ' ', $model);
        $model = preg_replace('/[,|]+/u', ' ', $model);
        $model = preg_replace('/\s+/u', ' ', trim($model));
        $model = preg_replace('/\bGEL[ -]+/u', 'GEL-', $model);
        $model = preg_replace('/\b(GEL-[A-Z]+)[ -]+(\d+)/u', '$1 $2', $model);

        return trim($model, ' -');
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function gender(string $name): ?string
    {
        if (preg_match('/mušk|musk|\bmen\b|\bM\b/ui', $name)) return 'male';
        if (preg_match('/žensk|zensk|women|\bW\b/ui', $name)) return 'female';
        if (preg_match('/unisex/ui', $name)) return 'unisex';
        if (preg_match('/dje|deč|dec|kids?|\bGS\b/ui', $name)) return 'kids';

        return null;
    }

    private function color(string $name): ?string
    {
        $colors = ['crna', 'bijela', 'bela', 'siva', 'plava', 'zelena', 'roza', 'crvena', 'bež', 'smeđa', 'ljubičasta', 'žuta', 'narandžasta'];
        foreach ($colors as $color) {
            if (preg_match('/\b'.preg_quote($color, '/').'\b/ui', $name)) return mb_convert_case($color, MB_CASE_TITLE, 'UTF-8');
        }

        return null;
    }
}
