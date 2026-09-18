<div class="field-help">@if($totalCost['total'] !== null)S dostavom: <strong>{{number_format($totalCost['total'],2,',','.')}} KM</strong> (dostava {{number_format($totalCost['shipping'],2,',','.')}} KM).@elseDostava nije potvrđena — ukupni trošak provjeri u trgovini.@endif
@if($totalCost['pickup_total'] !== null)<br>Besplatno preuzimanje gdje je dostupno: {{number_format($totalCost['pickup_total'],2,',','.')}} KM. {{$totalCost['pickup_note']}}@endif
@if($totalCost['source'])<a href="{{$totalCost['source']}}" target="_blank" rel="noopener nofollow">Uslovi dostave ↗</a>@endif</div>
