<?php

namespace App\Http\Controllers;

use App\Models\FitGraphEdge;
use App\Models\FitPassportEntry;
use App\Models\ProductAlert;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController
{
    public function show(Request $request)
    {
        $user = $request->session()->get('user_id') ? User::find($request->session()->get('user_id')) : null;

        $profileStats = $user ? [
            'fits' => FitPassportEntry::where('user_id', $user->id)->count(),
            'active' => ProductAlert::where('user_id', $user->id)->where('is_active', true)->count(),
            'triggered' => ProductAlert::where('user_id', $user->id)->whereNotNull('triggered_at')->count(),
        ] : [];

        return view('account.index', compact('user', 'profileStats'));
    }

    public function register(Request $request)
    {
        if (is_string($request->input('username'))) {
            $request->merge(['username' => mb_strtolower(trim($request->input('username')))]);
        }
        $request->merge(['form' => 'register']);
        $data = $request->validate([
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:40', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'username.required' => 'Unesi korisničko ime.',
            'username.alpha_dash' => 'Korisničko ime može sadržavati slova, brojeve, crticu i donju crtu.',
            'username.min' => 'Korisničko ime treba imati najmanje 3 znaka.',
            'username.max' => 'Korisničko ime može imati najviše 40 znakova.',
            'username.unique' => 'To korisničko ime je zauzeto. Odaberi drugo.',
            'password.required' => 'Unesi lozinku.',
            'password.min' => 'Lozinka treba imati najmanje 8 znakova.',
            'password.confirmed' => 'Lozinke se ne podudaraju. Provjeri ponovljenu lozinku.',
        ]);
        User::create(['username' => strtolower($data['username']), 'password' => Hash::make($data['password'])]);

        return redirect()->route('account')->with('status', 'Račun je spreman. Prijavi se i sačuvaj svoj sljedeći dobar izbor.')->withInput(['username' => $data['username']]);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['username' => ['required', 'string', 'max:40'], 'password' => ['required', 'string']], [
            'username.required' => 'Unesi korisničko ime.',
            'password.required' => 'Unesi lozinku.',
        ]);
        $user = User::where('username', strtolower($data['username']))->first();
        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['username' => 'Korisničko ime ili lozinka nisu tačni. Pokušaj ponovo.'])->withInput($request->only('username', 'form'));
        }
        $request->session()->regenerate();
        $request->session()->put('user_id', $user->id);
        app(\App\Services\ShoppingProfile::class)->mergeGuest($request, $user);
        if ($fitKey = $request->session()->get('fit_passport_key')) {
            FitPassportEntry::where('session_id', $fitKey)->update(['user_id' => $user->id]);
            FitGraphEdge::where('session_key', $fitKey)->update(['user_id' => $user->id]);
        }
        if ($alertKey = $request->session()->get('aerchi_session_key')) {
            ProductAlert::where('session_key', $alertKey)->update(['user_id' => $user->id]);
        }

        return redirect()->route('account')->with('status', 'Uspješno si prijavljen.');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('account');
    }
}
