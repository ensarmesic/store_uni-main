<?php

namespace App\Http\Controllers;

use App\Services\LabelScanner;
use Illuminate\Http\Request;

class ScannerController
{
    public function index()
    {
        return view('scanner.index');
    }

    public function scan(Request $request, LabelScanner $scanner)
    {
        $data = $request->validate([
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
            return view('scanner.index', compact('result'));
        }
        $file = $data['image'];

        try {
            $result = $scanner->scan($file->getRealPath());
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['image' => 'Fotografiju trenutno ne možemo pročitati. Pokušaj jasniju sliku ili unesi tekst s etikete ispod.']);
        }

        return view('scanner.index', compact('result'));
    }
}
