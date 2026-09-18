<!doctype html>
<html lang="bs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Uporedi cijene za {{$product->name}} u BiH trgovinama.">
    <title>{{$product->name}} — AErchi.ba</title>
    <link rel="stylesheet" href="/css/catalog.css?v=10">
    <link rel="stylesheet" href="/css/workspace.css?v=1">
    <script src="/js/workspace.js?v=1" defer></script>
    <link rel="stylesheet" href="/css/shopping.css?v=1">
    <link rel="stylesheet" href="/css/intelligence.css?v=1">
</head>
<body class="product-detail-page">
<div class="topbar"><span class="live-dot"></span> Kupovina se završava direktno kod provjerenog BiH trgovca</div>
<header class="header"><div class="shell header-inner"><a class="brandmark" href="/catalog"><i>A</i><span>AErchi<em>.ba</em></span></a><nav class="nav"><a href="/catalog">Katalog</a><a href="/fit-passport">Fit DNA</a><a href="/deals">Akcije</a><a class="active" href="/catalog?brand={{urlencode($product->brand)}}">{{$product->brand}}</a><a href="/analytics">Analitika</a></nav><a class="header-cta" href="#ponude">Vidi ponude <span>↘</span></a></div></header>
@php
    $size=request('size');
    $eligible=$selectedOffers->pluck('offer');
    $priceForSize=fn($offer)=>trim((string)$size)!=='' ? (float)($offer->variants->first(fn($variant)=>$variant->size==$size&&$variant->availability==='in_stock')->price ?? $offer->price) : (float)$offer->price;
    $best=$eligible->sortBy($priceForSize)->first();
    $imageOffer=$product->offers->whereNotNull('image_url')->sortBy('price')->first();
    $image=$imageOffer?->image_url;
    $allSizes=$product->offers->flatMap->variants->pluck('size')->unique()->sortBy(fn($s)=>(float)$s);
@endphp
@include('partials.shopping-nav')
<main class="shell detail">
    @include('partials.feedback')
    <a class="back" href="{{url()->previous()}}">← NAZAD NA KATALOG</a>
    <section class="product-hero">
        <div class="product-photo source-{{$imageOffer?->store?->slug}}">@if($image)<img decoding="async" src="{{$image}}" alt="{{$product->name}}">@else<span class="image-fallback">FOTO<br>USKORO</span>@endif</div>
        <div class="product-info">
            <div class="section-kicker">{{$product->brand}} / {{$product->category ?: 'PATIKE'}}</div>
            <h1>{{$product->model ?: $product->name}}</h1>
            @if($product->offers->first()?->description)<p style="color:var(--muted);line-height:1.55">{{$product->offers->first()->description}}</p>@endif
            <div class="best-price">{{$best?number_format($priceForSize($best),2,',','.').' KM':'Nije dostupno'}}</div>
            <div class="meta">@if($product->gender)<span>{{match($product->gender){'male'=>'MUŠKARCI','female'=>'ŽENE','kids'=>'DJECA',default=>'UNISEX'} }}</span>@endif @if($product->color)<span>{{$product->color}}</span>@endif<span>{{$product->offers->pluck('store_id')->unique()->count()}} BIH TRGOVINE</span><span>{{$allSizes->count()}} VELIČINA</span></div>
            <form id="size-picker" class="size-toolbar product-size-picker" method="get">
    <div class="tool-field"><label for="product-size">Provjeri svoj EU broj</label><input id="product-size" name="size" list="available-product-sizes" value="{{$size}}" maxlength="20" placeholder="Sve veličine"><datalist id="available-product-sizes">@foreach($allSizes as $availableSize)<option value="{{$availableSize}}">@endforeach</datalist></div>
    <input type="hidden" name="days" value="{{request('days',90)}}">
    <button class="tool-button" type="submit">Provjeri <b>↗</b></button>
</form>
            @include('partials.shopping-actions')
            @include('partials.product-tools')
        </div>
    </section>

    <h2 class="section-title" id="ponude">PONUDE BIH TRGOVINA</h2>
    @include('partials.decision-card')
    <div class="offer-list">
    @foreach($product->offers as $offer)
        @php $hasSize=!$size||$offer->variants->contains(fn($v)=>$v->size==$size&&$v->availability==='in_stock');$offerSizes=$offer->variants->where('availability','in_stock')->pluck('size')->sortBy(fn($s)=>(float)$s); @endphp
        @php($totalCost = app(\App\Services\TotalCost::class)->for($offer,$priceForSize($offer)))
        <div class="offer-card" style="{{$size&&!$hasSize?'opacity:.45':''}}"><div class="merchant">{{$offer->store->name}}<small>{{$hasSize?'✓ Dostupno':'Nije dostupno'.($size?' u EU '.$size:'')}}</small></div><div class="offer-price">{{number_format($priceForSize($offer),2,',','.')}} {{$offer->currency}}@if($offer->old_price>$offer->price)<del>{{number_format((float)$offer->old_price,2,',','.')}} KM</del>@endif</div><div class="offer-sizes">@include('partials.total-cost')@foreach($offerSizes->take(10) as $offerSize)<span style="{{$size==$offerSize?'background:var(--acid)':''}}">{{$offerSize}}</span>@endforeach @if($offerSizes->count()>10)<span>+{{$offerSizes->count()-10}}</span>@endif</div><a class="merchant-link" target="_blank" rel="nofollow sponsored noopener" href="{{route('offers.visit',$offer)}}">U TRGOVINU ↗</a></div>
    @endforeach
    </div>

    @include('partials.price-history')
    @include('partials.purchase-feedback')
</main>
<footer><div class="shell"><a class="brandmark footer-brand" href="/catalog"><i>A</i><span>AErchi<em>.ba</em></span></a><p>Pronađi bolje. Plati manje. Kupi u BiH.</p><span>© {{date('Y')}} AErchi.ba</span></div></footer>
<div class="mobile-buy-bar">
    <div><small>{{$size?'TVOJ BROJ · EU '.$size:'ODABERI SVOJ EU BROJ'}}</small><strong>{{$size&&$best?number_format($priceForSize($best),2,',','.').' KM':($size?'Broj nije dostupan':'Provjeri dostupnost')}}</strong></div>
    @if($size && $best)<a class="tool-button" href="{{route('offers.visit',$best)}}" target="_blank" rel="nofollow sponsored noopener">Najbolja ponuda <b>↗</b></a>@else<a class="tool-button" href="#size-picker">{{$size?'Promijeni broj':'Odaberi broj'}} <b>↗</b></a>@endif
</div>
</body>
</html>
