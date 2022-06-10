@extends('templates.login.login')

@section('title')
Senha de acesso | Login | {{config('app.name')}}
@endsection


@section('content')
<p class="login-box-msg">Recuperação de dados de acesso</p>

<form method="POST" action="{{ route('site.app.post',['login','password']) }}">
    @csrf
  <div class="form-group has-feedback{{ $errors->has('email') ? ' has-error' : '' }}">
  <input type="email" class="form-control" placeholder="Email" name="email" required id="email" value="{{array_get(session('fields'),'email')}}" autocomplete="off">
    <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
    @if ($errors->has('email'))
        <span class="invalid-feedback" role="alert">
            <strong>{{ $errors->first('email') }}</strong>
        </span>
    @endif
  </div>
  <div class="row">
    <div class="col-xs-8"></div>
    <div class="col-xs-4">
      <button type="submit" class="btn btn-primary btn-block btn-flat">Enviar</button>
    </div>
  </div>
</form>


@php
if(session('msg2')){
    echo '<p><strong class="text-aqua" style="font-weight:600;">'.session('msg2').'</strong></p>';
}
if(session('msg')){
    echo view('templates.components.alert',['type'=>false,'msg'=>session('msg')]);
}
@endphp


<div>
<hr>
<a href="{{route('login')}}">Voltar para o login</a><br>
</div>

@endsection



@push('bottom')
<script>
$(function(){
    $('#email').focus();
});
</script>
@endpush