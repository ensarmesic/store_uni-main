<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductAlert;
use App\Services\ProductAlertStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductAlertController
{
    public function index(Request $request, ProductAlertStatus $status)
    {
        $alerts = ProductAlert::where(fn ($query) => $this->ownerQuery($query, $request))
            ->with(['product.offers' => fn ($offers) => $offers->where('is_active', true)->with('variants')])
            ->latest()
            ->get();

        $currentPrices = [];
        foreach ($alerts as $alert) {
            $currentPrices[$alert->id] = $status->currentPrice($alert);
            $status->check($alert, $currentPrices[$alert->id]);
        }

        return view('alerts.index', compact('alerts', 'currentPrices'));
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'type' => ['required', 'in:price,restock'],
            'size' => ['nullable', 'string', 'max:20'],
            'target_price' => ['required_if:type,price', 'nullable', 'numeric', 'min:0.01'],
        ], ['target_price.required_if' => 'Unesi ciljnu cijenu u KM.', 'target_price.min' => 'Ciljna cijena mora biti veća od nule.']);

        if ($data['type'] === 'price' && empty($data['target_price'])) {
            return back()->withErrors(['target_price' => 'Unesi ciljnu cijenu.']);
        }

        $attributes = [
            'product_id' => $product->id, 'type' => $data['type'],
            'size' => $data['size'] ?? null,
            'target_price' => $data['type'] === 'price' ? $data['target_price'] : null,
        ];
        $existing = ProductAlert::where(fn ($query) => $this->ownerQuery($query, $request))->where($attributes)->first();
        if ($existing) {
            $existing->update(['is_active' => true, 'triggered_at' => null]);
        } else {
            ProductAlert::create($attributes + [
                'session_key' => $this->sessionKey($request), 'user_id' => $request->session()->get('user_id'),
            ]);
        }

        return back()->with('status', 'Praćenje je uključeno. Status i trenutnu cijenu pronađi u „Pratim cijene“.');
    }

    public function destroy(Request $request, ProductAlert $alert)
    {
        abort_unless($alert->session_key === $this->sessionKey($request) || ($alert->user_id && $alert->user_id === $request->session()->get('user_id')), 403);
        $alert->update(['is_active' => false]);

        return back()->with('status', 'Praćenje je ugašeno. Možeš ga ponovo uključiti kad poželiš.');
    }

    private function sessionKey(Request $request): string
    {
        if (! $key = $request->session()->get('aerchi_session_key')) {
            $key = (string) Str::uuid();
            $request->session()->put('aerchi_session_key', $key);
        }

        return $key;
    }

    private function ownerQuery($query, Request $request)
    {
        $key = $this->sessionKey($request);
        $userId = $request->session()->get('user_id');

        return $query->where('session_key', $key)->when($userId, fn ($owner) => $owner->orWhere('user_id', $userId));
    }
}
