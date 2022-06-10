@php
/*Variáveis esperadas:
    $user
    
Obs: view aberta por janela modal
*/

$level_list = \App\Services\UsersService::$levels;
unset($level_list['dev'],$level_list['superadmin']);


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
                '<p><a href="#" onclick="awBtnPostData({url:\''.  route('super-admin.app.post',['accounts','userIdReLogin',$user->id])  .'\',confirm:\'Irá desconectar o usuário para forçar um novo login\nDeseja continuar?.\'},this);return false;"><i class="fa fa-lock margin-r-5" style="width:14px;"></i> Forçar login </a> </p>'.
                ($user->user_status=='0' ? '<p><a href="#" onclick="awModal({title:\'Tentativas de Login\',html:$(\'#html-login_attempt_list\').html()});"><i class="fa fa-caret-right margin-r-5" style="width:14px;"></i> Tentativas de Login </a> </p>' : '').
                ''
        ],
    ];
    
    if($user->user_status=='0'){
        //apenas gera a div para armazena o html para ser utilizado pela janela modal programado acima
        echo '<div id="html-login_attempt_list" class="hiddenx">';
        echo view('_inc.users.login_attempt_list',['user_id'=>$user->id,'is_title'=>false,'is_metabox'=>false]);
        echo '</div>';
    }
    
    //inclui a view de permissões de login
    echo view('_inc.users.login_permiss_edit',['prefix'=>'super-admin','user'=>$user,'form_id'=>'form_user']);
    
    
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
        'url_action'=> route('super-admin.app.post',['accounts','user_save_ajax']),
        'url_back'=>route('super-admin.app.index','users'),
        'data_opt'=>[
            'focus'=>true,
            //'onSuccess'=>'@function(){ setTimeout(function(){ $(".modal").modal("hide"); },1000); }',
            'onSuccess'=>'@function(){ setTimeout(function(){ window.location.reload(); },600); }',
        ],
        'bt_save'=>true,
        'autodata'=>$user,
    ],
    'autocolumns'=>
        [
            'account_id'=>['type'=>'hidden','value'=>$account_id],
            'user_id'=>['type'=>'hidden','value'=> ($user?$user->id:'') ]
        ]+
        $param_fields
]);


@endphp
