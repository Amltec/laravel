@extends('admin.account_cli._template')

@section('title-tab')
Configurações
@endsection


@section('content-tab')
@php
/*Variáveis esperadas:
    $account
    $dataAccount
    $params[]: $configProcessNames, $configData
*/
$configProcessNames = $params['configProcessNames'];
$config_data = $params['configData'];
//dump($params);


//formata para o padrão aceito no formulário em autofields parâmetro autodata
$datavalues=[];
foreach($config_data as $f => $v){
    if(is_array($v)){
        foreach($v as $f2 => $v2){
            $datavalues[$f.'--'.$f2]=$v2;
        }
    }else{
        $datavalues[$f]=$v;
    }
}




$data=[];
//config: cadastro de apólice
    $process_name='cad_apolice';
    $process_opt = $configProcessNames[$process_name];
    $data[$process_name.'_group']=['title'=>$process_opt['title'],'content'=>function() use($process_name,$process_opt,$account,$datavalues){
            
            $productsList=[''=>'Todos'];
            foreach($process_opt['products'] as $prod =>$prod_opt){$productsList[$prod]=$prod_opt['title'];}
    
            $param=[];
            $param[$process_name.'--active']=['type'=>'radio','label'=>'Ativar Serviço','list'=>['s'=>'Sim','n'=>'Não'],'class_group'=>'j-service-active'];
            $param[$process_name.'--products_active[]'] = ['type'=>'checkbox','break_line'=>true,'label'=>'Produtos','list'=>$productsList];
            
            $param[$process_name.'--line01']=['type'=>'info','text'=>'<span class="text-muted">Login no Quiver</span>'];
            $param[$process_name.'--login_mode']=['type'=>'radio','label'=>'Modo de login','list'=>['unique'=>'Único para todos os corretores','separate'=>'Separado por corretor'],'class_group'=>'j-login-mode'];
            $param[$process_name.'--quiver_user']=['label'=>'Usuário','maxlength'=>20,'require'=>true,'class_group'=>'j-login-mode-separate'];
            $param[$process_name.'--quiver_login']=['label'=>'Login','maxlength'=>20,'require'=>true,'class_group'=>'j-login-mode-separate'];
            $param[$process_name.'--quiver_senha']=['type'=>'password','label'=>'Senha','maxlength'=>20,'require'=>true,'class_group'=>'j-login-mode-separate','info_html'=>'<span class="text-muted">Preencher somente se for alterar</span>'];
            
            echo view('templates.ui.auto_fields',[
                'layout_type'=>'horizontal',
                'autocolumns'=>$param,
                'class'=>'blocks-config block-'.$process_name,
                'autodata'=>$datavalues,
            ]);
    }];

    
    
//config: area de seguradoras
    $process_name='seguradora_files';
    $process_opt = $configProcessNames[$process_name];
    $data[$process_name.'_group']=['title'=>$process_opt['title'],'content'=>function() use($process_name,$process_opt,$account,$datavalues){
            $param=[];
            $param[$process_name.'--active']=['type'=>'radio','label'=>'Ativar Serviço','list'=>['s'=>'Sim','n'=>'Não'],'class_group'=>'j-service-active'];
            $param[$process_name.'--line01']=['type'=>'info','text'=>'<span class="text-muted">Login no Quiver</span>'];
            $param[$process_name.'--login_mode']=['type'=>'radio','label'=>'Modo de login','list'=>['unique'=>'Único para todos os corretores','separate'=>'Separado por corretor'],'class_group'=>'j-login-mode'];
            $param[$process_name.'--quiver_user']=['label'=>'Usuário','maxlength'=>20,'require'=>true,'class_group'=>'j-login-mode-separate'];
            $param[$process_name.'--quiver_login']=['label'=>'Login','maxlength'=>20,'require'=>true,'class_group'=>'j-login-mode-separate'];
            $param[$process_name.'--quiver_senha']=['type'=>'password','label'=>'Senha','maxlength'=>20,'require'=>true,'class_group'=>'j-login-mode-separate','info_html'=>'<span class="text-muted">Preencher somente se for alterar</span>'];
            
            echo view('templates.ui.auto_fields',[
                'layout_type'=>'horizontal',
                'autocolumns'=>$param,
                'class'=>'blocks-config block-'.$process_name,
                'autodata'=>$datavalues,
            ]);
    }];

 
    
//carrega a estrutura do formulário
    echo view('templates.ui.form',[
        'url'=>route('admin.app.post',['account_cli','config_save']),
        'content'=>function() use($account,$data){
            echo '<input type="hidden" name="account_id" value="'.$account->id.'">';
            echo view('templates.ui.accordion',['data'=>$data,'default_hide'=>true]);
        }
    ]);


@endphp

<script>
(function(){
    var oBlockConfig = $('.blocks-config');
    
    //campos de serviços ativos
    var fCheckActiveFields = function(){
        oBlockConfig.each(function(){
            var base=$(this);
            var rows=base.find('.form-group').not('.j-service-active');
            var input_active = base.find('.j-service-active input:checked').val();
            fLoginModeFields();
            if(input_active=='s'){
                rows.show();
            }else{
                rows.hide();
            }
        });
    };
    oBlockConfig.find('.j-service-active input').on('click',fCheckActiveFields);
    
    
    //campos de modo de login
    var fLoginModeFields = function(){
        oBlockConfig.each(function(){
            var base=$(this);
            var input_active = base.find('.j-login-mode input:checked').val();
            var rows=base.find('.j-login-mode-separate');
            if(input_active=='unique'){rows.show();}else{rows.hide();}
        });
    };
    oBlockConfig.find('.j-login-mode input').on('click',fLoginModeFields);
    
    
    //ao inicializar
    fCheckActiveFields();
    
})();
</script>


@endsection