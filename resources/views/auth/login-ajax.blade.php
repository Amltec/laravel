@php
    $account = Request::input('account_id');
    $prefix = Request::input('prefix');
    if($prefix=='admin' && $account)$account=\App\Models\Account::find($account);
    
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

<div class="login-box hold-transition">
<div class="login-box-body no-select">
    <div class="login-logo">
        @php
            if($logo_url){
                echo '<div class="login-logo-img"><img src="'. $logo_url .'" /></div>';
            }else{
                echo $title;
            }
        @endphp
    </div>
    
    <p class="login-box-msg">Insira seus dados de acesso</p>

    <form method="POST" action="{{ $account? route('account_postlogin',$account->account_login) : route('postlogin') }}" id="form-login-ajax">
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
        <div class="col-xs-12 text-right">
            <button type="submit" class="btn btn-primary btn-flat" style="min-width:80px;">Login</button>
        </div>
      </div>
        <br>
      @include('templates.components.alert-structure')
    </form>


    

    <div class="hidden">
    <hr>
    <a href="#">Esqueceu a senha</a><br>
    </div>
</div>
</div>
<style>
.modal-login .login-box{margin:0;}
.modal-login .modal-content{border-radius:5px;overflow:hidden;box-shadow:0px 0px 15px rgba(0,0,0,0.3);padding:10px 10px;margin-top:-100px;}
.modal-login .login-logo-img img{max-width:320px;}
</style>
<script>