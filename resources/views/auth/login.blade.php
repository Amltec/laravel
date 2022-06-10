@extends('templates.login.login')


@php
    $logo_url = '';

    if($account){
        $data = $account->getData();
        $title = $account->account_name;
        $logo_url = isset($data['logo_main']) ? ($data['logo_main'] .'?'. $data['updated_at']) : '';
    }

    if(!$logo_url){
        $title = Config::data('title');
        $logo_url = Config::data('logo_main').'?'.Config::data('updated_at');
    }
@endphp



@section('title')
Login - {{ ($title?$title.' - ':'') . config('app.name')}}
@endsection


@push('bottom')
<!-- iCheck -->
<script src="{{asset('AdminLTE-2.4.5/plugins/iCheck/icheck.min.js')}}"></script>
<script>
function browserTabId(){
    return sessionStorage.tabID && sessionStorage.closedLastTab !== '2' ? sessionStorage.tabID : sessionStorage.tabID = Math.random();
};

 $(function () {
    $('input').iCheck({
      checkboxClass: 'icheckbox_square-blue',
      radioClass: 'iradio_square-blue',
      increaseArea: '20%' //optional
    });
    $( $('#email').val()==''?'#email':'#senha').focus();
    $('<input type="hidden" name="X-TAB-Id" value="'+ browserTabId() +'">').appendTo('form');
  });
</script>
@endpush


@section('logo')
@php
    if($logo_url){
        echo '<div class="login-logo-img"><img src="'. $logo_url .'" /></div>';
    }else{
        echo $title;
    }
@endphp
@endsection


@section('content')
<p class="login-box-msg">
    {!! env('APP_MAINTENANCE')=='on'?'<span style="font-weight:600;font-size:15px;text-transform:uppercase;color:#cc0000;">Sistema em manutenção</span><br>':'' !!}
    Insira seus dados de acesso
</p>

<form method="POST" action="{{ $account? route('account_postlogin',$account->account_login) : route('postlogin')  }}" aria-label="{{ __('Login') }}">
    @csrf
    
    @if(!$account)
   <div class="form-group has-feedback{{ $errors->has('account_login') ? ' has-error' : '' }}">
    <input type="text" class="form-control" placeholder="Login da conta" name="account_login" required id="email" value="{{array_get(session('fields'),'account_login')}}" autocomplete="off">
      <span class="glyphicon glyphicon-log-in form-control-feedback"></span>
      @if ($errors->has('account_login'))
          <span class="invalid-feedback" role="alert">
              <strong>{{ $errors->first('account_login') }}</strong>
          </span>
      @endif
    </div>
    @endif
    
  <div class="form-group has-feedback{{ $errors->has('email') ? ' has-error' : '' }}">
  <input type="email" class="form-control" placeholder="Email" name="email" required id="email" value="{{array_get(session('fields'),'email')}}" autocomplete="off">
    <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
    @if ($errors->has('email'))
        <span class="invalid-feedback" role="alert">
            <strong>{{ $errors->first('email') }}</strong>
        </span>
    @endif
  </div>
  <div class="form-group has-feedback{{ $errors->has('password') ? ' has-error' : '' }}">
    <input type="password" class="form-control" placeholder="Senha" name="senha" id="senha" required autocomplete="off">
    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
    @if ($errors->has('password'))
        <span class="invalid-feedback" role="alert">
            <strong>{{ $errors->first('password') }}</strong>
        </span>
    @endif
  </div>
  <div class="row">
    <div class="col-xs-8">
      <div class="checkbox icheck">
          <label><input type="checkbox" name="remember" value="s"  {{array_get(session('fields'),'remember')===true ? 'checked' : ''}}> Manter conectado</label>
      </div>
    </div>
    <div class="col-xs-4">
      <button type="submit" class="btn btn-primary btn-block btn-flat">Login</button>
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

<div style="display:none;">
<hr>
<a href="{{route('site.app.get',['login','password'])}}">Esqueceu a senha</a><br>
</div>



@endsection
