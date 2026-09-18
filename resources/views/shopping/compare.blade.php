@extends('layouts.workspace')
@section('title','Poređenje modela')
@section('content')
<div class="tool-heading"><div><div class="section-kicker">DO TRI MODELA / JEDNA ODLUKA</div><h1>Koji je<br><em>tvoj sljedeći par?</em></h1><p>Uporedi cijene, veličine i dostupnost različitih modela. Odaberi svoj broj za preciznije poređenje.</p></div><a class="tool-button" href="{{route('catalog')}}">+ Dodaj iz kataloga</a></div>
@if($shared)<div class="guest-note">Pregledaš podijeljeno poređenje. <a href="{{route('compare')}}">Otvori moje poređenje</a></div>@endif
@if($missingModels)<div class="guest-note">Neki modeli više nisu u katalogu ({{$missingModels}}). Prikazani su preostali modeli.</div>@endif
@if($products->isNotEmpty())
<div class="size-toolbar">
    <div class="tool-field"><label for="comparison-link">Podijeli modele i odabranu veličinu</label><input id="comparison-link" type="text" readonly value="{{$shareUrl}}"></div>
    <button type="button" class="tool-button" data-copy-comparison hidden>Kopiraj link</button>
    <span role="status" data-copy-status></span>
</div>
<p class="field-help">Link prikazuje aktuelne cijene pri otvaranju. Cijene ne uključuju dostavu.</p>
<form class="size-toolbar" action="{{route('compare')}}">@foreach($comparisonQuery['models'] ?? [] as $modelId)<input type="hidden" name="models[]" value="{{$modelId}}">@endforeach<div class="tool-field"><label for="compare-size">EU veličina za sve modele</label><input id="compare-size" name="size" list="compare-sizes" value="{{$size}}" maxlength="20" placeholder="Sve veličine"><datalist id="compare-sizes">@foreach($sizes as $availableSize)<option value="{{$availableSize}}">@endforeach</datalist></div><button class="tool-button" type="submit">Uporedi moj broj <b>⇄</b></button><span>{{$products->count()}} / 3 modela</span></form>
@if($products->count()===1)<div class="guest-note">Dodaj još jedan model iz kataloga ili Moje liste da vidiš razlike.</div>@endif
<p class="field-help">Na telefonu prevuci tabelu lijevo/desno da vidiš sve modele.</p>
<div class="comparison-scroll" tabindex="0" role="region" aria-label="Tabela poređenja modela"><table class="comparison-table"><thead><tr><th scope="col">Tvoj izbor</th>@foreach($products as $product)<th scope="col">@php($image = $product->offers->where('is_active',true)->firstWhere('image_url','!=',null)?->image_url)<a href="{{route('product.show',['slug'=>$product->slug,'size'=>$size])}}">@if($image)<img class="comparison-photo" src="{{$image}}" alt="{{$product->name}}">@endif<span class="mini-label">{{$product->brand}}</span><h2>{{$product->model ?: $product->name}}</h2></a>@unless($shared)<form method="post" action="{{route('compare.destroy',$product)}}">@csrf @method('DELETE')<button class="delete-button" type="submit">Ukloni iz poređenja</button></form>@endunless</th>@endforeach</tr></thead><tbody>
<tr><th scope="row">Najbolja cijena @if($size)<small>EU {{$size}}</small>@endif</th>@foreach($products as $product)@php($best = $offers[$product->id]->first())<td><strong class="compare-price">{{$best?number_format($best['price'],2,',','.').' KM':'Nedostupno'}}</strong>@if($best)<small>{{$best['offer']->store->name}}</small>@endif</td>@endforeach</tr>
<tr><th scope="row">Razlika u cijeni<small>Među odabranim modelima</small></th>@foreach($products as $product)<td>
@if(isset($bestPrices[$product->id]))
@if($bestPrices->count() < 2)<span>Za poređenje cijene potrebna su barem dva dostupna modela.</span>
@elseif($bestPrices[$product->id] === $lowestPrice)<span class="status-pill good">Najpovoljniji izbor</span>
@else<strong>+{{number_format($bestPrices[$product->id] - $lowestPrice,2,',','.')}} KM</strong><small>U odnosu na najpovoljniji model</small>@endif
@else<span>Nema ponude za poređenje</span>@endif
</td>@endforeach</tr>
<tr><th scope="row">Popust najbolje ponude</th>@foreach($products as $product)@php($best = $offers[$product->id]->first())<td>@if($best && $best['offer']->old_price > $best['price'])<span class="status-pill good">−{{round((1-$best['price']/$best['offer']->old_price)*100)}}%</span><small>Ušteda {{number_format($best['offer']->old_price-$best['price'],2,',','.')}} KM</small>@else—@endif</td>@endforeach</tr>
<tr><th scope="row">Dostupne trgovine</th>@foreach($products as $product)<td>{{$offers[$product->id]->pluck('offer.store.name')->unique()->join(', ') ?: 'Nema dostupne ponude'}}</td>@endforeach</tr>
<tr><th scope="row">Dostupni EU brojevi</th>@foreach($products as $product)<td><div class="comparison-sizes">@forelse($product->offers->where('is_active',true)->flatMap->variants->where('availability','in_stock')->pluck('size')->unique()->sortBy(fn($s)=>(float)$s) as $number)<a href="{{route('compare',[...$comparisonQuery,'size'=>$number])}}" @if($size===$number) aria-current="true" @endif>{{$number}}</a>@empty<span>Provjeri u trgovini</span>@endforelse</div></td>@endforeach</tr>
<tr><th scope="row">Posljednja provjera</th>@foreach($products as $product)<td>{{$product->offers->where('is_active',true)->max('last_checked_at')?->format('d.m.Y. H:i') ?? 'Nema podatka'}}</td>@endforeach</tr>
<tr><th scope="row">Sljedeći korak</th>@foreach($products as $product)<td><a class="tool-button" href="{{route('product.show',['slug'=>$product->slug,'size'=>$size])}}#ponude">Sve ponude <b>↗</b></a></td>@endforeach</tr>
</tbody></table></div>
@else<div class="empty-tool"><span class="empty-symbol">⇄</span><h2>Dva dobra para? Uporedi ih.</h2><p>U katalogu ili favoritima odaberi „Uporedi“ uz najviše tri modela.</p><a class="tool-button tool-button-blue" href="{{route('catalog')}}">Odaberi modele <b>↗</b></a></div>@endif
<script src="/js/comparison.js?v=1" defer></script>
@endsection
