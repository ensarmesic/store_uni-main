<?php

namespace App\Http\Controllers;

use App\Services\LabelScanner;
use Illuminate\Http\Request;

class ScannerController
{
    public function index()
    {
        return view('scanner.index', ['lensReady' => app(\App\Services\VisualSearch::class)->ready()]);
    }

    public function scan(Request $request, LabelScanner $scanner)
    {
        $data = $request->validate([
            'mode' => ['nullable', 'in:label,visual'],
            'image' => ['required_without:label_text', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'label_text' => ['required_without:image', 'nullable', 'string', 'max:1000'],
        ], [
            'image.required_without' => 'Dodaj fotografiju ili unesi tekst s etikete.',
            'image.max' => 'Fotografija može imati najviše 10 MB.',
            'image.mimes' => 'Odaberi JPG, PNG ili WebP fotografiju.',
            'label_text.required_without' => 'Unesi tekst etikete ili odaberi fotografiju.',
        ]);
        if (! empty($data['label_text'])) {
            $result = $scanner->fromText($data['label_text']);
            return $this->results($request, $result);
        }
        $file = $data['image'];

        try {
            $result = ($data['mode'] ?? 'label') === 'visual'
                ? app(\App\Services\VisualSearch::class)->search($file->getRealPath())
                : $scanner->scan($file->getRealPath());
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['image' => 'Fotografiju trenutno ne možemo pročitati. Pokušaj jasniju sliku ili unesi tekst s etikete ispod.']);
        }

        return $this->results($request, $result);
    }

    private function results(Request $request, array $result)
    {
        $decisions = [];
        foreach ($result['matches'] as $product) {
            $fit = app(\App\Services\FitRecommendation::class)->for($product, (string) $request->session()->get('fit_passport_key'), $request->session()->get('user_id'));
            $size = $result['size'] ?: (($fit['suitable'] ?? false) ? $fit['size'] : null);
            $decisions[$product->id] = app(\App\Services\PurchaseDecision::class)->for($product, $size, $fit);
        }
        return view('scanner.index', ['result' => $result, 'decisions' => $decisions, 'lensReady' => app(\App\Services\VisualSearch::class)->ready()]);
    }
}
