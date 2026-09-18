@extends('layouts.workspace')
@section('title','Moje kupovine')
@section('content')
<div class="tool-heading"><div><div class="section-kicker">ISKUSTVO POSLIJE KUPOVINE</div><h1>Kako je<br><em>stvarno odgovaralo?</em></h1><p>Na stranici modela zabilježi broj, fit i da li si par zadržao ili vratio. Podaci su tvoji; zajednica vidi samo zbirne rezultate kada postoje najmanje tri različita profila.</p></div></div>
<div class="saved-list">@forelse($entries as $entry)<article class="saved-card"><h2><a href="{{route('product.show',['slug'=>$entry->product->slug,'size'=>$entry->size])}}">{{$entry->product->name}}</a></h2><p>EU {{$entry->size}} · {{$entry->outcome==='kept'?'Zadržano':'Vraćeno'}} · {{['tight'=>'Tijesne','just_right'=>'Taman','wide'=>'Široke'][$entry->fit]}}</p><form method="post" action="{{route('purchases.destroy',$entry)}}">@csrf @method('DELETE')<button class="delete-button">Ukloni zapis</button></form></article>@empty<div class="empty-tool"><h2>Još nema zapisa kupovine.</h2><p>Otvori model koji si kupio i dodaj iskustvo.</p></div>@endforelse</div>{{$entries->links()}}
@endsection
