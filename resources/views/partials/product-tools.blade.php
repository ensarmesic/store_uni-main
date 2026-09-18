<div class="product-tools">
    @if($fit)<section class="fit-signal"><div class="section-kicker">TVOJ FIT PASSPORT</div><strong>EU {{$fit['size']}}</strong><p>{{$fit['reason']}}</p><a class="tool-link" href="{{route('fit-passport')}}">Pregledaj svoje veličine ↗</a></section>@endif
    <details class="tool-panel" @if($errors->has('fit')) open @endif><summary>↔ Već nosiš ovaj model? Sačuvaj broj.</summary><p>Zabilježi svoju veličinu i kako ti odgovara. Bit će ti pri ruci kad biraš sljedeći par.</p>
        <form class="tool-form" method="post" action="{{route('fit-passport.store',$product)}}">@csrf
            <div class="tool-field"><label for="passport-size">Tvoja EU veličina</label><input id="passport-size" name="size" list="passport-sizes" value="{{old('size',$size)}}" placeholder="npr. 43 ili 42 2/3" maxlength="20" required><datalist id="passport-sizes">@foreach($allSizes as $availableSize)<option value="{{$availableSize}}">@endforeach</datalist></div>
            <fieldset class="fit-choices tool-field"><legend>Kako ti odgovaraju?</legend><div>@foreach(['tight'=>'Tijesne','just_right'=>'Taman','wide'=>'Široke'] as $value=>$label)<label class="fit-choice"><input type="radio" name="fit" value="{{$value}}" @checked(old('fit','just_right')===$value)><span>{{$label}}</span></label>@endforeach</div></fieldset>
            <button class="tool-button" type="submit">Sačuvaj u moje veličine <b>+</b></button>
        </form>
    </details>
    <details class="tool-panel" open><summary>↘ Prati cijenu ili svoj broj.</summary><p>Zadaj koliko želiš platiti. Rezultate provjeri na stranici <a class="tool-link" href="{{route('alerts')}}">Pratim cijene</a>.</p>
        <form class="tool-form" data-alert-form method="post" action="{{route('alerts.store',$product)}}">@csrf
            <div class="tool-field"><label for="alert-type">Šta želiš pratiti?</label><select id="alert-type" name="type"><option value="price" @selected(old('type')!=='restock')>Pad cijene</option><option value="restock" @selected(old('type')==='restock')>Povratak veličine</option></select></div>
            <div class="inline-fields"><div class="tool-field"><label for="alert-price">Ciljna cijena u KM</label><input id="alert-price" type="number" min="0.01" step="0.01" name="target_price" value="{{old('target_price')}}" placeholder="npr. 150,00"><small class="field-help">Potrebna za praćenje pada cijene.</small></div><div class="tool-field"><label for="alert-size">EU veličina <span class="field-help">(opcionalno)</span></label><input id="alert-size" name="size" list="passport-sizes" maxlength="20" value="{{old('size',$size)}}" placeholder="Sve veličine"></div></div>
            <button class="tool-button tool-button-blue" type="submit">Uključi praćenje <b>↘</b></button>
        </form>
    </details>
</div>
