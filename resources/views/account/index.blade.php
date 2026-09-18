@extends('layouts.workspace')
@section('title', 'Moj profil')
@section('content')
@if($user)
<div class="tool-heading"><div><div class="section-kicker">TVOJ LIČNI SHOPPING PROSTOR</div><h1>Hej, <em>{{$user->username}}.</em></h1><p>Tvoji modeli, tvoje veličine i cijene koje čekaš. Sve na jednom mjestu.</p></div><form method="post" action="{{route('account.logout')}}">@csrf<button class="tool-button tool-button-light" type="submit">Odjavi se ↗</button></form></div>
<div class="tool-stats"><div><strong>{{$profileStats['fits']}}</strong><span>SAČUVANIH<br>VELIČINA</span></div><div><strong>{{$profileStats['active']}}</strong><span>AKTIVNIH<br>PRAĆENJA</span></div><div><strong>{{$profileStats['triggered']}}</strong><span>ISPUNJENIH<br>USLOVA</span></div></div>
<div class="dashboard-banner"><div><strong>Sljedeći dobar par počinje dobrim izborom.</strong><p>Dodaj patike koje već nosiš ili zadaj cijenu za model koji želiš.</p></div><a class="tool-button tool-button-acid" href="{{route('catalog')}}">Pronađi novi par <b>↗</b></a></div>
<div class="feature-grid">
<article class="feature-card"><span class="feature-icon" aria-hidden="true">↔</span><h2>Pogodi veličinu.</h2><p>Sačuvaj veličine modela koje već nosiš. Koristi ih kao polaznu tačku dok biraš sljedeći par.</p><a class="tool-button" href="{{route('fit-passport')}}">Moje veličine <b>↗</b></a></article>
<article class="feature-card"><span class="feature-icon" aria-hidden="true">↘</span><h2>Sačekaj svoju cijenu.</h2><p>Odaberi model, veličinu i budžet. Pregledaj koje ponude trenutno ispunjavaju tvoje uslove.</p><a class="tool-button" href="{{route('alerts')}}">Moja praćenja <b>↗</b></a></article>
<article class="feature-card"><span class="feature-icon" aria-hidden="true">⌗</span><h2>Od etikete do para.</h2><p>Fotografiši etiketu ili unesi šifru. Provjeri postoji li isti model u domaćem katalogu.</p><a class="tool-button" href="{{route('scanner')}}">Otvori skener <b>↗</b></a></article>
</div>
@else
@php($register = old('form', request('mode')) === 'register')
<section class="auth-layout">
<div class="auth-story"><div><div class="section-kicker">DOBRO DOŠAO U SVOJ AERCHI</div><h1>DOBAR PAR.<br>BOLJA CIJENA.<br><em>TVOJA IGRA.</em></h1><p>Manje traženja. Više dobrih odluka. Sačuvaj svoje veličine i prati patike koje stvarno želiš.</p></div><div class="member-ticket"><b aria-hidden="true">A↗</b><div><strong>Tvoj shopping, na jednom mjestu.</strong><small>Veličine i praćenja vezani za tvoj profil.<br>Dostupni i kada se prijaviš s drugog uređaja.</small></div></div><div class="auth-benefits"><span>01 / MOJE VELIČINE</span><span>02 / MOJE CIJENE</span><span>03 / MOJI MODELI</span></div></div>
<div class="auth-panel">
<nav class="auth-tabs" aria-label="Pristup računu"><a href="{{route('account')}}" @unless($register) aria-current="page" @endunless>Prijava</a><a href="{{route('account', ['mode'=>'register'])}}" @if($register) aria-current="page" @endif>Novi račun</a></nav>
<h2>{{$register ? 'Uđi u svoju igru.' : 'Dobar je dan za novi par.'}}</h2><p>{{$register ? 'Odaberi korisničko ime i lozinku. Email nije potreban.' : 'Prijavi se i nastavi gdje si stao.'}}</p>
<form class="tool-form" method="post" action="{{route($register ? 'account.register' : 'account.login')}}" data-pending="{{$register ? 'Kreiram račun…' : 'Prijavljujem…'}}">
@csrf<input type="hidden" name="form" value="{{$register ? 'register' : 'login'}}">
<div class="tool-field"><label for="username">Korisničko ime</label><input id="username" name="username" value="{{old('username')}}" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="npr. sneakerhead" required maxlength="40" @if($register) minlength="3" aria-describedby="username-help" @endif @error('username') aria-invalid="true" @enderror>@if($register)<small id="username-help" class="field-help">Najmanje 3 znaka. Slova, brojevi, crtica ili donja crta.</small>@endif</div>
<div class="tool-field"><label for="password">Lozinka</label><div class="password-control"><input id="password" type="password" name="password" autocomplete="{{$register ? 'new-password' : 'current-password'}}" placeholder="{{$register ? 'Najmanje 8 znakova' : 'Tvoja lozinka'}}" required @if($register) minlength="8" data-new-password aria-describedby="password-hint" @endif @error('password') aria-invalid="true" @enderror><button type="button" data-password-toggle="password" aria-controls="password" aria-pressed="false" hidden>Prikaži</button></div>@if($register)<div class="password-meter" aria-hidden="true"><span data-password-meter></span></div><small class="field-help" id="password-hint" data-password-hint>Koristi najmanje 8 znakova i lozinku koju ne koristiš drugdje.</small>@endif</div>
@if($register)<div class="tool-field"><label for="password-confirmation">Ponovi lozinku</label><div class="password-control"><input id="password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Još jednom, za svaki slučaj" required minlength="8"><button type="button" data-password-toggle="password-confirmation" aria-controls="password-confirmation" aria-pressed="false" hidden>Prikaži</button></div></div>@endif
<button class="tool-button tool-button-blue" type="submit">{{$register ? 'Kreiraj moj račun' : 'Idemo — prijavi me'}} <b>↗</b></button>
</form>
<div class="auth-note">Samo razgledaš? <a href="{{route('catalog')}}">Katalog je otvoren svima ↗</a><br>Račun čuva tvoje veličine i praćenja i nakon odjave.</div>
</div></section>
@endif
@endsection
