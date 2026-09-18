<?php

namespace App\Http\Controllers;

use App\Models\FitPassportEntry;
use App\Models\FitGraphEdge;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FitPassportController
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $entries = FitPassportEntry::query()
            ->where(fn ($query) => $this->ownerQuery($query, $request))
            ->with('product')
            ->latest()
            ->get();

        $candidates = collect();
        if (mb_strlen(trim((string) $request->input('q'))) >= 2) {
            $query = Product::query();
            foreach (preg_split('/\s+/', trim($request->input('q'))) as $word) {
                $query->where(fn ($match) => $match->where('name', 'like', '%'.$word.'%')->orWhere('brand', 'like', '%'.$word.'%')->orWhere('model', 'like', '%'.$word.'%'));
            }
            $candidates = $query->orderBy('name')->limit(8)->get();
        }

        return view('fit-passport.index', compact('entries', 'candidates'));
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'size' => ['required', 'string', 'max:20'],
            'fit' => ['required', 'in:tight,just_right,wide'],
        ]);

        $entry = FitPassportEntry::where(fn ($query) => $this->ownerQuery($query, $request))
            ->where('product_id', $product->id)->where('size', $data['size'])->first();
        if ($entry) {
            $entry->update(['fit' => $data['fit']]);
        } else {
            $entry = FitPassportEntry::create([
                'session_id' => $this->passportKey($request), 'product_id' => $product->id,
                'size' => $data['size'], 'fit' => $data['fit'], 'user_id' => $request->session()->get('user_id'),
            ]);
        }

        FitPassportEntry::where(fn ($query) => $this->ownerQuery($query, $request))
            ->where('id', '!=', $entry->id)
            ->get()
            ->each(function (FitPassportEntry $other) use ($entry, $request) {
                FitGraphEdge::updateOrCreate([
                    'session_key' => $entry->session_id,
                    'source_product_id' => $other->product_id,
                    'source_size' => $other->size,
                    'target_product_id' => $entry->product_id,
                    'target_size' => $entry->size,
                ], ['target_fit' => $entry->fit, 'user_id' => $request->session()->get('user_id')]);
                FitGraphEdge::updateOrCreate([
                    'session_key' => $entry->session_id,
                    'source_product_id' => $entry->product_id,
                    'source_size' => $entry->size,
                    'target_product_id' => $other->product_id,
                    'target_size' => $other->size,
                ], ['target_fit' => $other->fit, 'user_id' => $request->session()->get('user_id')]);
            });

        return back()->with('status', 'Patike su dodane u tvoj Fit DNA.');
    }

    public function destroy(Request $request, FitPassportEntry $entry)
    {
        abort_unless($entry->session_id === $this->passportKey($request) || ($entry->user_id && $entry->user_id === $request->session()->get('user_id')), 403);
        FitGraphEdge::where(function ($query) use ($entry, $request) {
            $query->where('session_key', $entry->session_id)
                ->when($request->session()->get('user_id'), fn ($owner, $id) => $owner->orWhere('user_id', $id));
        })->where(function ($query) use ($entry) {
            $query->where(fn ($source) => $source->where('source_product_id', $entry->product_id)->where('source_size', $entry->size))
                ->orWhere(fn ($target) => $target->where('target_product_id', $entry->product_id)->where('target_size', $entry->size));
        })->delete();
        $entry->delete();

        return back()->with('status', 'Patike su uklonjene iz Fit DNA-a.');
    }

    private function passportKey(Request $request): string
    {
        if (! $key = $request->session()->get('fit_passport_key')) {
            $key = (string) Str::uuid();
            $request->session()->put('fit_passport_key', $key);
        }

        return $key;
    }

    private function ownerQuery($query, Request $request)
    {
        $key = $this->passportKey($request);
        $userId = $request->session()->get('user_id');

        return $query->where('session_id', $key)->when($userId, fn ($owner) => $owner->orWhere('user_id', $userId));
    }
}
