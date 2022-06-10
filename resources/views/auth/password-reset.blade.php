@extends('templates.login.login')

@section('title')
Senha de acesso | Login | {{config('app.name')}}
@endsection


@section('content')

<p class="login-box-msg">Gravando nova senha de acesso</p>

<form method="POST" action="{{ route('site.app.post',['login','reset_password']) }}">
    @csrf
    <input type="hidden" name="reset_token" value="{{ $token }}">
    <input type="hidden" name="reset_email" value="{{ \Request::input("email") }}">
 
  <div class="form-group has-feedback{{ $errors->has('password') ? ' has-error' : '' }}">
    <input type="password" class="form-control" placeholder="Nova senha" name="senha" id="senha" required autocomplete="off">
    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
    @if ($errors->has('password'))
        <span class="invalid-feedback" role="alert">
            <strong>{{ $errors->first('password') }}</strong>
        </span>
    @endif
  </div>
  <div class="form-group has-feedback{{ $errors->has('password2') ? ' has-error' : '' }}">
    <input type="password" class="form-control" placeholder="Confirmar nova senha" name="senha2" id="senha2" required autocomplete="off">
    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
    @if ($errors->has('password2'))
        <span class="invalid-feedback" role="alert">
            <strong>{{ $errors->first('password2') }}</strong>
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