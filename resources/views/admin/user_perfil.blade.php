@extends('templates.admin.index')

@section('title')
Perfil <small>{{$user->level_name}}</small>
@endsection


@section('content-view')
@php

$prefix = Config::adminPrefix();

echo view('templates.ui.auto_fields',[
    //'layout_type'=>'horizontal',
    'form'=>[
        'id'=>'form_user',
        'url_action'=> $prefix=='super-admin' ? route('super-admin.app.post',['super-users','perfilUpdate']) : route('admin.app.post',['users','perfilUpdate']),
        'data_opt'=>[
            'focus'=>true,
            'onSuccess'=>'@function(opt){ window.location.reload(); }' //obs: é necessário o reload da página, pois pela regra de segurança do laravél, é desautenticado o usuário ao salvar pela segunda vez alternado a senha
        ],
        'bt_save'=>true,
        'autodata'=>$user??false
    ],
    'metabox'=>[
        'title'=>'Seus dados'
    ],
    'autocolumns'=>[
        'user_name'=>['label'=>'Nome','maxlength'=>100,'require'=>true],
        'user_email'=>['type'=>'email','label'=>'E-mail','require'=>true],
        'line01'=>['type'=>'info','label'=>' ','text'=>'Preencher somente se for alterar'],
        'user_pass'=>['type'=>'password','label'=>'Senha','maxlength'=>20],
        'user_pass2'=>['type'=>'password','label'=>'Confirmar Senha','maxlength'=>20],
        
        'line02'=>['type'=>'info','label'=>' ','text'=>'<a href="#" onclick="$(\'#div-opt1\').fadeToggle(\'fast\');">Ações desta conta</a>'],
        'logout_devices'=>function() use($prefix){
            echo '<div id="div-opt1" class="hiddenx">
            <p>
                <a href="#" onclick="var pass=prompt(\'Digite sua senha\');if(pass)awBtnPostData({url:\''.  route($prefix.'.app.post',['users','logoff_devices'])  .'\',data:{user_pass:pass},cb:function(r,bt){if(r.success){r.oBt.html(\'Desconectado\');}else{alert(r.msg);}}},this);return false;" class="btn btn-default">Desconectar de todos os dispositivos logados</a>
            </p>
            </div>';
        }
    ],
]);



@endphp

@endsection