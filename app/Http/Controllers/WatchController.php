<?php

namespace App\Http\Controllers;

use App\Models\{Product, ShoppingWatch, WatchNotification};
use App\Services\{EuSize, ShoppingProfile};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Mail, URL};

class WatchController
{
    public function index(Request $request, ShoppingProfile $profile)
    {
        $watches = ShoppingWatch::where('owner_key', $profile->owner($request))->with('product')->latest()->get();
        $notifications = WatchNotification::whereIn('shopping_watch_id', $watches->pluck('id'))->latest()->paginate(20);
        return view('watch.index', compact('watches', 'notifications'));
    }

    public function store(Request $request, Product $product, ShoppingProfile $profile)
    {
        $data = $request->validate(['size' => ['required', 'string', 'max:20'], 'budget' => ['nullable', 'numeric', 'min:1', 'max:10000'],
            'email' => ['nullable', 'email', 'max:254']]);
        if (EuSize::number($data['size']) === null) return back()->withErrors(['size' => 'Unesi važeći EU broj.']);
        if (! empty($data['email']) && config('mail.default') !== 'smtp') return back()->withErrors(['email' => 'Email dostava još nije podešena. Uključi Watch bez emaila za obavijesti u aplikaciji.']);
        $watch = ShoppingWatch::firstOrNew(['owner_key' => $profile->owner($request), 'product_id' => $product->id, 'size' => EuSize::label(EuSize::number($data['size']))]);
        $email = $data['email'] ?? null;
        if ($watch->email !== $email) $watch->email_verified_at = null;
        $watch->fill(['budget' => $data['budget'] ?? null, 'is_active' => true, 'email' => $email,
            'fit_session_key' => $request->session()->get('fit_passport_key')]);
        $watch->save();
        app(\App\Services\ProductInteractions::class)->record($request, $product, 'alert');
        if ($email && ! $watch->email_verified_at) {
            $url = URL::temporarySignedRoute('watch.verify', now()->addHour(), ['watch' => $watch->id, 'email_hash' => hash('sha256', $email)]);
            try {
                Mail::raw("Potvrdi email za AErchi Watch:\n".$url, fn ($message) => $message->to($email)->subject('Potvrdi AErchi Watch'));
            } catch (\Throwable $exception) {
                report($exception);
                return back()->withErrors(['email' => 'Watch je sačuvan, ali email potvrda nije poslana. Pokušaj ponovo.']);
            }
        }
        return back()->with('status', 'AErchi Watch je uključen.'.($email && ! $watch->email_verified_at ? ' Potvrdi email preko linka u poruci.' : ''));
    }

    public function verify(Request $request, ShoppingWatch $watch)
    {
        abort_unless($watch->email && hash_equals(hash('sha256', $watch->email), (string) $request->query('email_hash')), 403);
        $watch->update(['email_verified_at' => now()]);
        return redirect()->route('watch')->with('status', 'Email za Watch je potvrđen.');
    }

    public function destroy(Request $request, ShoppingWatch $watch, ShoppingProfile $profile)
    {
        abort_unless($watch->owner_key === $profile->owner($request), 403);
        $watch->update(['is_active' => false]);
        return back()->with('status', 'Watch je ugašen.');
    }

    public function read(Request $request, WatchNotification $notification, ShoppingProfile $profile)
    {
        abort_unless($notification->watch->owner_key === $profile->owner($request), 403);
        $notification->update(['read_at' => now()]);
        return back();
    }
}
