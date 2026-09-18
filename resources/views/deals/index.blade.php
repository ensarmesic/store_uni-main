<!doctype html>
<html lang="bs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Najveća aktuelna sniženja patika u provjerenim trgovinama Bosne i Hercegovine.">
    <title>Deal Radar — najbolje akcije patika u BiH | AErchi.ba</title>
    <link rel="stylesheet" href="/css/catalog.css?v=6">
</head>
<body>
<div class="topbar"><span class="live-dot"></span> DEAL RADAR · {{number_format($radar['total'],0,',','.')}} AKTUELNIH SNIŽENJA U BIH</div>
<header class="header"><div class="shell header-inner">
    <a class="brandmark" href="/catalog"><i>A</i><span>AErchi<em>.ba</em></span></a>
    <nav class="nav" aria-label="Glavna navigacija"><a href="/catalog">Patike</a><a class="active" href="/deals">Akcije</a><a href="/catalog#brendovi">Brendovi</a><a href="/analytics">Analitika</a></nav>
    <a class="header-cta" href="#akcije">UHVATI POPUST <span>↘</span></a>
</div></header>

<main>
<section class="shell deal-hero">
    <div class="deal-hero-copy">
        <div class="eyebrow"><span>LIVE / DEAL RADAR</span> · SAMO PROVJERENE BIH TRGOVINE</div>
        <h1>CIJENA<br><em>PADA.</em></h1>
        <p>Najveća stvarna sniženja iz cijelog AErchi kataloga, složena od najjačeg popusta prema dole. Filtriraj ono što ti odgovara i idi direktno u domaću trgovinu.</p>
    </div>
    <div class="radar-visual" aria-label="Najveći popust {{number_format($radar['best'])}} posto">
        <div class="radar-ring"><i></i><b>−{{number_format($radar['best'])}}%</b><span>NAJVEĆI<br>POPUST</span></div>
    </div>
</section>

<section class="shell radar-metrics">
    <article><span>AKTIVNE AKCIJE</span><strong>{{number_format($radar['total'],0,',','.')}}</strong></article>
    <article><span>PROSJEČAN POPUST</span><strong>−{{number_format($radar['average'],0)}}%</strong></article>
    <article><span>UKUPNA RAZLIKA CIJENA</span><strong>{{number_format($radar['savings'],0,',','.')}} <small>KM</small></strong></article>
</section>

<section class="shell deals-layout" id="akcije">
    <aside>
        <form class="deal-filters" method="get">
            <div class="filters-title"><span>PODEŠAVANJE RADARA</span><b>{{collect(request()->except('sort','page'))->filter()->count()}}</b></div>
            <div class="field"><label>Traži model ili brend</label><input name="q" value="{{request('q')}}" placeholder="Samba, Nike, Gel..." /></div>
            <div class="field"><label>Brend</label><select name="brand"><option value="">Svi brendovi</option>@foreach($filters['brands'] as $brand)<option value="{{$brand}}" @selected(request('brand')===$brand)>{{$brand}}</option>@endforeach</select></div>
            <div class="field"><label>BiH trgovina</label><select name="store"><option value="">Sve trgovine</option>@foreach($filters['stores'] as $store)<option value="{{$store->slug}}" @selected(request('store')===$store->slug)>{{$store->name}}</option>@endforeach</select></div>
            <div class="field"><label>EU veličina</label><select name="size"><option value="">Sve veličine</option>@foreach($filters['sizes'] as $size)<option value="{{$size}}" @selected(request('size')==$size)>EU {{$size}}</option>@endforeach</select></div>
            <div class="field"><label>Minimalni popust</label><select name="min_discount"><option value="">Bilo koji</option>@foreach([10,20,30,40,50] as $discount)<option value="{{$discount}}" @selected(request('min_discount')==$discount)>Najmanje {{$discount}}%</option>@endforeach</select></div>
            <div class="field"><label>Najviša cijena</label><input type="number" min="0" name="max_price" value="{{request('max_price')}}" placeholder="npr. 150 KM" /></div>
            <input type="hidden" name="sort" value="{{request('sort','discount')}}">
            <button class="apply">SKENIRAJ PONUDE <span>↗</span></button>
            <a class="reset" href="/deals">Očisti sve filtere</a>
        </form>
    </aside>

    <div class="deal-results">
        <div class="results-head deal-results-head"><div><div class="section-kicker">01 / RADAR REZULTATI</div><h2>PRAVI<br>PADOVI CIJENA.</h2><p><strong>{{$deals->total()}}</strong> sniženih ponuda odgovara filterima</p></div>
            <form class="sort-form">@foreach(request()->except('sort','page') as $key=>$value) @if(is_scalar($value))<input type="hidden" name="{{$key}}" value="{{$value}}">@endif @endforeach<label for="deal-sort">SORTIRAJ</label><select id="deal-sort" class="sort" name="sort" onchange="this.form.submit()"><option value="discount" @selected(request('sort','discount')==='discount')>Najveći popust</option><option value="saving" @selected(request('sort')==='saving')>Najveća ušteda u KM</option><option value="price" @selected(request('sort')==='price')>Najniža cijena</option><option value="newest" @selected(request('sort')==='newest')>Najnovije provjereno</option></select></form>
        </div>

        <div class="radar-grid">
        @forelse($deals as $deal)
            <article class="radar-card">
                <a class="radar-photo source-{{$deal->store?->slug}}" href="{{route('product.show',$deal->product->slug)}}">
                    <span class="radar-discount">−{{number_format((float)$deal->discount_percent,0)}}%</span>
                    <span class="radar-store">{{$deal->store->name}}</span>
                    @if($deal->image_url)<img loading="lazy" src="{{$deal->image_url}}" alt="{{$deal->product->name}}">@else<span class="image-fallback">FOTO<br>USKORO</span>@endif
                </a>
                <div class="radar-card-copy">
                    <span class="radar-brand">{{$deal->product->brand}}</span>
                    <h3>{{$deal->product->model ?: $deal->product->name}}</h3>
                    <div class="radar-price"><div><del>{{number_format((float)$deal->old_price,2,',','.')}} KM</del><strong>{{number_format((float)$deal->price,2,',','.')}} <small>KM</small></strong></div><b>ŠTEDI<br>{{number_format((float)$deal->savings,2,',','.')}} KM</b></div>
                    <div class="radar-sizes">@forelse($deal->variants->take(7) as $variant)<span>{{$variant->size}}</span>@empty<em>Veličine provjeri u trgovini</em>@endforelse</div>
                    <a class="radar-link" href="{{$deal->product_url}}" target="_blank" rel="noopener sponsored"><span>OTVORI U {{$deal->store->name}}</span><b>↗</b></a>
                </div>
            </article>
        @empty
            <div class="empty"><span>RADAR / 0 POGODAKA</span><h3>Nema akcija za ove filtere.</h3><p>Probaj manji popust, višu cijenu ili drugu veličinu.</p><a href="/deals">PRIKAŽI SVE AKCIJE ↗</a></div>
        @endforelse
        </div>

        @if($deals->hasPages())<nav class="pager" aria-label="Stranice">@if($deals->onFirstPage())<span>← PRETHODNA</span>@else<a href="{{$deals->previousPageUrl()}}">← PRETHODNA</a>@endif<strong>{{$deals->currentPage()}} / {{$deals->lastPage()}}</strong>@if($deals->hasMorePages())<a href="{{$deals->nextPageUrl()}}">SLJEDEĆA →</a>@endif</nav>@endif
    </div>
</section>
</main>

<footer><div class="shell"><a class="brandmark footer-brand" href="/catalog"><i>A</i><span>AErchi<em>.ba</em></span></a><p>Pronađi bolje. Plati manje. Kupi u BiH.</p><span>© {{date('Y')}} AErchi.ba</span></div></footer>
</body>
</html>
