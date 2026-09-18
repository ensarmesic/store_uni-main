<?php

namespace App\Http\Controllers;

use App\Models\{Product, PurchaseFeedback};
use App\Services\{EuSize, ShoppingProfile};
use Illuminate\Http\Request;

class PurchaseFeedbackController
{
    public function index(Request $request, ShoppingProfile $profile)
    {
        $entries = PurchaseFeedback::where('owner_key', $profile->owner($request))->with('product')->latest()->paginate(20);
        return view('shopping.purchases', compact('entries'));
    }

    public function store(Request $request, Product $product, ShoppingProfile $profile)
    {
        $data = $request->validate(['size' => ['required','string','max:20'], 'fit' => ['required','in:tight,just_right,wide'],
            'outcome' => ['required','in:kept,returned']]);
        $number = EuSize::number($data['size']);
        if ($number === null) return back()->withErrors(['size' => 'Unesi važeći EU broj.']);
        $data['size'] = EuSize::label($number);
        PurchaseFeedback::updateOrCreate(['owner_key' => $profile->owner($request), 'product_id' => $product->id, 'size' => $data['size']], $data);
        return back()->with('status', 'Iskustvo kupovine je sačuvano. U Fit DNA možeš posebno dodati veličinu za buduće preporuke.');
    }

    public function destroy(Request $request, PurchaseFeedback $feedback, ShoppingProfile $profile)
    {
        abort_unless($feedback->owner_key === $profile->owner($request), 403);
        $feedback->delete();
        return back()->with('status', 'Iskustvo je uklonjeno.');
    }
}
