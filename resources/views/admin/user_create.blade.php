@extends('templates.admin.index')

@section('title')
@php
    if(!empty($isUserLogged)){
        echo 'Usuário da Conta';
    }else{
        echo isset($user) ? $user->user_name .' <small>#'. $user->id.'</small>' :'Novo Usuário';
    }
    
@endphp
@endsection


@section('content-view')
@php

$level_list = \App\Services\UsersService::$levels;
if(in_array($user_level_Logged,['dev','superadmin','admin'])){
    unset($level_list['dev'],$level_list['superadmin']);
}else{//==admin
    unset($level_list['dev'],$level_list['superadmin'],$level_list['admin']);
}
//dd($level_list);


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

echo view('templates.ui.auto_fields',[
    'layout_type'=>'horizontal',
    'form'=>[
        'id'=>'form_user',
        'url_action'=> (isset($user)?route('admin.app.update',['users',$user->id]):route('admin.app.store','users')),
        'url_back'=>route('admin.app.index','users'),
        'data_opt'=>[
            'focus'=>true,
        ],
        'bt_save'=>true,
        'bt_back'=>true,
        'autodata'=>$user??false,
        'url_success'=> isset($user) ? null : route('admin.app.edit',['users',':id'])
    ],
    'metabox'=>true,
    'autocolumns'=>$param_fields,
]);

if(isset($user)){
    //inclui a view de permissões de login
    echo view('_inc.users.login_permiss_edit',['prefix'=>'admin','user'=>$user,'form_id'=>'form_user']);
}


if(isset($user) && Gate::allows('superadmin')){//edit

    echo view('templates.components.metabox',[
        'title'=>'Ações',
        'content'=>function(){
            echo view('templates.components.button',[
                'title'=>'Desconectar todas as sessões deste usuário?',
                'post'=>[
                    'url'=>route('admin.app.post',['users','logoffDevices']),
                    'confirm'=>true,
                    //'cb'=>'@function(r){if(r.success)alert("Sessões desconectadas");}'
                ]
            ]);
        },
    ]);
    
    
    if($user->user_status=='0'){
        echo view('_inc.users.login_attempt_list',['user_id'=>$user->id,'is_title'=>true]);
    }
}



@endphp

@endsection