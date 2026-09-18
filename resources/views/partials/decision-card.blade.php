<section class="decision-card" aria-labelledby="decision-heading">
    <div class="section-kicker">AERCHI ODLUKA @if($decision['size']) / EU {{$decision['size']}} @endif</div>
    <h2 id="decision-heading">{{$decision['title']}}</h2>
    <p>{{$decision['reason']}}</p>
    <div class="decision-grid">
        <div><span>FIT DNA</span>
            @if($fit)<strong>EU {{$fit['size']}}</strong><p>{{$fit['reason']}}</p>
                <small>Jačina dokaza: {{$fit['confidence']}}/100 · {{$fit['samples']}} profila</small>
                @if(($fit['suitable'] ?? false) && $size !== $fit['size'])<a class="tool-link" href="{{route('product.show',['slug'=>$product->slug,'size'=>$fit['size']])}}">Provjeri predloženi broj ↗</a>@endif
            @else<strong>Dodaj par koji već nosiš</strong><p>Još nemamo lični oslonac za preporuku broja.</p>@endif
            <a class="tool-link" href="{{route('fit-passport')}}">Moj Fit DNA ↗</a>
        </div>
        <div><span>CIJENA TVOG BROJA</span><strong>{{$decision['current'] !== null ? number_format($decision['current'],2,',','.').' KM' : 'Nema svježe ponude'}}</strong>
            @if($decision['aboveLow'] !== null)<p>{{max(0,$decision['aboveLow'])}}% iznad zabilježenog minimuma: {{number_format($decision['prices']['low_90'],2,',','.')}} KM.</p>@else<p>Historija ovog broja se tek prikuplja.</p>@endif
            <small>{{$decision['prices']['observed_days_90']}} dana s cijenom u posljednjih 90 dana. Dostava nije uključena.</small>
        </div>
        <div><span>STOCK PRESSURE</span><strong>{{$decision['stock']['score'] !== null ? $decision['stock']['score'].'/100 · '.$decision['stock']['label'] : 'Nema dovoljno historije'}}</strong>
            @if($size)<p>EU {{$size}}: {{$decision['stock']['current_stores']}} od {{$decision['stock']['total_stores']}} svježe provjerenih trgovina.</p>@endif
            @foreach($decision['stock']['periods'] as $days => $period)
                @if($period['known_stores'] > 0)<small>Prije {{$days}} dana: {{$period['before']}} → danas {{$period['now']}}. Upoređeno {{$period['known_stores']}} trgovina.</small>@endif
            @endforeach
            @if($decision['stock']['stale_stores'])<small>Izostavljeno zbog stare provjere: {{$decision['stock']['stale_stores']}} trgovina.</small>@endif
        </div>
    </div>
    <details><summary>Kako nastaje odluka?</summary><p>Koristimo cijene odabrane veličine i ponude provjerene u posljednjih 48 sati. Stock Pressure je neto pad broja dostupnih trgovina tokom sedam dana, uz najmanje dvije dostupne trgovine na početku i poznato stanje na oba datuma. Ne predstavlja broj preostalih pari niti vjerovatnoću rasprodaje. Fit ocjena je jačina dokaza, a ne kalibrisana vjerovatnoća. Podaci mogu kasniti za trgovinom.</p></details>
    <details class="manual-entry"><summary>AErchi Watch — javi kad bude dobar trenutak</summary>
        <form class="tool-form" method="post" action="{{route('watch.store',$product)}}">@csrf
            <div class="inline-fields"><div class="tool-field"><label for="watch-size">EU broj</label><input id="watch-size" name="size" required maxlength="20" value="{{old('size',$size ?? (($fit['suitable'] ?? false) ? $fit['size'] : null))}}"></div>
            <div class="tool-field"><label for="watch-budget">Budžet u KM (opcionalno)</label><input id="watch-budget" name="budget" type="number" min="1" max="10000" step=".01" value="{{old('budget')}}"></div></div>
            <div class="tool-field"><label for="watch-email">Email (opcionalno)</label><input id="watch-email" name="email" type="email" maxlength="254" value="{{old('email')}}"><small>Bez emaila obavijesti pratiš u aplikaciji. Email prvo potvrđuješ preko poslanog linka.</small></div>
            <button class="tool-button" type="submit">Uključi Watch ↗</button>
        </form>
    </details>
</section>
