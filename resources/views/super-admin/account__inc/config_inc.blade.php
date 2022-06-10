@php
/*Variáveis esperadas:
    $account
    $dataAccount
    $params[]: $configProcessNames, $configData
    $user_level (from template blade): dev, superadmin, admin
*/
$configProcessNames = $params['configProcessNames'];
$config_data = $params['configData'];


$prefix = Config::adminPrefix();
//dump($params);


//formata para o padrão aceito no formulário em autofields parâmetro autodata
$datavalues=[
    'process_single'=>$account->process_single?'s':'n'
];
foreach($config_data as $f => $v){
    if(is_array($v)){
        foreach($v as $f2 => $v2){
            $datavalues[$f.'--'.$f2]=$v2;
        }
    }else{
        $datavalues[$f]=$v;
    }
}

//verifica quais configurações deve exibir
$show_cad_apolice=true;         if($user_level=='admin' && $config_data['cad_apolice']['active']!='s')$show_cad_apolice=false;
$show_seguradora_files=true;    if($user_level=='admin' && $config_data['seguradora_files']['active']!='s')$show_seguradora_files=false;
$show_seguradora_data=true;     if($user_level=='admin')$show_seguradora_data=false;//somente superadmin e dev devem visualizar
$show_count=0;


$data=[];

//Configurações gerais
        $data['general']=['title'=>'Gerais','content'=>function() use($datavalues){
                $param=[];
                $param['process_single']=['type'=>'radio','label'=>'Processamento Exclusivo','list'=>['s'=>'Sim','n'=>'Não']  ];
                $param['instances']=['type'=>'number','label'=>'Instâncias do Robô','attr'=>'min="1" max="10"'];
                echo view('templates.ui.auto_fields',[
                    'layout_type'=>'horizontal',
                    'autocolumns'=>$param,
                    'class'=>'blocks-config block-general',
                    'autodata'=>$datavalues,
                ]);
        }];


if($show_cad_apolice){
    //config: cadastro de apólice
        $show_count++;
        $process_name='cad_apolice';
        $process_opt = $configProcessNames[$process_name];
        $data[$process_name.'_group']=['title'=>$process_opt['title'],'content'=>function() use($process_name,$process_opt,$account,$datavalues,$user_level){


                $param=[];
                if($user_level=='superadmin'){
                    $productsList=[''=>'Todos'];
                    foreach($process_opt['products'] as $prod =>$prod_opt){$productsList[$prod]=$prod_opt['title'];}
                    $param[$process_name.'--active']=['type'=>'radio','label'=>'Ativar Serviço','list'=>['s'=>'Sim','n'=>'Não'],'class_group'=>'j-service-active'];
                    $param[$process_name.'--products_active[]'] = ['type'=>'checkbox','break_line'=>true,'label'=>'Produtos','list'=>$productsList,'class_group'=>'j-group-fields'];
                }else{
                    $param[$process_name.'--active']=['type'=>'radio','list'=>['s'=>'Sim'],'class_group'=>'j-service-active hiddenx'];
                }
                
                //$param[$process_name.'--line01']=['type'=>'info','text'=>'<span class="text-muted">Login no Quiver</span>','class_group'=>'j-group-fields'];
                $param[$process_name.'--login_mode']=['type'=>'radio','label'=>'Modo de login','list'=>['unique'=>'Único para todos os corretores','separate'=>'Separado por corretor'],'class_group'=>'j-login-mode j-group-fields'];
                
                
                /*//*** sistema de logins desativado em 06/04/2021 - estes logins foram movidos para a tabela account_pass ***
                $param[$process_name.'--tab_lists']=function() use($process_name,$datavalues){
                    $tab_data=[];
                    for($i=0;$i<=$logins_limit;$i++){
                        $n=($i>0?'_'.$i:'');
                        $tab_data[$process_name.'_tab_'.($i+1)]=['title'=>'Login '. ($logins_limit==$i?'Revisão':($i+1))  ,'content'=>function() use($i,$n,$process_name,$datavalues,$logins_limit){
                                if($i==$logins_limit)echo '<p class="text-aqua"><strong>Obs: este login será utilizado pelo sistema de revisão/testes</strong></p>';
                                echo view('templates.ui.auto_fields',[
                                    'layout_type'=>'horizontal',
                                    'autocolumns'=>[
                                        strval($process_name.'--quiver_user'.$n)  => ['label'=>'Usuário '.($i+1),'maxlength'=>20,'require'=>$i==0,'xclass_group'=>'j-login-mode-separate'],
                                        strval($process_name.'--quiver_login'.$n) => ['label'=>'Login '.($i+1),'maxlength'=>20,'require'=>$i==0,'xclass_group'=>'j-login-mode-separate'],
                                        strval($process_name.'--quiver_senha'.$n) => ['label'=>'Senha '.($i+1),'type'=>'password','maxlength'=>20,'require'=>$i==0,'xclass_group'=>'j-login-mode-separate','info_html'=>'<span class="text-muted">Preencher somente se for alterar</span>'],
                                    ],
                                    'autodata'=>$datavalues,
                                ]);
                        }];
                    }
                    echo view('templates.ui.tab',['data'=>$tab_data,'class'=>'j-login-mode-separate j-group-fields tab-login']);
                };
                */
                
                echo view('templates.ui.auto_fields',[
                    'layout_type'=>'horizontal',
                    'autocolumns'=>$param,
                    'class'=>'blocks-config block-'.$process_name,
                    'autodata'=>$datavalues,
                ]);
        }];
}
    

if($show_seguradora_files){
    //config: area de seguradoras
        $show_count++;
        $process_name='seguradora_files';
        $process_opt = $configProcessNames[$process_name];
        $data[$process_name.'_group']=['title'=>$process_opt['title'],'content'=>function() use($process_name,$process_opt,$account,$datavalues,$user_level){
                $param=[];
                if($user_level=='superadmin'){
                    $param[$process_name.'--active']=['type'=>'radio','label'=>'Ativar Serviço','list'=>['s'=>'Sim','n'=>'Não'],'class_group'=>'j-service-active'];
                    $param[$process_name.'--show_cli']=['type'=>'radio','label'=>'<span title="Exibe no menu este recurso para o cliente">Exibir para o cliente</span>','list'=>['s'=>'Sim','n'=>'Não'] ];
                }else{
                    $param[$process_name.'--active']=['type'=>'radio','list'=>['s'=>'Sim'],'class_group'=>'j-service-active hiddenx'];
                    $param[$process_name.'--show_cli']=['type'=>'radio','list'=>['s'=>'Sim'],'class_group'=>'hiddenx'];
                }
                
                
                /*//*** sistema de logins desativado em 15/03/2021 - utilizado apenas os logins do cad_apolice ***
                $param[$process_name.'--line01']=['type'=>'info','text'=>'<span class="text-muted">Login no Quiver</span>','class_group'=>'j-group-fields'];
                $param[$process_name.'--tab_lists']=function() use($process_name,$datavalues){
                    $tab_data=[];
                    for($i=0;$i<=$logins_limit;$i++){
                        $n=($i>0?'_'.$i:'');
                        $r='**'.$i;
                        
                        $tab_data[$process_name.'_tab_'.($i+1)]=['title'=>'Login '.($i+1),'content'=>function() use($i,$n,$process_name,$datavalues){
                                echo view('templates.ui.auto_fields',[
                                    'layout_type'=>'horizontal',
                                    'autocolumns'=>[
                                        strval($process_name.'--quiver_user'.$n)  => ['label'=>'Usuário '.($i+1),'maxlength'=>20,'require'=>$i==0],
                                        strval($process_name.'--quiver_login'.$n) => ['label'=>'Login '.($i+1),'maxlength'=>20,'require'=>$i==0],
                                        strval($process_name.'--quiver_senha'.$n) => ['label'=>'Senha '.($i+1),'type'=>'password','maxlength'=>20,'require'=>$i==0,'info_html'=>'<span class="text-muted">Preencher somente se for alterar</span>'],
                                    ],
                                    'autodata'=>$datavalues,
                                ]);
                        }];
                    }
                    echo view('templates.ui.tab',['data'=>$tab_data,'class'=>'j-group-fields tab-login']);
                };
                */

                echo view('templates.ui.auto_fields',[
                    'layout_type'=>'horizontal',
                    'autocolumns'=>$param,
                    'class'=>'blocks-config block-'.$process_name,
                    'autodata'=>$datavalues,
                ]);
        }];
}


if($show_seguradora_data){
    //config: seguradora dados
        $show_count++;
        $process_name='seguradora_data';
        $process_opt = $configProcessNames[$process_name];
        $data[$process_name.'_group']=['title'=>$process_opt['title'],'content'=>function() use($process_name,$process_opt,$account,$datavalues,$user_level,$configProcessNames){
            $param=[];
            if($user_level=='superadmin'){
                $param[$process_name.'--active']=['type'=>'radio','label'=>'Ativar Serviço','list'=>['s'=>'Sim','n'=>'Não'],'class_group'=>'j-service-active'];
                foreach($process_opt['products'] as $p_name => $p_opt){
                    $param[$process_name.'--active_'.$p_name]=['type'=>'radio','label'=>'<span>'.$p_opt['title'].'</span>','list'=>['s'=>'Sim','n'=>'Não'],'class_group'=>'j-group-fields'  ];
                    
                    
                    if($p_name=='boleto_seg'){
                        //exibe a lista dos ramos habilitados para o processo 'boleto_seg'
                        $productsList=[];
                        foreach(array_keys($configProcessNames['seguradora_data']['products']['boleto_seg']['insurers_allow']) as $prod){
                            $productsList[$prod] = $configProcessNames['cad_apolice']['products'][$prod]['title'];
                        }
                        $param[$process_name.'--active_'.$p_name.'_prods[]']=['type'=>'checkbox', 'label'=>'&nbsp;', 'list'=>$productsList, 'class_group'=>'j-group-fields', 'info_html'=>'<small class="text-muted">Caso nenhum item esteja marcado, serão considerados todos</small>' ];
                    }
                    
                    
                }
                
                //dd($productsList);
                
            }else{
                $param[$process_name.'--active']=['type'=>'radio','list'=>['s'=>'Sim'],'class_group'=>'j-service-active hiddenx'];
                $param[$process_name.'--show_cli']=['type'=>'radio','list'=>['s'=>'Sim'],'class_group'=>'hiddenx'];
                foreach($process_opt['products'] as $p_name => $p_opt){
                    $param[$process_name.'--active_'.$p_name]=['type'=>'radio','list'=>['s'=>'Sim','n'=>'Não'],'class_group'=>'hiddenx'];
                }
            }
            $param[$process_name.'--line01']=['type'=>'info','text'=>'<span class="text-muted">É necessário configurar os logins das seguradas no cadastro de cada corretor.</span>','class_group'=>'j-group-fields'];
            //dump($datavalues);
            
            echo view('templates.ui.auto_fields',[
                    'layout_type'=>'horizontal',
                    'autocolumns'=>$param,
                    'class'=>'blocks-config block-'.$process_name,
                    'autodata'=>$datavalues,
                ]);
        }];
}



 
if(!$show_count){
    echo 'Nenhuma configuração disponível';return;
}

//carrega a estrutura do formulário
    echo view('templates.ui.form',[
        'url'=> $user_level=='superadmin' ? route($prefix.'.app.post',['accounts','config_save']) : route($prefix.'.app.post',['account','config_save']),
        'content'=>function() use($account,$data){
            echo '<input type="hidden" name="account_id" value="'.$account->id.'">';
            echo view('templates.ui.accordion',['data'=>$data,'default_hide'=>true,'show_arrow'=>true]);
        }
    ]);


    
if($prefix=='super-admin'){
    $user=Auth::user();
    if($user->user_level=='dev' && $user->id==1){
        echo '<br><br><a href="#" onclick="$(this).next().fadeToggle();">Visualizar configuração</a><div class="hiddenx">';
            dump($config_data);
        echo '</div>';
    }
}
    
@endphp

<script>
(function(){
    var oBlockConfig = $('.blocks-config');
    
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
    
    
    //campos de serviços ativos
    var fCheckActiveFields = function(){
        oBlockConfig.each(function(){
            var base=$(this);
            var rows=base.find('.j-group-fields').not('.j-service-active');
            var input_active = base.find('.j-service-active input:checked').val();
            if(input_active=='s'){
                rows.show();
                fLoginModeFields();
            }else{
                setTimeout(function(){ rows.hide(); },0);
            }
        });
    };
    oBlockConfig.find('.j-service-active input').on('click',fCheckActiveFields);
    fCheckActiveFields();
    
    
    //campo radio 'Baixa de Boletos Segs' (exibe os ramos)
    $('[name=seguradora_data--active_boleto_seg]').on('click',function(){
        var o=$('#form-group-seguradora_data--active_boleto_seg_prod');
        if(this.value=='s'){o.show();}else{o.hide();}
    }).filter(':checked').click();
    
    
})();
</script>
@if($user_level!='superadmin')
<style>
    .tab-login .nav-tabs > .tab-item-5,
    .tab-login .nav-tabs > .tab-item-6
    {display:none !important;}
@php
    if($prefix=='admin')echo '#headingseguradora_files_group{display:none;}'; //oculta esta configuração para o 'admin', pois na atualização de 15/03/2021 o process_name 'seguradora_files' não tem mais login (usa apenas o do cad_apolice)
@endphp
</style>
@endif
    
    