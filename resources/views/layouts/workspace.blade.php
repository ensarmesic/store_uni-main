<!doctype html>
<html lang="bs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#3155ff">
    <title>@yield('title', 'Moj AErchi') — AErchi.ba</title>
    <link rel="stylesheet" href="/css/catalog.css?v=8">
    <link rel="stylesheet" href="/css/workspace.css?v=2">
    <script src="/js/workspace.js?v=1" defer></script>
    <link rel="stylesheet" href="/css/shopping.css?v=1">
    <link rel="stylesheet" href="/css/intelligence.css?v=1">
</head>
<body class="workspace-body">
<a class="skip-link" href="#sadrzaj">Preskoči na sadržaj</a>
<div class="topbar"><span class="live-dot"></span> TVOJ PAR. TVOJA VELIČINA. TVOJA CIJENA.</div>
<header class="header"><div class="shell header-inner">
    <a class="brandmark" href="{{route('catalog')}}"><i>A</i><span>AErchi<em>.ba</em></span></a>
    <nav class="workspace-main-nav" aria-label="Glavna navigacija"><a href="{{route('catalog')}}">Katalog</a><a href="{{route('deals')}}">Akcije ↗</a></nav>
    <a class="workspace-profile" href="{{route('account')}}"><span aria-hidden="true">◎</span> Moj AErchi</a>
</div></header>
<div class="shell workspace-tabs"><nav aria-label="Lični alati">
    @foreach(['account'=>['01','Moj profil'], 'fit-passport'=>['02','Moje veličine'], 'alerts'=>['03','Pratim cijene'], 'scanner'=>['04','Skeniraj etiketu']] as $route => [$number, $label])
        <a href="{{route($route)}}" @if(request()->routeIs($route, $route.'.*')) aria-current="page" @endif><span>{{$number}}</span>{{$label}}<b aria-hidden="true">↗</b></a>
    @endforeach
</nav></div>
@include('partials.shopping-nav')
<main id="sadrzaj" class="shell workspace-main">
    @include('partials.feedback')
    @yield('content')
</main>
<footer class="workspace-footer"><div class="shell"><strong>AErchi.ba</strong><p>Pronađi bolje. Plati manje. Kupi u BiH.</p><a href="{{route('catalog')}}">Nazad na katalog ↗</a></div></footer>
</body>
</html>
