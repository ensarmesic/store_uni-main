<?php

namespace App\Services;

use App\Models\{Favorite, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShoppingProfile
{
    public function owner(Request $request): string
    {
        if ($id = $request->session()->get('user_id')) return 'user:'.$id;
        if (! $request->session()->has('shopping_key')) $request->session()->put('shopping_key', 'guest:'.Str::uuid());
        return $request->session()->get('shopping_key');
    }

    public function favoriteIds(Request $request): array
    {
        return Favorite::where('owner_key', $this->owner($request))->pluck('product_id')->all();
    }

    public function preferences(Request $request): array
    {
        if ($id = $request->session()->get('user_id')) return User::find($id)?->shopping_preferences ?? [];
        return $request->session()->get('shopping_preferences', []);
    }

    public function mergeGuest(Request $request, User $user): void
    {
        if ($key = $request->session()->pull('shopping_key')) {
            DB::transaction(function () use ($key, $user) {
                foreach (Favorite::where('owner_key', $key)->get() as $favorite) {
                    Favorite::firstOrCreate(['owner_key' => 'user:'.$user->id, 'product_id' => $favorite->product_id]);
                }
                Favorite::where('owner_key', $key)->delete();
            });
        }
        if ($preferences = $request->session()->pull('shopping_preferences')) {
            if (! $user->shopping_preferences) $user->update(['shopping_preferences' => $preferences]);
        }
    }
}
