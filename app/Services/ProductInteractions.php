<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductInteractions
{
    public function record(Request $request, Product $product, string $kind): void
    {
        if (! in_array($kind, ['view','favorite','compare','alert','outbound'], true)) return;
        DB::table('product_interactions')->insertOrIgnore([
            'product_id' => $product->id, 'kind' => $kind, 'observed_on' => now()->toDateString(),
            'owner_hash' => hash_hmac('sha256', app(ShoppingProfile::class)->owner($request), (string) config('app.key')),
        ]);
    }
}
