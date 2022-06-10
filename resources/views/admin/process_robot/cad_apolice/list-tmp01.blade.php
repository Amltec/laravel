@extends('templates.admin.index')

@section('title')
{{array_get($configProcessNames,$filter['process_name'].'.title')}}
@endsection


@section('content-view')
@php
/*  Parâmetros esperados:
        $model
        $filter
        $configProcessNames
        $insurers_list
        $brokers_list
        $status_list
        $user_logged_level
        $types_apolices_list
*/

$prefix = Config::adminPrefix();

Form::loadScript('inputmask');


//*** seleção de colunas ***
$columns_show = _GET('columns_show');
if($columns_show){//get
    Session::put('process.cad_apolice.columns_show',$columns_show);
}else{//session
    $columns_show=Session::get('process.cad_apolice.columns_show');
}
if(!$columns_show)$columns_show='id,account,process_name_prod,data_type_label,broker,insurer,nome,term_1tx,created_at,status_msg';
    

$termsList=[];

echo view('templates.components.metabox',[
        'content'=>function() use($filter,$insurers_list,$brokers_list,$status_list,$types_apolices_list,$prefix,$user_logged_level,$configProcessNames,&$termsList){
            
            $data_type_list = [''=>'']+$types_apolices_list;
            unset($data_type_list['endosso'],$data_type_list['endosso-hist']);//deixa somente as opções 'apolice' e 'historico'
            
            
            //lista do campo status
            $filter_status_list = [''=>'Todos','all'=>'Todos + Ignorados'];
            if(in_array($user_logged_level,['dev','superadmin']))$filter_status_list += ['allx'=>'Todos + Ign. + Removidos'];
            $filter_status_list += $status_list;
            
            //formata os valores para exibir no form
            foreach(['dt','dts','dte'] as $dt){
                if($filter[$dt])$filter[$dt]=FormatUtility::dateFormat($filter[$dt]);
            }
            
            //campos do filtro
            $params = [
                'form'=>[
                    'id'=>'form-filter-bar',
                    'url_action'=>'#',
                    'alert'=>false,
                    'data_opt'=>[
                        'fields_log'=>false,//desativa os campos de log
                    ]
                ],
                'autodata'=>(object)$filter,
                'autocolumns'=>[
                    'account_id'=>['label'=>'ID Conta','class_group'=>''],
                    'status'=>['label'=>'Status ','class_group'=>'','type'=>'select','list'=>$filter_status_list],
                    'id'=>['label'=>'ID','class_group'=>''],
                    'cpf'=>['label'=>'CPF','class_group'=>''],
                    'nome'=>['label'=>'Nome','class_group'=>''],
                    'data_type'=>['label'=>'Tipo','class_group'=>'','type'=>'select','list'=>$data_type_list],
                    'broker_id'=>['label'=>'Corretora','class_group'=>'','type'=>'select2','list'=>[''=>'']+$brokers_list,'attr'=>'data-allow-clear="true"'],
                    'insurer_id'=>['label'=>'Seguradora','class_group'=>'','type'=>'select2','list'=>[''=>'']+$insurers_list,'attr'=>'data-allow-clear="true"'],
                    'ctrl_id'=>['label'=>'Nº Apólice','class_group'=>''],
                    
                    'dt'=>['label'=>'&nbsp;','class_group'=>'','type'=>'hidden'],
                    /*
                    'dtype'=>['label'=>'&nbsp;<span>Período</span>','type'=>'select','list'=>['Cadastro','Processamento']],
                    'dts'=>['label'=>'&nbsp;','class_group'=>'','type'=>'date','placeholder'=>'Inícial'],
                    'dte'=>['label'=>'&nbsp;','class_group'=>'','type'=>'date','placeholder'=>'Final'],
                    */
                    
                    'dtgroup'=>function() use($filter){ return '<div class="form-group form-group-dtgroup">'.
                                                        '<label class="control-label ">'.
                                                            'Data '.
                                                                '<span style="margin-left:5px;font-weight:normal;"><input type="radio" name="dtype" value="c" '. ($filter['dtype']!='p'?'checked':'') .'><span class="checkmark small2"></span>Cadastro</span>'.
                                                                '<span style="margin-left:5px;font-weight:normal;"><input type="radio"name="dtype" value="p" '. ($filter['dtype']=='p'?'checked':'') .'><span class="checkmark small2"></span>Processo</span>'.
                                                        '</label>'.
                                                        '<div class="control-div input-group">'.
                                                            Form::text("dts",$filter['dts'],["placeholder"=>"Inicial","class"=>"form-control","data-mask"=>"99/99/9999"]).
                                                            Form::text("dte",$filter['dte'],["placeholder"=>"Final","class"=>"form-control","data-mask"=>"99/99/9999"]).
                                                            '<span class="help-block"></span>'.
                                                        '</div>'.
                                                    '</div>'; 
                                        },
                   
                    'msg'=>['label'=>'Msg de Retorno','class_group'=>''],
                    
                    /*'cfilter'=>['label'=>'Filtro','class_group'=>'','type'=>'select','list'=>[
                        ''=>'',
                        'process_test:s'=>'Teste',
                        'error_code:not_insurer'=>'Erro: Seguradora não encontrado',
                        'error_code:repeat'=>'Ignorado: Apólice repetida',
                        'error_code:endosso'=>'Ignorado: Endosso não programado',
                        'error_code:not_product'=>'Ignorado: Produto inválido',
                        'error_code:other'=>'Erro: Outros',
                    ]],*/
                    'bt'=>['title'=>false,'alt'=>'Filtrar','class_group'=>'','type'=>'submit','color'=>'info','icon'=>'fa-filter','attr'=>'style="margin-top:24px;"','class'=>'j-btn-submit'],
                ]
            ];
            if($prefix=='admin'){
                unset($params['autocolumns']['account_id']);
            }
            
            
            //***** monta o box da taxonomia padrão ****
            if($user_logged_level=='dev'){//TEMPORÁRIO ESTE IF enquanto estiver em teste --- //XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
                    $terms = $configProcessNames['cad_apolice']['terms']??null;
                    $termsList = \App\Models\Term::whereIn('id',$terms)->get();
                    
                    if($termsList){
                            //campo geral para armazenar os ids taxs selecionados
                            $params['autocolumns']['taxs_id']=['type'=>'hidden','value'=>_GET('taxs_id')];
                            
                            //botão que abre a janela taxs
                            foreach($termsList as $term){
                                $term_id = $term->id;
                                $r=['title'=>'Selecionar','label'=>$term->term_title,'type'=>'button_field','onclick'=>'fTaxOpen(this,'.$term_id.')'];
                                
                                //adiciona antes do campo msg retorno
                                $offset=12;
                                $params['autocolumns'] = array_slice($params['autocolumns'], 0, $offset, true) +
                                                        ['term_'.$term_id.'tx'=>$r] +
                                                        array_slice($params['autocolumns'], $offset, NULL, true);
                                
                                //***** monta o box da taxonomia padrão **** //obs: este comando pode estar escrito em qualquer parte do código
                                echo view('templates.ui.taxs_form',[
                                            'id'=>'autofilter_box_terms_'.$term_id,
                                            'term_id'=>$term_id,
                                            'is_collapse'=>true,
                                            'is_popup'=>true,
                                            'start_collapse'=>true,
                                            'taxs_start'=>$filter['taxs_id'],
                                            'is_add'=>false,
                                            'is_header'=>false,
                                            'class'=>'j-autofilter_box_terms',
                                        ]);
                            }
                    }
            }
            //dd($params);
            
            
            echo view('templates.ui.auto_fields',$params);
        }
]);



$params = [
        'list_id'=>'process_robot_list',
        'data'=>$model,
        'columns'=>[
            //'date_group'=>['Data Grupo','value'=>function($v,$reg){ return FormatUtility::dateFormat($reg->created_at,'d/m/Y'); }],
            'id'=>['ID','value'=>function($v,$reg){ return $v . ($reg->process_auto?'*':''); }],
            'account'=>['Conta','value'=>function($v,$reg){
                $n=$reg->account->account_name; 
                $account_cancel = $reg->account->account_status!='a';
                return '<span title="'. ($account_cancel?'Cancelado - ':'') .'#'.$reg->account_id.' - '.$n.'" style="'. ($account_cancel?'text-decoration:line-through;':'') .'">'.str_limit($n,20) .'</span>'; 
            }],
            'process_name_prod'=>['Ramo','value'=>function($v,$reg) use($configProcessNames){return array_get($configProcessNames,$reg->process_name.'.products.'.$reg->process_prod.'.title'); }],
            'data_type'=>['Tipo','value'=>function($v,$reg){ return array_get($reg->getSegData(),'data_type','-'); }],
            'broker'=>['Corretora','value'=>function($v){ return $v->broker_alias ?? '-'; }],
            'insurer'=>['Seguradora','value'=>function($v){ return $v->insurer_alias ?? '-'; }],
            'nome'=>['Nome','value'=>function($v,$reg){ $n=array_get($reg->data_array,'segurado_nome','-'); return '<span title="'.$n.'">'. str_limit($n,25) .'</span>'; }],
            'created_at'=>['Cadastro','value'=>function($v){ return FormatUtility::dateFormat($v,'d/m/Y H:i');  }],
            'updated_at'=>['Processamento','value'=>function($v){ return FormatUtility::dateFormat($v,'d/m/Y H:i');  }],
            'status_msg'=>['Informações','value'=>function($v,$reg)use($filter){
                if(in_array($reg->process_status,['e','c','i'])){
                    $data_arr = $reg->data_array;
                    $count = (int)($data_arr['return_count']??0);
                    $mtitle=$data_arr[$count==0 ? 'error_msg' : 'error_msg_'.$count]??'';//captura a última mensagem retornada
                    $m=$mtitle;
                    if(substr(strtolower($m),0,8)=='{blocks:')$m=substr($m,strpos($m,'*sep*')+5);
                    $m=str_limit($m,30);
                    $label = $filter['status']?'':$reg->status_label;
                }else{
                    $mtitle='';
                    $m='';
                    $label = $reg->status_label;
                }
                return ($reg->process_test?'<span class="label bg-orange margin-r-5">Teste</span>':'').
                        '<span>'. trim($label.' '. $m).'</span>';
            }],
            'process_ctrl_id'=>'Apólice',
            //'doc'=>['CPF/CNPJ','value'=>function($v,$reg){ return array_get($reg->getText('data'),'segurado_doc','-'); }],
            'vigenciai'=>['Vigência I','value'=>function($v,$reg){ return array_get($reg->getText('data'),'inicio_vigencia','-'); }],
            'chassi'=>['Chassi','value'=>function($v,$reg){ return array_get($reg->getText('data'),'veiculo_chassi','-'); }],
            'fpgto_juros_md'=>['Juros_MD','value'=>function($v){ return $v?$v:'-'; }],
        ],
        //'columns_show'=>$columns_show,
        'options'=>[
            'checkbox'=>true,
            'select_type'=>2,
            'pagin'=>true,
            'confirm_remove'=>true,
            'toolbar'=>true,
            'list_remove'=>false,
            //'regs'=>false,
            'search'=>false,
            'columns_sel'=>true
        ],
        'routes'=>[
            'click'=>function($reg) use($prefix){return route($prefix.'.app.show',['process_cad_apolice',$reg->id,'rd='. urlencode(Request::fullUrl()) ]);},
            'remove'=>route($prefix.'.app.remove','process_cad_apolice'),
        ],
        //'field_group'=>'date_group',
        //'field_click'=>'id',
        'row_opt'=>[
            'class'=>function($reg){
                if($reg->account->account_status!='a'){
                    return 'text-red';
                }else{
                    return $reg->status_color['text'];
                }
            },
            'lock_del'=>function($reg) use($user_logged_level){
                if($user_logged_level=='admin'){
                    return in_array($reg->process_status,['f','w']);//finalizado ou pendente de apólice
                }elseif($user_logged_level=='user'){
                    return in_array($reg->process_status,['f','w']);//finalizado ou pendente de apólice
                }else{//dev, superadmin
                    return false;
                }
            },
            'lock_click'=>'deleted'
        ],
        'metabox'=>true,
        'toolbar_buttons'=>[
            ['title'=>false,'alt'=>'Status','icon'=>'fa-pencil','attr'=>'onclick="updateStatus(this);"','class'=>(_GET('is_trash')=='s'?'hiddenx':'j-show-on-select') ],
        ],
    ];
    
    
    //adiciona os botões de taxonomias na lista
    $r=[];
    
    if($termsList){
        foreach($termsList as $term){
            $r[ $term->id ]=[
                'show_list'=>'term_'.$term->id.'tx',
                'button'=>['icon'=>'fa-tags','color'=>'default','title'=>false,'alt'=>$term->term_title],
                'area_name'=>'cad_apolice',
                'tax_form_type'=>'set',
                'term_model'=>$term
            ];
        }
    }
    if($r)$params['taxs']=$r;
    
    
    
    if($user_logged_level!='dev'){//temporário enquanto estiver em teste
         unset($params['taxs']);//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    }
    

    if($prefix=='admin'){
        unset($params['columns']['account']);
    }
    if($user_logged_level=='user'){//negado user_level='user'
        $params['options']['remove']=false; //desativa a lixeira
        $params['options']['is_trash']=false; //desativa a lixeira
    }else if(in_array($user_logged_level,['dev','superadmin'])){
        $params['options']['list_remove']=true; //ativa a opção de exibir da lixeira
    }
    
    
    
    //***** monta as colunas das taxonomias ****
    if($termsList){
        foreach($termsList as $term){
            //conteúdo da coluna
            $r=[$term->term_title];
            
            //adiciona a coluna antes do campo status_msg
            $offset=10;
            $params['columns'] = array_slice($params['columns'], 0, $offset, true) +
                                    ['term_'.$term->id.'tx'=>$r] +
                                    array_slice($params['columns'], $offset, NULL, true);

            //***** monta o box da taxonomia padrão **** //obs: este comando pode estar escrito em 
            
        }
        
    }
    
    
    
    
echo view('templates.ui.auto_list',$params);
    
    
    
$status_list_change = $status_list;
unset($status_list_change['a'],$status_list_change['o']);//tira os status que são alterados dinamicamente pelo sistema
if($user_logged_level!='dev')unset($status_list_change['0']);//somente desenvolvedor tem esta permissão


//gera um json dos blocos dos processos
$json=[];
foreach($configProcessNames['cad_apolice']['products'] as $prod => $opt){
    $json[$prod] = ['title'=>$opt['title'],'blocks'=>explode(',',$opt['blocks']) ];
}


@endphp
<style>
    .select2-container{width:100% !important;}
    #form-filter-bar .form-group{width:120px;float:left;padding-right:10px;/*display:none;*/}
    #form-filter-bar .form-group-account_id,#form-filter-bar .form-group-id{width:65px;}
    #form-filter-bar .form-group-type{width:120px;/*display:block;*/}
    #form-filter-bar .form-group-data_type{width:90px;}
    #form-filter-bar .form-group-dts,#form-filter-bar .form-group-dte,#form-filter-bar .form-group-ctrl_id,#form-filter-bar .form-group-cfilter{width:103px;}
    #form-filter-bar .form-group-status{width:120px;}
    #form-filter-bar .form-group-term_1tx{width:100px;}
    #form-filter-bar .j-btn-submit{float:left;}
    .select2-dropdown{min-width:200px;}
    
    #form-filter-bar .form-group-dtgroup{width:206px;padding-right:0;}
    #form-filter-bar .form-group-dtgroup input{width:calc(50% - 10px);margin-right:10px;}
    #form-filter-bar .form-group-dtgroup .control-label .checkmark{margin-top:-3px !important;}
    
    .col-account{width:130px;}
    .col-created_at,.col-updated_at{white-space:nowrap;}
    
</style>
<script>
var configProcessNames={!!json_encode($json)!!};
var oForm=$('#form-filter-bar');

//aplica a função de barra de filtos
awFilterBar(oForm);


//botões de taxonomia do filtro
$('.j-autofilter_box_terms').on('onClickItem',function(e,opt){
    var oSels=$(this).triggerHandler('get_select');
    oForm.find('[name=taxs_id]').val(oSels.join(','));
});
function fTaxOpen(bt,term_id){
    var oTaxBox=$('#autofilter_box_terms_'+term_id);
    oTaxBox.trigger('show',{position:$(bt)});
};


//ao clicar no botão de atualizar
function updateStatus(bt){
    var ids=$('#process_robot_list').triggerHandler('get_select');
    if(ids.length==0)return;
    
    var oModal = awModal({
        title:'Alteração de Status',
        form:'method="POST" action="{{route($prefix.".app.post",["process_cad_apolice","changeAllStatus","id"=>"field-status"])}}"',
        html:function(oHtml){
            var r=''+
                '<p>'+ ids.length +' registro(s) selecionado(s)</p> '+
                '<div class="form-group">'+
                    '<div class="control-div">'+
                        '{!! Form::select("status",[""=>""]+$status_list_change,"",["class"=>"form-control","id"=>"field-status"]) !!}'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'+
                @if($user_logged_level=='dev')
                '<div class="form-group hiddenx" id="div-fields-process">'+
                    '<label class="control-label">Agendar processo</label>'+
                    '<div class="control-div">'+
                        '{!! Form::text("next_at","",["placeholder"=>"dd/mm/aaaa hh:mm","class"=>"form-control","data-mask"=>"99/99/9999 99:99"]) !!}'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                    '<br>'+
                    '<label class="control-label">Processar Blocos</label>'+
                    '<div class="control-div">';
                    
                    var i,x,a,b;
                    for(i in configProcessNames){
                        a=configProcessNames[i];
                        r+='<strong class="margin-r-5 inlineblock" style="width:70px;">'+a.title+':</strong> ';
                        for(x in a.blocks){
                            b=a.blocks[x];
                            r+='<label class="nostrong margin-r-5"><input type="checkbox" name="'+i+'-blocks[]" value="'+ b +'"><span class="checkmark small1"></span> '+ b +'</label> ';
                        }
                        r+='<br>'
                    }
                    
                    r+= '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'+
                @endif
                '';
            
            oHtml.html(r);
            oHtml.find('#field-status').on('change',function(){
                var o=oHtml.find('#div-fields-process').hide();
                var v=this.value;
                if(v=='0' || v=='p')o.show();//0 indexação, p pronto robo
            });
        },
        btSave:'Salvar',
        form_opt:{
            dataFields:{'ids[]':ids},
            @if(in_array($user_logged_level,['admin','user']))
            onBefore:function(opt){
                if($('#field-status').val()=='f' && !confirm('Ao confirmar como FINALIZADO, não poderá ser mais alterado. Deseja continuar?')){window.location.reload();return false;}
            },
            @endif
            onSuccess:function(opt){
               window.location.reload();
            },
        }
    });
};
</script>

@endsection
