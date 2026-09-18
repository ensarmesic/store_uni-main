@php($isFavorite = in_array($product->id, $favoriteIds ?? []))
@php($inComparison = in_array($product->id, session('comparison', [])))
<div class="shopping-actions">
    <form method="post" action="{{route($isFavorite?'favorites.destroy':'favorites.store',$product)}}">@csrf @if($isFavorite) @method('DELETE') @endif<button class="save-button {{$isFavorite?'is-saved':''}}" type="submit" aria-label="{{$isFavorite?'Ukloni iz favorita: ':'Sačuvaj u favorite: '}}{{$product->name}}" aria-pressed="{{$isFavorite?'true':'false'}}"><span aria-hidden="true">{{$isFavorite?'♥':'♡'}}</span> {{$isFavorite?'Sačuvano':'Sačuvaj'}}</button></form>
    <form method="post" action="{{route($inComparison?'compare.destroy':'compare.store',$product)}}">@csrf @if($inComparison) @method('DELETE') @endif<button class="compare-button" type="submit" aria-pressed="{{$inComparison?'true':'false'}}">{{$inComparison?'✓ U poređenju':'⇄ Uporedi'}}</button></form>
</div>
