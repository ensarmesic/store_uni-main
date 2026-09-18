<?php

namespace App\Services;

use App\Models\Product;
use Symfony\Component\Process\Process;

class LabelScanner
{
    public function scan(string $path): array
    {
        $process = new Process([config('scanner.tesseract_path'), $path, 'stdout', '-l', 'eng']);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('OCR nije uspio: '.$process->getErrorOutput());
        }

        return $this->fromText(trim($process->getOutput()));
    }

    public function fromText(string $text): array
    {
        $parsed = $this->parse($text);
        $parsed['text'] = $text;
        $parsed['matches'] = $this->matches($parsed);

        return $parsed;
    }

    public function parse(string $text): array
    {
        $normalized = strtoupper(preg_replace('/\s+/', ' ', $text));
        $brands = ['NEW BALANCE', 'UNDER ARMOUR', 'NIKE', 'ADIDAS', 'ASICS', 'PUMA', 'SKECHERS', 'REEBOK', 'SALOMON', 'MIZUNO', 'HOKA', 'CONVERSE', 'ECCO', 'GEOX', 'FILA'];
        $brand = collect($brands)->first(fn ($candidate) => str_contains($normalized, $candidate));
        preg_match('/\b[A-Z]{1,5}\d{2,}[A-Z0-9-]*\b/', $normalized, $codeMatch);
        preg_match('/\bEU\s*([0-9]{2}(?:[.,]\d)?(?:\s*1\/3|\s*2\/3)?)\b/', $normalized, $sizeMatch);

        return [
            'brand' => $brand,
            'style_code' => $codeMatch[0] ?? null,
            'size' => isset($sizeMatch[1]) ? str_replace(',', '.', trim($sizeMatch[1])) : null,
        ];
    }

    private function matches(array $parsed)
    {
        if (! $parsed['style_code']) return collect();

        return Product::query()
            ->where(function ($query) use ($parsed) {
                $query->whereRaw('UPPER(mpn) = ?', [$parsed['style_code']])->orWhere('gtin', $parsed['style_code']);
            })
            ->whereHas('offers', fn ($offers) => $offers->where('is_active', true))
            ->with(['offers' => fn ($offers) => $offers->where('is_active', true)->with('store')])
            ->limit(10)
            ->get();
    }
}
