@php
/*Variáveis esperadas:
    $pass
    $account_id
    $allowEditLogin (bool)
    
Obs: view aberta por janela modal
*/

//ajustes para caso venha do painel admin ou superadmin
$prefix = Config::adminPrefix();
if($prefix=='super-admin'){
    $account_class='accounts';
}else{//admin
    $account_class='account';
}


$param_fields = [
    'pass_user'=>['label'=>'Corretora','maxlength'=>100,'require'=>true],
    'pass_login'=>['label'=>'Login','require'=>true],
    'pass_pass'=>['type'=>'password','label'=>'Senha','require'=>true],
    'pass_status'=>['type'=>'select','label'=>'Status','list'=>[''=>'','a'=>'Normal','0'=>'Bloqueado','c'=>'Cancelado'],'require'=>true],
    'pass_type'=>['type'=>'radio','label'=>'Usuário de Revisão','list'=>['t'=>'Sim',''=>'Não'],'default'=>'','info_html'=>'<span class="text-muted">Este usuário é utilizado para testes e revisões interna do robô</span>'],
];
if($pass){
    if($allowEditLogin===false)$param_fields['pass_login']['attr']='readonly';
    
    $param_fields['pass_pass']['info_html']='<span class="text-muted">Preencher somente se for alterar</span>';
}else{
    unset( $param_fields['pass_status'] );
}

echo view('templates.ui.auto_fields',[
    'layout_type'=>'horizontal',
    'form'=>[
        'id'=>'form_user',
        'url_action'=> route($prefix.'.app.post',[$account_class,'pass_save_ajax']),
        //'url_back'=>route($prefix.'.app.index','pass'),
        'data_opt'=>[
            'focus'=>true,
            //'onSuccess'=>'@function(){ setTimeout(function(){ $(".modal").modal("hide"); },1000); }',
            'onSuccess'=>'@function(){ setTimeout(function(){ window.location.reload(); },600); }',
        ],
        'bt_save'=>$pass ? 'Atualizar' : 'Adicionar',
        'autodata'=>$pass,
    ],
    'autocolumns'=>
        [
            'account_id'=>['type'=>'hidden','value'=>$account_id],
            'pass_id'=>['type'=>'hidden','value'=> ($pass?$pass->id:'') ]
        ]+
        $param_fields
]);


@endphp
