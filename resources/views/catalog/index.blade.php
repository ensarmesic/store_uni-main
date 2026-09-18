<!doctype html>
<html lang="bs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Uporedi cijene patika u provjerenim trgovinama iz Bosne i Hercegovine.">
    <title>AErchi.ba — najbolje cijene patika u BiH</title>
    <link rel="stylesheet" href="/css/catalog.css?v=10">
    <link rel="stylesheet" href="/css/workspace.css?v=2">
    <link rel="stylesheet" href="/css/shopping.css?v=1">
    <script src="/js/shopping.js?v=1" defer></script>
</head>
<body>
<a class="skip-link" href="#rezultati">Preskoči na rezultate</a>
<div class="topbar"><span class="live-dot"></span> Ponude iz provjerenih BiH trgovina · cijene u konvertibilnim markama</div>
<header class="header"><div class="shell header-inner">
    <a class="brandmark" href="/catalog"><i>A</i><span>AErchi<em>.ba</em></span></a>
    <nav class="nav" aria-label="Glavna navigacija"><a class="active" href="/catalog">Patike</a><a href="/deals">Akcije</a><a href="#brendovi">Brendovi</a><a href="#trgovine">BiH trgovine</a><a href="/analytics">Analitika</a></nav>
    <a class="header-cta" href="#rezultati">Pronađi par <span>↘</span></a>
</div></header>
<nav class="shell utility-nav" aria-label="Lični alati">
    <a href="{{route('scanner')}}">Skeniraj etiketu <span>↗</span></a>
    <a href="{{route('fit-passport')}}">Moje veličine <span>↗</span></a>
    <a href="{{route('alerts')}}">Pratim cijene <span>↗</span></a>
    <a href="{{route('account')}}">Moj račun <span>↗</span></a>
</nav>
@include('partials.shopping-nav')
<main><div class="shell shopping-feedback">@include('partials.feedback')</div>
<section class="shell hero">
    <div class="hero-grid"></div>
    <div class="hero-copy">
        <div class="eyebrow"><span>100% BOSNA I HERCEGOVINA</span> / PAMETNIJE DO NOVIH PATIKA</div>
        <h1>NE PREPLAĆUJ<br><span>DOBAR PAR.</span></h1>
        <p>Jedna pretraga. Više domaćih trgovina. Stvarne cijene, dostupne veličine i najbolja ponuda bez lutanja kroz deset tabova.</p>
        <form class="hero-search" action="/catalog#rezultati" method="get"><label class="sr-only" for="hero-model">Pretraži model ili opiši šta tražiš</label><span aria-hidden="true">⌕</span><input id="hero-model" name="q" value="{{request('q',request('model'))}}" placeholder="crne Nike muške 43 do 180 KM..."><button>TRAŽI <b>↗</b></button></form>
    </div>
    <div class="hero-side" aria-label="Statistika kataloga">
        <div class="country-stamp"><small>SAMO</small><strong>BiH</strong><span>LOKALNE TRGOVINE</span></div>
        <div class="hero-number"><strong>{{number_format($stats['offers'],0,',','.')}}</strong><span>aktivnih ponuda<br>spremnih za poređenje</span></div>
    </div>
    <div class="stats"><div class="stat"><strong>{{number_format($stats['products'],0,',','.')}}</strong><span>MODELA</span></div><div class="stat"><strong>{{number_format($stats['brands'],0,',','.')}}</strong><span>BRENDOVA</span></div><div class="stat"><strong>{{$stats['stores']}}</strong><span>BIH TRGOVINE</span></div><div class="stat stat-wide"><span>{{$filters['stores']->pluck('name')->join(' · ')}}</span></div></div>
</section>
<a class="shell analytics-teaser" href="/analytics"><div><span>LIVE / AERCHI MARKET INTELLIGENCE</span><strong>VIDI ŠTA SE STVARNO<br>DEŠAVA SA CIJENAMA.</strong></div><div><b>{{$stats['brands']}}</b><small>BRENDOVA</small></div><div><b>{{number_format($stats['offers'],0,',','.')}}</b><small>PONUDA</small></div><div><b>{{$stats['stores']}}</b><small>BIH TRGOVINA</small></div><i>↗</i></a>
<a class="shell home-deal-signal" href="/deals">
    <div><span>⚡ NOVO / DEAL RADAR</span><strong>{{number_format($stats['sale_offers'],0,',','.')}} AKTUELNIH AKCIJA</strong><small>SKENIRANO KROZ CIJELI BIH KATALOG</small></div>
    <div><small>NAJVEĆI EVIDENTIRANI PAD</small><b>−{{$stats['best_discount']}}%</b></div>
    <i>UHVATI<br>POPUST <b>↗</b></i>
</a>
<section class="shell tools-discovery" aria-labelledby="tools-title">
    <div class="section-kicker">MOJ AERCHI / KUPUJ S VIŠE SIGURNOSTI</div><h2 id="tools-title">Dobar izbor nije slučajnost.</h2>
    <div class="feature-grid">
        <a class="discovery-card" href="{{route('fit-passport')}}"><span aria-hidden="true">↔</span><div><h3>Pogodi svoj broj.</h3><p>Sačuvaj veličine koje ti odgovaraju.</p></div><b>↗</b></a>
        <a class="discovery-card" href="{{route('alerts')}}"><span aria-hidden="true">↘</span><div><h3>Čekaj svoju cijenu.</h3><p>Tvoj model. Tvoj budžet. Tvoji uslovi.</p></div><b>↗</b></a>
        <a class="discovery-card" href="{{route('scanner')}}"><span aria-hidden="true">⌗</span><div><h3>Otkrij model s etikete.</h3><p>Skeniraj šifru i pronađi isti par.</p></div><b>↗</b></a>
    </div>
</section>
<section class="shell popular-zone" aria-labelledby="popular-title">
    <div class="popular-head"><div><div class="section-kicker">01 / POPULARNO SADA</div><h2 id="popular-title">PAROVI KOJI<br><em>PRIVLAČE POGLEDE.</em></h2></div><p>Izdvojeno prema aktuelnosti modela, dostupnosti, broju BiH ponuda i sniženjima u katalogu.</p></div>
    <div class="popular-grid">
    @foreach($popularProducts as $popular)
        @php $popularOffers=$popular->offers;$popularBest=$popularOffers->sortBy('price')->first();$popularImageOffer=$popularOffers->firstWhere('image_url','!=',null);$popularImage=$popularImageOffer?->image_url;$popularSale=$popularBest?->old_price&&$popularBest->old_price>$popularBest->price; @endphp
        <a class="popular-card" href="{{route('product.show',$popular->slug)}}">
            <div class="popular-rank"><span>TOP</span><strong>{{str_pad((string)$loop->iteration,2,'0',STR_PAD_LEFT)}}</strong></div>
            <div class="popular-photo source-{{$popularImageOffer?->store?->slug}}">@if($popularImage)<img loading="lazy" src="{{$popularImage}}" alt="{{$popular->name}}">@else<span class="image-fallback">FOTO<br>USKORO</span>@endif @if($popularSale)<b>-{{round((1-$popularBest->price/$popularBest->old_price)*100)}}%</b>@endif</div>
            <div class="popular-info"><span>{{$popular->brand}}</span><h3>{{$popular->model ?: $popular->name}}</h3><div><strong>{{number_format((float)$popular->display_min_price,2,',','.')}} KM</strong><small>{{$popularOffers->pluck('store_id')->unique()->count()}} BIH {{$popularOffers->pluck('store_id')->unique()->count()===1?'TRGOVINA':'TRGOVINE'}}</small></div></div><i>↗</i>
        </a>
    @endforeach
    </div>
</section>
<section class="ticker" aria-label="Brendovi u katalogu"><div>@foreach($filters['brands'] as $brand) {{Str::upper($brand)}} ✦ @endforeach</div></section>
<section class="shell brand-zone" id="brendovi"><div class="section-kicker">02 / BIRAJ PO BRENDU</div><div class="brand-strip">
    <a class="brand-pill {{!request('brand')?'active':''}}" href="/catalog">SVI BRENDOVI <span>↗</span></a>
    @foreach($filters['brands'] as $brand)<a class="brand-pill {{request('brand')===$brand?'active':''}}" href="/catalog?brand={{urlencode($brand)}}">{{Str::upper($brand)}} <span>↗</span></a>@endforeach
</div></section>
<section class="shell catalog-layout" id="rezultati">
<aside id="catalog-filters" class="filter-panel" tabindex="-1"><button class="filter-close" type="button" data-filter-close aria-label="Zatvori filtere">Zatvori ×</button><form class="filters" action="/catalog#rezultati" method="get">
    <div class="filters-title"><span>FILTERI</span><b>{{collect(request()->except('sort','page'))->filter()->count()}}</b></div>
    <div class="field"><label for="filter-brand">Brend</label><select id="filter-brand" name="brand"><option value="">Svi brendovi</option>@foreach($filters['brands'] as $brand)<option @selected(request('brand')===$brand) value="{{$brand}}">{{Str::title($brand)}}</option>@endforeach</select></div>
    <div class="field"><label for="filter-model">Model</label><input id="filter-model" name="model" value="{{request('model')}}" placeholder="npr. Air Max"></div>
    <div class="field"><label for="filter-size">EU veličina</label><select id="filter-size" name="size"><option value="">Sve veličine</option>@foreach($filters['sizes'] as $size)<option @selected(request('size')===$size) value="{{$size}}">EU {{$size}}</option>@endforeach</select></div>
    <div class="field"><label for="filter-gender">Za koga</label><select id="filter-gender" name="gender"><option value="">Svi</option><option @selected(request('gender')==='male') value="male">Muškarci</option><option @selected(request('gender')==='female') value="female">Žene</option><option @selected(request('gender')==='kids') value="kids">Djeca</option><option @selected(request('gender')==='unisex') value="unisex">Unisex</option></select></div>
    <div class="field"><label for="filter-store">BiH trgovina</label><select id="filter-store" name="store"><option value="">Sve trgovine</option>@foreach($filters['stores'] as $store)<option @selected(request('store')===$store->slug) value="{{$store->slug}}">{{$store->name}}</option>@endforeach</select></div>
    <div class="field"><label>Cijena u KM</label><div class="two"><input type="number" min="0" step="0.01" aria-label="Minimalna cijena u KM" name="price_min" value="{{request('price_min')}}" placeholder="OD"><input type="number" min="0" step="0.01" aria-label="Maksimalna cijena u KM" name="price_max" value="{{request('price_max')}}" placeholder="DO"></div></div>
    @if($filters['colors']->isNotEmpty())<div class="field"><label for="filter-color">Boja</label><select id="filter-color" name="color"><option value="">Sve boje</option>@foreach($filters['colors'] as $color)<option @selected(request('color')===$color) value="{{$color}}">{{$color}}</option>@endforeach</select></div>@endif
    @if(request()->boolean('personal'))<input type="hidden" name="personal" value="1">@endif<input type="hidden" name="sort" value="{{request('sort','price_asc')}}"><label class="check"><input type="checkbox" name="available" value="1" @checked(request('available'))><span></span> Samo dostupno</label><label class="check"><input type="checkbox" name="sale" value="1" @checked(request('sale'))><span></span> Samo sniženo</label>
    <button class="apply">PRIKAŽI REZULTATE <span>↗</span></button><a class="reset" href="/catalog">Očisti sve filtere</a>
</form></aside>
<div class="filter-backdrop" data-filter-backdrop hidden></div>
<div class="catalog-results"><button class="mobile-filter-toggle tool-button" type="button" data-filter-open aria-controls="catalog-filters" aria-expanded="false">Filteri i moj broj <b>☷</b></button>
    <div class="results-head"><div><div class="section-kicker">03 / NAJBOLJE PONUDE</div><h2>{{request('brand')?Str::upper(request('brand')):'SVE PATIKE'}}</h2><p><strong>{{$products->total()}}</strong> rezultata iz domaćih trgovina</p></div><form class="sort-form" action="/catalog#rezultati">@foreach(request()->except('sort','page') as $key => $value) @if(is_scalar($value))<input type="hidden" name="{{$key}}" value="{{$value}}">@endif @endforeach<label for="sort">SORTIRAJ</label><select id="sort" class="sort" name="sort" onchange="this.form.submit()"><option @selected(request('sort','price_asc')==='price_asc') value="price_asc">Najniža cijena</option><option @selected(request('sort')==='price_desc') value="price_desc">Najviša cijena</option><option @selected(request('sort')==='discount') value="discount">Najveći popust</option><option @selected(request('sort')==='stores') value="stores">Najviše trgovina</option><option @selected(request('sort')==='name') value="name">Naziv A–Z</option></select></form></div>
    @php
        $filterLabels = ['brand'=>'Brend', 'model'=>'Model', 'size'=>'EU veličina', 'gender'=>'Za koga', 'store'=>'Trgovina', 'price_min'=>'Cijena od', 'price_max'=>'Cijena do', 'color'=>'Boja', 'available'=>'Samo dostupno', 'sale'=>'Samo sniženo', 'personal'=>'Moji parovi'];
        $activeFilters = collect(request()->only(array_keys($filterLabels)))->filter(fn ($value) => is_scalar($value) && (string) $value !== '');
    @endphp
    @if($activeFilters->isNotEmpty())
    <nav class="active-filters" aria-label="Aktivni filteri">
    @foreach($activeFilters as $key => $value)
        @php
            $remaining = request()->except($key, 'page', 'q');
            if (request()->boolean('personal') && in_array($key, ['size','price_max'])) $remaining[$key] = '';
            $displayValue = match($key) {
                'gender' => ['male'=>'Muškarci','female'=>'Žene','kids'=>'Djeca','unisex'=>'Unisex'][$value] ?? $value,
                'store' => $filters['stores']->firstWhere('slug', $value)?->name ?? $value,
                'price_min', 'price_max' => $value.' KM',
                'available', 'sale', 'personal' => '',
                default => $value,
            };
        @endphp
        <a href="{{route('catalog', $remaining)}}#rezultati" aria-label="Ukloni filter: {{$filterLabels[$key]}} {{$displayValue}}">{{$filterLabels[$key]}}@if($displayValue !== ''): {{$displayValue}}@endif <span aria-hidden="true">×</span></a>
    @endforeach
        <a class="clear-filters" href="{{route('catalog')}}#rezultati">Očisti sve</a>
    </nav>
    @endif
    <div class="grid">
    @forelse($products as $product)
        @php $offers=$product->offers;$cardPrice=fn($offer)=>request('size')?(float)($offer->variants->first(fn($v)=>$v->size==request('size')&&$v->availability==='in_stock')->price??$offer->price):(float)$offer->price;$best=$offers->sortBy($cardPrice)->first();$imageOffer=$offers->whereNotNull('image_url')->sortBy('price')->first();$sizes=$offers->flatMap->variants->where('availability','in_stock')->pluck('size')->unique()->sortBy(fn($s)=>(float)$s);$sale=$best?->old_price&&$best->old_price>$cardPrice($best);$storeCount=$offers->pluck('store_id')->unique()->count(); @endphp
        <article class="product-card">
            @include('partials.shopping-actions')
            <a class="image-wrap source-{{$imageOffer?->store?->slug}}" href="{{route('product.show',$product->slug)}}"><span class="card-index">#{{str_pad((string)(($products->currentPage()-1)*$products->perPage()+$loop->iteration),2,'0',STR_PAD_LEFT)}}</span>@if($sale)<span class="badge">-{{round((1-$cardPrice($best)/$best->old_price)*100)}}%</span>@endif<span class="store-badge">{{$storeCount}} {{$storeCount===1?'TRGOVINA':'TRGOVINE'}}</span>@if($imageOffer)<img loading="lazy" decoding="async" src="{{$imageOffer->image_url}}" alt="{{$product->name}}">@else<span class="image-fallback">FOTO<br>USKORO</span>@endif</a>
            <div class="card-body"><div class="card-brand"><span>{{$product->brand}}</span><i>{{match($product->gender){'male'=>'M','female'=>'Ž','kids'=>'D',default=>'U'} }}</i></div><h3>{{$product->model ?: $product->name}}</h3><div class="price-row"><span class="from">OD</span><span class="price">{{number_format((float)$product->display_min_price,2,',','.')}}</span><span class="currency">KM</span>@if($sale)<span class="old-price">{{number_format((float)$best->old_price,2,',','.')}}</span>@endif</div><div class="size-label">DOSTUPNE VELIČINE</div><div class="size-row">@forelse($sizes->take(6) as $size)<span>{{$size}}</span>@empty<span>Provjeri ponudu</span>@endforelse @if($sizes->count()>6)<span>+{{$sizes->count()-6}}</span>@endif</div><div class="card-stores">{{$offers->pluck('store.name')->unique()->take(2)->join(' · ')}}</div><a class="card-link" href="{{route('product.show',$product->slug)}}{{request('size')?'?size='.urlencode(request('size')):''}}"><span>UPOREDI PONUDE</span><b>↗</b></a></div>
        </article>
    @empty
        <div class="empty"><span>404 / NEMA PARA</span><h3>Nema rezultata.</h3><p>Promijeni veličinu, brend ili raspon cijene pa pokušaj ponovo.</p><a href="/catalog">PRIKAŽI SVE PATIKE ↗</a></div>
    @endforelse
    </div>
    @if($products->hasPages())<nav class="pager" aria-label="Stranice">@if($products->onFirstPage())<span>← PRETHODNA</span>@else<a href="{{$products->previousPageUrl()}}">← PRETHODNA</a>@endif<strong>{{$products->currentPage()}} / {{$products->lastPage()}}</strong>@if($products->hasMorePages())<a href="{{$products->nextPageUrl()}}">SLJEDEĆA →</a>@endif</nav>@endif
</div>
</section>
<section class="shell store-proof" id="trgovine"><div><div class="section-kicker">04 / PROVJERENI IZVORI</div><h2>SAMO DOMAĆI<br>SHOPPING TEREN.</h2></div><div class="store-proof-copy"><p>Ne miješamo cijene, valute ni dostavu iz drugih država. Svaka ponuda u rezultatima vodi na trgovinu koja posluje u Bosni i Hercegovini.</p><div class="store-list">@foreach($filters['stores'] as $store)<span><i>✓</i>{{$store->name}}</span>@endforeach</div></div></section>
</main>
<footer><div class="shell"><a class="brandmark footer-brand" href="/catalog"><i>A</i><span>AErchi<em>.ba</em></span></a><p>Pronađi bolje. Plati manje. Kupi u BiH.</p><span>© {{date('Y')}} AErchi.ba</span></div></footer>
</body>
</html>