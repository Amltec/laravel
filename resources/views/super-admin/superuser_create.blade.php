@extends('templates.admin.index')

@section('title')
@php
echo isset($user) ? 'Super Usuário - '.$user->user_name .' <small>#'. $user->id.'</small>' :'Novo Super Usuário';
@endphp
@endsection


@section('content-view')
@php

$level_list = \App\Services\UsersService::$levels;
$level_list = ['dev'=>$level_list['dev'], 'superadmin'=>$level_list['superadmin'] ];

$show_level=true;

$userLogged = Auth::user();
if($userLogged->id>1)$show_level=false;//oculta o campo level


if(isset($user)){//edit
    $param_fields = [
        'user_name'=>['label'=>'Nome','maxlength'=>100,'require'=>true],
        'user_email'=>['type'=>'email','label'=>'E-mail','require'=>true],
        'user_level'=>['type'=>'select','label'=>'Nível de acesso','list'=>['']+$level_list],
        'user_status'=>['type'=>'select','label'=>'Status','list'=>[''=>'','a'=>'Normal','0'=>'Bloqueado','c'=>'Cancelado'],'require'=>true],
        
        'line01'=>['type'=>'info','label'=>' ','text'=>'Preencher somente se for alterar'],
        
        'user_pass'=>['type'=>'password','label'=>'Senha','maxlength'=>20],
        'user_pass2'=>['type'=>'password','label'=>'Confirmar Senha','maxlength'=>20],
        
        'line_opt'=>['type'=>'html','label'=>'Opções','html'=>
                '<p><a href="#" class="inlineblock padd-form-top j-permiss-login"><i class="fa fa-edit margin-r-5" style="width:14px;"></i> Permissões de Acesso</a></p>'.
                '<p><a href="#" onclick="awBtnPostData({url:\''.  route('super-admin.app.post',['superusers','userIdReLogin',$user->id])  .'\',confirm:\'Irá desconectar o usuário para forçar um novo login\nDeseja continuar?.\'},this);return false;"><i class="fa fa-lock margin-r-5" style="width:14px;"></i> Forçar login </a> </p>'.
                ($user->user_status=='0' ? '<p><a href="#" onclick="awModal({title:\'Tentativas de Login\',html:$(\'#html-login_attempt_list\').html()});"><i class="fa fa-caret-right margin-r-5" style="width:14px;"></i> Tentativas de Login </a> </p>' : '').
                ''
        ],
        
    ];
    
}else{//add
    $param_fields = [
        'user_name'=>['label'=>'Nome','maxlength'=>100,'require'=>true],
        'user_email'=>['type'=>'email','label'=>'E-mail','require'=>true],
        'user_level'=>['type'=>'select','label'=>'Nível de acesso','list'=>['']+$level_list],
        'user_pass'=>['type'=>'password','label'=>'Senha','maxlength'=>20,'require'=>true],
        'user_pass2'=>['type'=>'password','label'=>'Confirmar Senha','maxlength'=>20,'require'=>true],
    ];
}
if(!$show_level)unset($param_fields['user_level']);

echo view('templates.ui.auto_fields',[
    'layout_type'=>'horizontal',
    'form'=>[
        'id'=>'form_user',
        'url_action'=> (isset($user)?route('super-admin.app.update',['superusers',$user->id]):route('super-admin.app.store','superusers')),
        'url_back'=>route('super-admin.app.index','superusers'),
        'data_opt'=>[
            'focus'=>true,
        ],
        'bt_save'=>true,
        'bt_back'=>true,
        'autodata'=>$user??false,
        'url_success'=> isset($user) ? null : route('super-admin.app.edit',['superusers',':id'])
    ],
    'metabox'=>true,
    'autocolumns'=>$param_fields,
]);



if(isset($user)){//edit
    if($user->user_status=='0'){
        //apenas gera a div para armazena o html para ser utilizado pela janela modal programado acima
        echo '<div id="html-login_attempt_list" class="hiddenx">';
        echo view('_inc.users.login_attempt_list',['user_id'=>$user->id]);
        echo '</div>';
    }
    
    //inclui a view de permissões de login
    echo view('_inc.users.login_permiss_edit',['prefix'=>'super-admin','user'=>$user,'form_id'=>'form_user']);

}




@endphp

@endsection