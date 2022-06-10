@extends('templates.admin.index')

@section('title')
@php
    if(!$model){
        echo 'Novo Corretor';
    }else{
        echo 'Corretor <small>#'.$model->id.' - '. $model->created_at .'</small>';
    }
@endphp
@endsection


@section('content-view')
@php
/* Variáveis esperadas
    $model
*/



function fTab_dados($vars){
    extract($vars);
    
    $param_fields = [
        'broker_alias'=>['label'=>'Corretor','maxlength'=>50,'require'=>true],
        'broker_name'=>['label'=>'Nome ou Razão Social','maxlength'=>100,'require'=>true],
        'broker_doc'=>['label'=>'Nº SUSEP','maxlength'=>100,'require'=>true, 'info_html'=>'<span class="text-muted">Para mais de um valor, utilize virgula</span>'],
        'broker_cpf_cnpj'=>['label'=>'CNPJ / CPF','maxlength'=>100,'require'=>true],
        'broker_status'=>['type'=>'select','label'=>'Status','list'=>[''=>'','a'=>'Normal','c'=>'Cancelado'],'require'=>true],

        'line01'=>['type'=>'info','text'=>'<hr><span class="text-muted">Informações para Login no Quiver</span>'],
        'broker_col_user'=>['label'=>'Usuário','maxlength'=>20,'require'=>true],
        'broker_col_login'=>['label'=>'Login','maxlength'=>20,'require'=>true],
        'broker_col_senha'=>['type'=>'password','label'=>'Senha','maxlength'=>20,'require'=>true],
        'broker_col_senha2'=>['type'=>'password','label'=>'Confirmar Senha','require'=>true,'maxlength'=>20,
            'info_html'=>($model ? 
                    '<span class="text-muted">Preencher somente se for alterar</span>'.
                    (Gate::allows('superadmin')?'<br><em class="text-muted">Senha atual: <b onclick="$(this).text(\''.$model->broker_col_senha.'\')">[clique para ver]</b></em>':'')
                : '')],
    ];
    
    if(!$is_broker_login){//as informações de login do corretor não devem aparecer
        unset($param_fields['line01']);
        $param_fields['broker_col_user']['type']='hidden';
        $param_fields['broker_col_login']['type']='hidden';
        $param_fields['broker_col_senha']['type']='hidden';
        $param_fields['broker_col_senha2']['type']='hidden';
    }

    if(empty($model)){//add
        $param_fields['broker_status']=['type'=>'select','label'=>'Status','list'=>[''=>'','a'=>'Normal','c'=>'Cancelado'],'require'=>true];
        unset($param_fields['broker_status']);
    }else{//edit
        //limpa o campo senha 
        $model->broker_col_senha='';
    }
    
    echo view('templates.ui.auto_fields',[
            'layout_type'=>'horizontal',
            'autocolumns'=>$param_fields,
            'autodata'=>$model??false,
        ]);
}



function fTab_comissao($vars){
    extract($vars);
    
    $field_insurer = [];
    $checks_ids_insurer = [];
    if($modelInsurer){
        $r='';
        foreach($configProcessNames['cad_apolice']['products'] as $process_prod => $process_opt){
            if(!$products_active || in_array($process_prod,$products_active)){
                    $r.='<div class="block_comissao_desc">'.
                        '<strong>'.$process_opt['title'].'</strong>';
                    foreach($modelInsurer as $rs){
                        $t=false;
                        if($model){//somente se for atualização
                            $data = $rs->getBrokerData($model->id);//captura os dados gerais relacionados entre corretor com a seguradora do loop
                            $comissao_desc = $data['cad_apolice_comissao_desc']??[];
                            if($comissao_desc[$process_prod]??false === true)$t=true;
                            //dump($data);
                        }
                        $r.='<br><label class="nostrong"><input type="checkbox" name="cad_apolice_comissao_desc[]" '. ($t?'checked':'') .' value="'. $process_prod .','. $rs->id .'"><span class="checkmark"></span> '.$rs->insurer_alias.'</label>';
                    }
                    $r.='</div>';
            }
        }
        if($r){
            $r='<span class="text-muted">Descontar Comissão de 1% no cadastro</span><br>'.
                $r.
                '<div class="clearfix"></div>';
            echo $r;
        }
    }
}



function fTab_insurers($vars){
    extract($vars);
    
    if($modelInsurer){
        echo '<p>Informe os dados de acessos aos sites das seguradoras</p>';
        
       
        echo '<table class="table table-insurers-logins" id="table-insurers-logins">'.
            '<tbody>';
                
                
        //junta todos os ids das seguradoras de todos os produtos $configProcessNames['seguradora_data']['products'] para verificar quais podem ser configuradas abaixo
        $insurers_allow_ids=[];
        foreach($configProcessNames['seguradora_data']['products'] as $i => $opt){
            if($opt['insurers_allow']??false)$insurers_allow_ids = array_unique(array_merge($insurers_allow_ids,$opt['insurers_allow']), SORT_REGULAR);
        }
        
        
        $tmp = $insurers_allow_ids;
        $insurers_allow_ids = [];
        //aqui a var $insurers_allow_ids contém o valor por ramo, mas no caso deste arquivo será juntado todas as seguradoras em uma só var
        foreach($tmp as $prod => $rs){
            foreach($rs as $item){
                if(!in_array($item,$insurers_allow_ids))$insurers_allow_ids[]=$item;
            }
        }
        
        
        //monta a tabela
        foreach($modelInsurer as $rs){
            if($insurers_allow_ids && !in_array($rs->insurer_basename,$insurers_allow_ids))continue;//está permitido apenas estes ids de seguradora para este processo
            
            if($model){
                $dataconfig = $rs->getBrokerData($model->id)['seguradora_config']??null;
                //if($dataconfig['active'])dump([$model->id,$dataconfig]);
            }else{
                $dataconfig = null;
            }
            
            $v_active=$dataconfig['active']??false;
            $v_use_quiver=$dataconfig['use_quiver']??'s';
            $v_login_quiver=$dataconfig['login_quiver']??'';
            $v_login=$dataconfig['login']??'';
            $is_pass=!empty($dataconfig['pass']);//true - existe a senha, false - não existe a senha
            $v_code=$dataconfig['code']??'';
            $v_user=$dataconfig['user']??'';
            
            
            echo '<tr class="'. (!$v_active?'tr-disable':'') .'">'.
                    '<td class="col-active" style="padding-top:20px;">'. 
                        '<input type="hidden" name="logins_insurer_id[]" value="'. $rs->id .'" >'.
                        '<input type="hidden" class="new_item" name="new_item_'.$rs->id.'" value="'. (!$is_pass?'s':'') .'" >'.
                        view('templates.components.checkbox',['list'=>['s'=>''], 'name'=>'active_'.$rs->id, 'class_group'=>'margin-bottom-none','value'=>($v_active?'s':'')  ]) .
                    '</td>'.
                    '<td class="col-insurer" style="vertical-align:middle;">'.$rs->insurer_alias.'</td>'.
                    '<td class="col-use_quiver">'. view('templates.components.select',['value'=>$v_use_quiver, 'name'=>'insurer_use_quiver_'.$rs->id, 'class_group'=>'margin-bottom-none', 'class_field'=>'field-use_quiver', 'list'=>['s'=>'Sim','n'=>'Não'], 'label'=>'Senhas do Quiver']) .'</td>'.
                    '<td class="col-login_quiver">'. view('templates.components.text',['value'=>$v_login_quiver, 'name'=>'insurer_login_quiver_'.$rs->id, 'class_group'=>'margin-bottom-none', 'maxlength'=>50,'label'=>'Corretor Login']) .'</td>'.
                    '<td class="col-login">'. view('templates.components.text',['value'=>$v_login, 'name'=>'insurer_login_'.$rs->id, 'class_group'=>'margin-bottom-none', 'maxlength'=>50,'label'=>'Login']) .'</td>'.
                    '<td class="col-pass">'. 
                        '<div class="form-group form-group-insurer_pass_'.$rs->id.' margin-bottom-none" id="form-group-insurer_login_'.$rs->id.'">'.
                            '<label class="control-label">Senha</label>'.
                            '<div data-fieldpass="show" class="form-control '. ($is_pass?'':'hiddenx') .'" id="insurer_pass_'.$rs->id.'_tmp">(alterar)</div>' .
                            '<div data-fieldpass="hide" class="'. ($is_pass?'hiddenx':'') .'">'. view('templates.components.text',['type'=>'password', 'name'=>'insurer_pass_'.$rs->id, 'class_group'=>'margin-bottom-none', 'maxlength'=>20]) .'</div>'.
                        '</div>'.
                    '</td>'.
                    '<td class="col-code col-field-hide">'. view('templates.components.text',['value'=>$v_code, 'name'=>'insurer_code_'.$rs->id, 'class_group'=>'margin-bottom-none', 'maxlength'=>50, 'label'=>'Código']) .'</td>'.
                    '<td class="col-user col-field-hide">'. view('templates.components.text',['value'=>$v_user, 'name'=>'insurer_user_'.$rs->id, 'class_group'=>'margin-bottom-none', 'maxlength'=>50, 'label'=>'Usuário']) .'</td>'.
                    '<td>&nbsp;</td>'. //deixar esta coluna vazia
                '</tr>';
        }
        echo '</tbody>'.
             '</table>';
       
        
   }else{
        echo 'Confiração não disponível';
   }
}



//Lista dos produtos permitidos
function fTab_products($vars){
    extract($vars);
    
    echo '<p>Produtos permitidos para o Cadastro de Apólice. <br>Caso nenhum item esteja marcado, serão considerados todos.</p>';
    
    if($model){
        $value = explode(',',$model->getMetaData('products_allow'));
    }else{
        $value = [];
    }
    
    $produtcs_all = array_get($configProcessNames,'cad_apolice.products');
    $list = [];
    foreach($produtcs_all as $f=>$v){
        if(!$products_active || in_array($f,$products_active))$list[$f]=$v['title'];
    }
    
    echo view('templates.ui.auto_fields',[
            'layout_type'=>'horizontal',
            'autocolumns'=>[
                'produtcs_allow[]'=>['type'=>'checkbox','list'=>$list,'value'=>$value,'break_line'=>true],
            ],
            'autodata'=>$model??false,
        ]);
}




$account_config = \Config::accountData()->data['config']??null;
$is_broker_login = $account_config['cad_apolice']['login_mode']=='separate';
$modelInsurer = \App\Models\Insurer::where('insurer_status','a')->get();
$configProcessNames = \App\ProcessRobot\VarsProcessRobot::$configProcessNames;
$products_active = array_filter(array_get($account_config,'cad_apolice.products_active')??[]);



$get_defined_vars = get_defined_vars();
echo view('templates.ui.form',[
    'url'=>($model?route('admin.app.update',['brokers',$model->id]):route('admin.app.store','brokers')),
    'url_back'=>route('admin.app.index','brokers'),
    'data_opt'=>[
        'focus'=>true,
        'onSuccess'=>"@function(r){if(r.action=='add'){window.location=String('". route('admin.app.edit',['brokers',':id']) ."').replace(':id',r.data.id);} }"
    ],
    'method'=> $model?'put':'post',
    'content'=>function() use($get_defined_vars){
        extract($get_defined_vars);
        
        $data = [
            'edit'=>['title'=>'Dados','content'=>['fTab_dados',$get_defined_vars] ],
            'comissao'=>['title'=>'Desconto de Comissão','content'=>['fTab_comissao',$get_defined_vars] ],
            'insurers'=>['title'=>'Seguradoras','content'=>['fTab_insurers',$get_defined_vars] ],
            'products'=>['title'=>'Produtos','content'=>['fTab_products',$get_defined_vars] ],
        ];
        if(array_get($account_config,'seguradora_data.active')!='s')unset($data['insurers']);
        
        echo view('templates.ui.tab',[
            'id'=>'tab_main',
            'tab_active'=>  _GET('pag')??'edit',
            'data'=>$data,
        ]);
    }
]);


@endphp

<style>
.block_comissao_desc{width:20%;float:left;}
@media (max-width:780px){ 
    .block_comissao_desc{width:50%;}
}

.table-insurers-logins{max-width:1240px;}
.table-insurers-logins .control-label{font-size:12px;}

.tr-disable td:not(.col-active){opacity:0.5;pointer-events:none;cursor:default;}
.col-active{width:80px;}
.col-insurer{width:240px;}
.col-use_quiver{width:160px;}
.col-login_quiver{width:160px;}
.col-login{width:180px;}
.col-pass{width:180px;}
.col-code{width:180px;}
.col-user{width:180px;}

/*classe padrão para ocultar tds* /
.col-field-hide .form-group{display:none;}
.col-field-hide:before{content:'-';}
*/
</style>
<script>
    
(function(){
    //show/hide password
    var oTb=$('#table-insurers-logins');
    oTb.on('click','a[data-action]',function(){
        var a=$(this);
        var tr=a.closest('tr');
        if(tr.find('.new_item').val()=='s'){
            fields.filter('[data-fieldpass=hide]').show();
        }else{
            var icons=tr.find('a[data-action]').hide();
            var fields=tr.find('[data-fieldpass]').hide();

            if(a.attr('data-action')=='pass-show'){//exibe
                icons.filter('[data-action=pass-hide]').show();
                fields.filter('[data-fieldpass=hide]').show().find('input').focus();
            }else{//oculta
                icons.filter('[data-action=pass-show]').show();
                fields.filter('[data-fieldpass=show]').show();
            }
        }
    });
   
    //oculta os demais campos se o campo use_quiver=s
    oTb.find('.field-use_quiver').on('change change_init',function(){
        var tr=$(this).closest('tr');
        var td1=tr.find('td.col-login,td.col-pass,td.col-code,td.col-user');
        var td2=tr.find('td.col-login_quiver');
        if(this.value=='s'){
            td1.hide();
            td2.show();
        }else{
            td1.show();
            td2.hide();
        };
        $(this).closest('td').nextAll(':visible:eq(0)').find('input:eq(0)').focus();
    }).trigger('change_init');
    
    
    //ao clicar no campo senha, exibe o campo
    oTb.on('click','div[data-fieldpass=show]',function(){
        var tr=$(this).closest('tr');
        if(tr.find('.new_item').val()!='s')tr.find('a[data-action=pass-show]').click();
    });
    //ao sair do campo senha, esconde o padrão
    oTb.on('focusout','input[type=password]',function(){
        var tr=$(this).closest('tr');
        if(tr.find('.new_item').val()!='s')tr.find('a[data-action=pass-hide]').click();
    });
    //ao teclar tab no campo login, exibe o campo senha
    oTb.on('keydown','.col-login input',function(e){
        if(e.keyCode==9){
            var tr=$(this).closest('tr');
            if(tr.find('.new_item').val()!='s'){
                setTimeout(function(){ 
                    tr.find('a[data-action=pass-show]').click(); 
                    tr.find('input[type=password]').focus();
                },1);
            }
        }
    });
    //field active
    oTb.on('click','.col-active input',function(){
        var tr=$(this).closest('tr');
        if( !$(this).prop('checked') ){
            tr.addClass('tr-disable');
        }else{
            tr.removeClass('tr-disable');
            tr.find('.col-login input').focus(); 
        }
    });
    
}());
</script>
@endsection