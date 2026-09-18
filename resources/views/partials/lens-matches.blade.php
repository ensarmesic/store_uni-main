@if($result['visual'] ?? false)<p class="field-help">Vizuelni indeks sadrži {{$result['indexed']}} modela. Lista prikazuje sličnost, ne vjerovatnoću tačnog prepoznavanja.</p>@endif
@foreach($result['matches'] as $match)
@php($candidateDecision = $decisions[$match->id])
<article class="saved-card">
@if($result['visual'] ?? false)
    @php($candidateImage = $match->offers->firstWhere('image_url','!=',null)?->image_url)
    @if($candidateImage)<img src="{{$candidateImage}}" alt="{{$match->name}}" width="160" height="120" style="object-fit:contain" loading="lazy">@endif
@endif
<a class="scan-match" href="{{route('product.show',['slug'=>$match->slug,'size'=>$candidateDecision['size']])}}"><div><strong>{{$match->name}}</strong><small>{{$match->brand}} · {{$match->mpn}} · {{$candidateDecision['size'] ? 'EU '.$candidateDecision['size'] : 'Odaberi svoj broj'}}</small></div><b>↗</b></a>
<p>{{$candidateDecision['title']}} @if($candidateDecision['size'] && $candidateDecision['current']) · {{number_format($candidateDecision['current'],2,',','.')}} KM @endif</p>
@if($candidateDecision['fit'])<p class="field-help">{{$candidateDecision['fit']['reason']}}</p>@endif
<a class="tool-link" href="{{route('product.show',['slug'=>$match->slug,'size'=>$candidateDecision['size']])}}">{{$result['visual'] ?? false ? 'Provjeri da li je ovo tvoj model' : 'Otvori model'}} → ponude i odluka</a>
</article>
@endforeach
