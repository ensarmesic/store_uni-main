<?php

namespace App\Services\ProductImport\Importers;

use App\Services\ProductImport\Contracts\StoreImporterInterface;
use App\Services\ProductImport\ProductNormalizer;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractHtmlImporter implements StoreImporterInterface
{
    private array $failures = [];

    protected bool $missingOnly = false;

    public function onlyMissing(bool $missing): void
    {
        $this->missingOnly = $missing;
    }

    public function resetFailures(): void
    {
        $this->failures = [];
    }

    public function failures(): array
    {
        return $this->failures;
    }

    protected function reportFailure(string $url, string $message): void
    {
        $this->failures[] = $url.': '.mb_substr($message, 0, 500);
    }

    public function __construct(protected ProductNormalizer $normalizer) {}

    protected function get(string $url): string
    {
        usleep((int) env('IMPORT_DELAY_MS', 750) * 1000);

        return Http::withHeaders(['User-Agent' => env('IMPORT_USER_AGENT')])
            ->withOptions(['verify' => config('catalog.ca_bundle') ?: true])
            ->timeout(15)
            ->retry(3, fn (int $attempt) => 250 * (2 ** $attempt))
            ->get($url)
            ->throw()
            ->body();
    }

    protected function getMany(array $urls): array
    {
        usleep((int) env('IMPORT_DELAY_MS', 750) * 1000);
        $keys = [];

        $responses = Http::pool(function (Pool $pool) use ($urls, &$keys) {
            $requests = [];
            foreach ($urls as $url) {
                $key = sha1($url);
                $keys[$key] = $url;
                $requests[] = $pool->as($key)
                    ->withOptions(['verify' => config('catalog.ca_bundle') ?: true])
                    ->withHeaders(['User-Agent' => env('IMPORT_USER_AGENT')])
                    ->timeout(20)
                    ->get($url);
            }

            return $requests;
        });

        $bodies = [];
        foreach ($responses as $key => $response) {
            if ($response instanceof Response && $response->successful()) {
                $bodies[$keys[$key]] = $response->body();
            }
        }

        return $bodies;
    }

    public function normalize(array $product): array
    {
        return $this->normalizer->normalize($product);
    }

    protected function text(Crawler $node, string $selector): ?string
    {
        try {
            return trim($node->filter($selector)->first()->text());
        } catch (\Throwable) {
            return null;
        }
    }
}
