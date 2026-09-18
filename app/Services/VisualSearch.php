<?php

namespace App\Services;

use App\Models\Product;
use Symfony\Component\Process\Process;

class VisualSearch
{
    public function ready(): bool { return is_file(storage_path('app/lens/index.npz')); }

    public function search(string $path): array
    {
        if (! $this->ready()) throw new \RuntimeException('Lens index is not ready.');
        $process = new Process([config('scanner.lens_python'), base_path('scripts/lens/lens.py'), 'search',
            '--storage', storage_path('app/lens'), '--image', $path]);
        $process->setTimeout(90); $process->mustRun();
        $data = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $scores = collect($data['matches'])->pluck('similarity', 'product_id');
        $matches = Product::whereIn('id', $scores->keys())
            ->whereHas('offers', fn ($q) => $q->where('is_active', true))
            ->with(['offers' => fn ($q) => $q->where('is_active', true)->with(['store','variants'])])
            ->get()->sortByDesc(fn ($product) => $scores[$product->id])->take(6)->values();
        return ['matches' => $matches, 'scores' => $scores, 'indexed' => $data['indexed'], 'visual' => true,
            'brand' => null, 'style_code' => null, 'size' => null, 'text' => 'Vizuelni kandidati iz lokalnog kataloga. Potvrdi naziv i detalje modela.'];
    }
}
