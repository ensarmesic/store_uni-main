@if(session('status'))<div class="notice notice-success" role="status"><b aria-hidden="true">✓</b><div>{{session('status')}}</div></div>@endif
@if($errors->any())<div class="notice notice-error" role="alert"><b aria-hidden="true">!</b><div><strong>Još samo mala ispravka.</strong><ul>@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div></div>@endif
