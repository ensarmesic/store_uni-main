<!doctype html>
<html lang="bs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Analitika cijena, brendova i ponuda patika u provjerenim BiH trgovinama.">
    <title>Analitika tržišta patika — AErchi.ba</title>
    <link rel="stylesheet" href="/css/catalog.css?v=6">
</head>
<body>
@php
    $overview=$analytics['overview'];
    $maxStoreOffers=max(1,(int)$analytics['stores']->max('offers'));
    $maxBrandOffers=max(1,(int)$analytics['brands']->max('offers'));
    $maxBucket=max(1,(int)$analytics['price_buckets']->max('total'));
    $maxSize=max(1,(int)$analytics['sizes']->max('total'));
    $genderLabels=['male'=>'MUŠKARCI','female'=>'ŽENE','kids'=>'DJECA','unisex'=>'UNISEX','unknown'=>'NIJE OZNAČENO'];
@endphp
<div class="topbar"><span class="live-dot"></span> LIVE PRESJEK TRŽIŠTA · {{number_format($overview['offers'],0,',','.')}} STVARNIH BIH PONUDA</div>
<header class="header"><div class="shell header-inner">
    <a class="brandmark" href="/catalog"><i>A</i><span>AErchi<em>.ba</em></span></a>
    <nav class="nav" aria-label="Glavna navigacija"><a href="/catalog">Patike</a><a href="/deals">Akcije</a><a href="/catalog#brendovi">Brendovi</a><a class="active" href="/analytics">Analitika</a></nav>
    <a class="header-cta" href="/catalog">OTVORI KATALOG <span>↗</span></a>
</div></header>

<main>
<section class="shell analytics-hero">
    <div class="analytics-hero-copy">
        <div class="eyebrow"><span>TRŽIŠTE U BROJEVIMA</span> / AŽURIRANI PODACI IZ BIH</div>
        <h1>CIJENE BEZ<br><em>MAGLE.</em></h1>
        <p>Stvarni presjek ponude patika u domaćim trgovinama: ko ima najveći izbor, gdje su cijene najniže i koliko se zaista može uštedjeti.</p>
    </div>
    <div class="analytics-pulse">
        <span>PROSJEČNA CIJENA</span>
        <strong>{{number_format($overview['avg_price'],2,',','.')}}</strong>
        <b>KM</b>
        <small>RASPON {{number_format($overview['min_price'],0,',','.')}}—{{number_format($overview['max_price'],0,',','.')}} KM</small>
    </div>
</section>

<section class="shell metric-grid" aria-label="Ključna statistika">
    <article><span>01 / MODELI</span><strong>{{number_format($overview['products'],0,',','.')}}</strong><small>JEDINSTVENIH PAROVA</small></article>
    <article><span>02 / PONUDE</span><strong>{{number_format($overview['offers'],0,',','.')}}</strong><small>AKTIVNIH CIJENA</small></article>
    <article><span>03 / BRENDOVI</span><strong>{{number_format($overview['brands'],0,',','.')}}</strong><small>BEZ PRESKAKANJA</small></article>
    <article><span>04 / AKCIJE</span><strong>{{number_format($overview['sale_offers'],0,',','.')}}</strong><small>PROSJEČNO −{{number_format($overview['avg_discount'],0)}}%</small></article>
</section>

<section class="shell analytics-section">
    <div class="analytics-title"><div><div class="section-kicker">01 / PRODAVNICE</div><h2>KO DRŽI<br><em>NAJVEĆI IZBOR?</em></h2></div><p>Broj aktivnih ponuda, modela i brendova u svakoj provjerenoj BiH trgovini.</p></div>
    <div class="store-ranking">
        @foreach($analytics['stores'] as $store)
        <article class="rank-row">
            <div class="rank-number">{{str_pad((string)$loop->iteration,2,'0',STR_PAD_LEFT)}}</div>
            <div class="rank-store"><strong>{{$store->name}}</strong><span>{{$store->brands}} BRENDOVA · {{$store->models}} MODELA</span></div>
            <div class="rank-track"><i style="width:{{round($store->offers/$maxStoreOffers*100,1)}}%"></i></div>
            <div class="rank-value"><strong>{{number_format($store->offers,0,',','.')}}</strong><span>PONUDA</span></div>
            <div class="rank-price"><strong>{{number_format((float)$store->avg_price,0,',','.')}} KM</strong><span>PROSJEK · OD {{number_format((float)$store->min_price,0,',','.')}} KM</span></div>
        </article>
        @endforeach
    </div>
</section>

<section class="analytics-dark">
<div class="shell analytics-section">
    <div class="analytics-title light"><div><div class="section-kicker">02 / BRENDOVI</div><h2>VELIKA<br><em>LIGA BRENDOVA.</em></h2></div><p>Najzastupljeniji proizvođači prema broju stvarnih ponuda u domaćim trgovinama.</p></div>
    <div class="brand-chart">
        @foreach($analytics['brands'] as $brand)
        <a href="/catalog?brand={{urlencode($brand->brand)}}" class="brand-bar">
            <div><strong>{{$brand->brand}}</strong><span>{{$brand->models}} MODELA · {{$brand->stores}} {{$brand->stores==1?'TRGOVINA':'TRGOVINE'}}</span></div>
            <div class="brand-track"><i style="width:{{round($brand->offers/$maxBrandOffers*100,1)}}%"></i></div>
            <b>{{number_format($brand->offers,0,',','.')}}</b>
        </a>
        @endforeach
    </div>
</div>
</section>

<section class="shell analytics-split">
    <article class="data-panel">
        <div class="section-kicker">03 / CIJENOVNI SEGMENTI</div><h2>GDJE JE<br>NAVIŠE PONUDA?</h2>
        <div class="bucket-chart">
            @foreach($analytics['price_buckets'] as $bucket)
            <div class="bucket"><span>{{$bucket->bucket}}</span><div><i style="height:{{max(3,round($bucket->total/$maxBucket*100,1))}}%"></i></div><strong>{{number_format($bucket->total,0,',','.')}}</strong></div>
            @endforeach
        </div>
    </article>
    <article class="data-panel blue-panel">
        <div class="section-kicker">04 / KONKURENCIJA</div><h2>ISTA PATIKA.<br>DRUGA CIJENA.</h2>
        <div class="competition-number">{{number_format((int)$analytics['competition']->compared_models,0,',','.')}}</div>
        <p>modela ima ponude iz više trgovina i može se direktno porediti.</p>
        <div class="competition-meta"><span>PROSJEČNA RAZLIKA <b>{{number_format((float)$analytics['competition']->avg_spread,2,',','.')}} KM</b></span><span>NAJVEĆA RAZLIKA <b>{{number_format((float)$analytics['competition']->max_spread,2,',','.')}} KM</b></span></div>
    </article>
</section>

@if($analytics['deals']->isNotEmpty())
<section class="shell analytics-section">
    <div class="analytics-title"><div><div class="section-kicker">05 / NAJVEĆI POPUSTI</div><h2>CIJENE KOJE<br><em>UDARAJU JAKO.</em></h2></div><p>Najveća trenutno evidentirana sniženja iz punog kataloga.</p></div>
    <div class="deal-grid">
        @foreach($analytics['deals'] as $deal)
        <a class="deal-card" href="{{route('product.show',$deal->slug)}}">
            <div class="deal-image">@if($deal->image_url)<img loading="lazy" src="{{$deal->image_url}}" alt="{{$deal->brand}} {{$deal->model}}">@else<span class="image-fallback">FOTO<br>USKORO</span>@endif<span>−{{$deal->discount}}%</span></div>
            <div class="deal-copy"><small>{{$deal->store_name}}</small><strong>{{$deal->brand}} {{$deal->model}}</strong><div><b>{{number_format((float)$deal->price,2,',','.')}} KM</b><del>{{number_format((float)$deal->old_price,2,',','.')}} KM</del></div></div>
        </a>
        @endforeach
    </div>
</section>
@endif

<section class="shell analytics-bottom">
    <article>
        <div class="section-kicker">06 / DOSTUPNE VELIČINE</div><h2>NAJČEŠĆI BROJEVI</h2>
        <div class="size-cloud">@foreach($analytics['sizes'] as $size)<a href="/catalog?size={{urlencode($size->size)}}" style="--weight:{{round($size->total/$maxSize,2)}}"><strong>EU {{$size->size}}</strong><span>{{number_format($size->total,0,',','.')}} PONUDA</span></a>@endforeach</div>
    </article>
    <article>
        <div class="section-kicker">07 / KOME SU NAMIJENJENE</div><h2>STRUKTURA KATALOGA</h2>
        <div class="gender-list">@foreach($analytics['genders'] as $gender)<div><span>{{$genderLabels[$gender->gender]??Str::upper($gender->gender)}}</span><strong>{{number_format($gender->total,0,',','.')}}</strong></div>@endforeach</div>
    </article>
</section>

<section class="shell analytics-cta"><div><span>SPREMAN ZA PRETRAGU?</span><h2>{{number_format($overview['products'],0,',','.')}} MODELA.<br>JEDAN PRAVI PAR.</h2></div><a href="/catalog">PRETRAŽI SVE PATIKE <b>↗</b></a></section>
</main>

<footer><div class="shell"><a class="brandmark footer-brand" href="/catalog"><i>A</i><span>AErchi<em>.ba</em></span></a><p>Podaci umjesto nagađanja. Kupi pametnije u BiH.</p><span>© {{date('Y')}} AErchi.ba</span></div></footer>
</body>
</html>
