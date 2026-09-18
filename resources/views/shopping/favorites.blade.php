@extends('layouts.workspace')
@section('title','Moja lista')
@section('content')
<div class="tool-heading"><div><div class="section-kicker">FAVORITI / TVOJ UŽI IZBOR</div><h1>Dobri parovi.<br><em>Na tvojoj listi.</em></h1><p>Sačuvaj ono što ti zapadne za oko, uporedi modele ili uključi praćenje cijene.</p></div><a class="tool-button" href="{{route('catalog')}}">Pronađi još modela <b>↗</b></a></div>
@unless(session('user_id'))<div class="guest-note"><span>Lista se čuva u ovoj sesiji. Prijavom je poveži sa svojim računom.</span><a class="tool-link" href="{{route('account')}}">Sačuvaj na profilu ↗</a></div>@endunless
<form class="size-toolbar" action="{{route('favorites')}}"><div class="tool-field"><label for="favorites-size">Cijene za tvoj EU broj</label><input id="favorites-size" name="size" value="{{$size}}" maxlength="20" placeholder="Sve veličine"></div><button class="tool-button" type="submit">Provjeri <b>↗</b></button><span>{{$products->total()}} sačuvanih modela</span></form>
<div class="favorites-grid">
@forelse($products as $product)
    @php($choices = $offers[$product->id])
    @php($best = $choices->first())
    @php($photo = $product->offers->where('is_active',true)->firstWhere('image_url','!=',null)?->image_url)
    <article class="favorite-card"><a class="favorite-photo" href="{{route('product.show',['slug'=>$product->slug,'size'=>$size])}}">@if($photo)<img src="{{$photo}}" alt="{{$product->name}}" loading="lazy">@else<span>Fotografija uskoro</span>@endif</a><div class="favorite-copy"><span class="mini-label">{{$product->brand}}</span><h2>{{$product->model ?: $product->name}}</h2><strong class="favorite-price">{{$best?number_format($best['price'],2,',','.').' KM':'Trenutno nedostupno'}}</strong><p class="field-help">{{$size?'EU '.$size.' · ':''}}{{$choices->pluck('offer.store_id')->unique()->count()}} trgovina sa dostupnom ponudom</p>
    @include('partials.shopping-actions',['favoriteIds'=>$products->pluck('id')->all()])
    <a class="tool-link" href="{{route('product.show',['slug'=>$product->slug,'size'=>$size])}}#ponude">Pogledaj ponude ↗</a>
    <details class="manual-entry"><summary>Prati pad cijene ↘</summary><form class="tool-form" action="{{route('alerts.store',$product)}}" method="post">@csrf<input type="hidden" name="type" value="price"><input type="hidden" name="size" value="{{$size}}"><div class="tool-field"><label for="favorite-price-{{$product->id}}">Tvoj budžet u KM</label><input id="favorite-price-{{$product->id}}" type="number" name="target_price" min="0.01" step="0.01" placeholder="npr. 150" required></div><button class="tool-button" type="submit">Uključi praćenje <b>↘</b></button></form></details>
    </div></article>
@empty<div class="empty-tool"><span class="empty-symbol">♡</span><h2>Ovdje počinje tvoj uži izbor.</h2><p>Dodirni srce uz model u katalogu. Sačuvani parovi će te čekati ovdje.</p><a class="tool-button tool-button-blue" href="{{route('catalog')}}">Istraži katalog <b>↗</b></a></div>@endforelse
</div>
@if($products->hasPages())<nav class="pager" aria-label="Stranice favorita">@if($products->previousPageUrl())<a href="{{$products->previousPageUrl()}}">← Prethodna</a>@endif<strong>{{$products->currentPage()}} / {{$products->lastPage()}}</strong>@if($products->nextPageUrl())<a href="{{$products->nextPageUrl()}}">Sljedeća →</a>@endif</nav>@endif
@endsection
