<section class="chart history-panel" id="historija">
    <div class="history-heading"><div><div class="section-kicker">ZABILJEŽENE CIJENE / PO TRGOVINI</div><h2 class="section-title">Historija cijene</h2></div><nav aria-label="Period historije">@foreach([30,90] as $days)<a href="{{route('product.show',['slug'=>$product->slug,'size'=>request('size'),'days'=>$days])}}#historija" @if($historyChart['days']===$days) aria-current="page" @endif>{{$days}} dana</a>@endforeach</nav></div>
    <p class="field-help">Najniža zabilježena cijena modela po trgovini i danu. Historija nije odvojena po veličinama. Praznine znače da za taj dan nema zapisa.</p>
    @if($historyChart['lastChecked'])<p class="field-help">Posljednja provjera ponude: {{$historyChart['lastChecked']->format('d.m.Y. H:i')}}.</p>@endif
    @if($historyChart['hasTrend'])
        <div class="history-legend">@foreach($historyChart['series'] as $line)<span><i style="background:{{$line['color']}}"></i>{{$line['name']}}</span>@endforeach</div>
        <svg class="history-svg" viewBox="0 0 800 260" role="img" aria-label="Historija cijena po trgovinama u posljednjih {{$historyChart['days']}} dana">
            <line x1="55" y1="210" x2="770" y2="210" stroke="#c8c7bf"/><line x1="55" y1="30" x2="770" y2="30" stroke="#c8c7bf"/>
            <text x="0" y="35" font-size="11">{{number_format($historyChart['high'],0)}} KM</text><text x="0" y="215" font-size="11">{{number_format($historyChart['low'],0)}} KM</text>
            <text x="55" y="245" font-size="12">{{$historyChart['start']->format('d.m.')}}</text><text x="730" y="245" font-size="12">{{now()->format('d.m.')}}</text>
            @foreach($historyChart['series'] as $line)
                <polyline points="{{$line['points']->map(fn($p)=>$p['x'].','.$p['y'])->join(' ')}}" fill="none" stroke="{{$line['color']}}" stroke-width="3" stroke-dasharray="5 3"/>
                @foreach($line['points'] as $point)<circle cx="{{$point['x']}}" cy="{{$point['y']}}" r="4" fill="{{$line['color']}}"><title>{{$line['name']}} · {{$point['label']}} · {{number_format($point['price'],2,',','.')}} KM</title></circle>@endforeach
            @endforeach
        </svg>
        <p class="field-help">Tačke su stvarni zapisi. Isprekidane linije samo povezuju poznate cijene.</p>
    @else<div class="history-empty"><strong>Još nema dovoljno podataka za trend.</strong><p>Grafikon će se prikazati kada ista trgovina ima zapise iz najmanje dva različita dana u odabranom periodu.</p></div>@endif
    @if($historyChart['series']->isNotEmpty())<details class="manual-entry"><summary>Pogledaj zabilježene cijene</summary><div class="history-table-wrap"><table class="history-table"><thead><tr><th>Trgovina</th><th>Datum</th><th>Cijena</th></tr></thead><tbody>@foreach($historyChart['series'] as $line)@foreach($line['points']->reverse() as $point)<tr><td>{{$line['name']}}</td><td>{{$point['label']}}</td><td>{{number_format($point['price'],2,',','.')}} KM</td></tr>@endforeach @endforeach</tbody></table></div></details>@endif
</section>
