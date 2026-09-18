@extends('layouts.workspace')
@section('title','AErchi Watch')
@section('content')
<div class="tool-heading"><div><div class="section-kicker">AERCHI WATCH</div><h1>Javi kad<br><em>ima smisla.</em></h1><p>Pratimo cijenu tvog broja, historiju i dostupnost. Obavijest stiže kada cijena bude blizu zabilježenog minimuma i unutar tvog budžeta.</p></div></div>
@if($lastCheck = \Illuminate\Support\Facades\Cache::get('watch:last_checked'))<p class="field-help">Posljednja provjera: {{\Carbon\Carbon::parse($lastCheck)->format('d.m.Y. H:i')}}</p>@endif
<p class="guest-note">Obavijesti su ovdje sačuvane. Email se šalje samo na potvrđenu adresu uz podešenu email dostavu. Ponavljamo signal tek nakon promjene uslova i najmanje 24 sata.</p>
<div class="tool-columns"><section class="saved-list"><h2>Obavijesti</h2>
@forelse($notifications as $notification)<article class="saved-card"><span class="mini-label">{{$notification->read_at?'PROČITANO':'NOVO'}} · {{$notification->created_at->format('d.m.Y. H:i')}}</span><h3>{{$notification->payload['product_name']}} · EU {{$notification->payload['size']}}</h3><strong>{{$notification->payload['title']}} · {{number_format($notification->payload['price'],2,',','.')}} KM</strong><p>{{$notification->payload['reason']}}</p><a class="tool-link" href="{{$notification->payload['url']}}">Provjeri trenutnu ponudu ↗</a>@unless($notification->read_at)<form method="post" action="{{route('watch.read',$notification)}}">@csrf<button class="delete-button">Označi pročitano</button></form>@endunless</article>
@empty<div class="empty-tool"><h2>Još nema novog signala.</h2><p>Dodaj Watch na stranici modela. Ako historija još nije dovoljna, nastavljamo prikupljati podatke.</p></div>@endforelse
{{$notifications->links()}}</section><section class="saved-list"><h2>Tvoja praćenja</h2>
@forelse($watches as $watch)<article class="saved-card"><h3><a href="{{route('product.show',['slug'=>$watch->product->slug,'size'=>$watch->size])}}">{{$watch->product->name}}</a></h3><p>EU {{$watch->size}} · {{$watch->budget ? 'Do '.number_format($watch->budget,2,',','.').' KM' : 'Bez gornje granice'}} · {{$watch->is_active?'Aktivno':'Ugašeno'}}</p><small>{{$watch->email ? ($watch->email_verified_at?'Email potvrđen':'Email čeka potvrdu') : 'Obavijesti u aplikaciji'}}</small>@if($watch->is_active)<form method="post" action="{{route('watch.destroy',$watch)}}">@csrf @method('DELETE')<button class="delete-button">Ugasi Watch</button></form>@endif</article>@empty<p>Nema aktivnih praćenja. Odaberi model u katalogu.</p>@endforelse
</section></div>
@endsection
