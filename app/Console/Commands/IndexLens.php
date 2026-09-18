<?php

namespace App\Console\Commands;

use App\Models\Offer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class IndexLens extends Command
{
    protected $signature = 'lens:index {--limit=0} {--download-model}';
    protected $description = 'Priprema lokalni vizuelni model i indeks slika kataloga';

    public function handle(): int
    {
        $lock = Cache::lock('lens:index', 7200);
        if (! $lock->get()) { $this->error('Indeksiranje već radi.'); return self::FAILURE; }
        try {
            $directory = storage_path('app/lens');
            if (! is_dir($directory)) mkdir($directory, 0755, true);
            if ($this->option('download-model')) {
                $download = new Process([config('scanner.lens_python'), base_path('scripts/lens/lens.py'), 'download-model', '--storage', $directory]);
                $download->setTimeout(1800); $download->mustRun(fn ($type, $buffer) => $this->output->write($buffer));
            }
            $offers = Offer::where('is_active', true)->whereNotNull('image_url')->orderByDesc('last_checked_at')->orderBy('id')->get(['product_id','image_url'])->unique('product_id');
            $limit = max(0, (int) $this->option('limit'));
            if ($limit) $offers = $offers->take($limit);
            $manifest = $directory.'/manifest.json';
            file_put_contents($manifest, json_encode(['partial' => $limit > 0, 'allowed_hosts' => config('scanner.lens_image_hosts'),
                'items' => $offers->map(fn ($offer) => ['product_id' => $offer->product_id, 'url' => $offer->image_url])->values()], JSON_THROW_ON_ERROR));
            $process = new Process([config('scanner.lens_python'), base_path('scripts/lens/lens.py'), 'index', '--storage', $directory, '--manifest', $manifest]);
            $process->setTimeout(7000); $process->mustRun(fn ($type, $buffer) => $this->output->write($buffer));
            return self::SUCCESS;
        } finally { $lock->release(); }
    }
}
