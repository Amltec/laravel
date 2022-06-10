@extends('templates.admin.index')

@section('title')
@php
    echo array_get($configProcessNames,$filter['process_name'].'.title');
    if($title2??false)echo ' - '.$title2;
@endphp
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
        $count_regs_order

    Parâmetros personalizados para os includes (arquivos blade) da pasta pr_process
        $title2 - título complementar de @section(title)
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
if(!$columns_show)$columns_show='id,account,process_prod,data_type,broker,insurer,nome,term_1tx,created_at,status_msg';


$termsList=[];


echo view('templates.components.metabox',[
        'content'=>function() use($filter,$insurers_list,$brokers_list,$status_list,$types_apolices_list,$prefix,$user_logged_level,$configProcessNames,&$termsList,$model,$thisClass){

            $data_type_list = [''=>'']+$types_apolices_list;
            unset($data_type_list['endosso'],$data_type_list['endosso-hist']);//deixa somente as opções 'apolice' e 'historico'


            //lista do campo status
            $filter_status_list = [''=>'Todos','all'=>'Todos + Ignorados'];
            if(in_array($user_logged_level,['dev','superadmin']))$filter_status_list += ['allx'=>'Todos + Ign. + Removidos'];

            $filter_status_list += $status_list;
                //verifica se o status informado é uma combinação de valores e neste caso, adiciona mais uma opção na lista de status
                $tmp=explode(',',join(',',array_keys($status_list)));
                array_push($tmp,',');
                if($filter['status']!='all' && $filter['status']!='order' && str_replace($tmp,'',$filter['status'])!=''){//tem um valor customizado
                    $filter_status_list[$filter['status']]='Personalizado';
                }

            //lista de prioridades
            //$filter_status_list = $filter_status_list + ['order'=>'Prioridades'];

            //formata os valores para exibir no form
            foreach(['dt','dts','dte'] as $dt){
                if($filter[$dt])$filter[$dt]=FormatUtility::dateFormat($filter[$dt]);
            }

            //lista dos nomes dos produtos / ramos
            $prod_list = [''=>''];
            foreach($configProcessNames['cad_apolice']['products'] as $name=>$val){
                $prod_list[$name]=$val['title'];
            }


            //captura a lista de erros agrupados
            $f=$filter['status'];
            if(in_array($f,['e','c','1','i'])){
                $list_code_all = \App\ProcessRobot\ResumeProcessRobot::calcResumeError('cad_apolice',[
                    'account_id' => Config::accountID(),
                    'status_err' => $f
                ]);
                $r=[];
                foreach($list_code_all as $e => $c){
                    if(!$e || in_array($e,['ok','ok2']) )continue;
                    $r[$e] = $thisClass->getStatusCode($e,false) .'('.$c.')';
                }
                $list_code_all=$r;
            }else{
                $list_code_all=[];
            }



            //campos do filtro
            unset($filter['ids']);//remove o campo ids
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
                    'ids'=>['type'=>'hidden'],//campo oculto apenas para não setar o parâmetro interno 'ids' na querystring
                    'account_id'=>['label'=>'ID Conta','class_group'=>''],
                    'status'=>['label'=>'Status ','class_group'=>'','type'=>'select','list'=>$filter_status_list],
                    'id'=>['label'=>'ID','class_group'=>''],
                    'cpf'=>['label'=>'CPF','class_group'=>''],
                    'nome'=>['label'=>'Nome','class_group'=>''],
                    'process_prod'=>['label'=>'Ramo','type'=>'select','list'=>$prod_list],
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

                    //'msg'=>['label'=>'Msg de Retorno','class_group'=>''],
                    'code'=>['label'=>'Msg de Retorno','type'=>'select2','list'=>[''=>''] + $list_code_all,'attr'=>'data-select=\'{"allowClear":true}\''],
                    'req_fill_manual'=>['label'=>'<span title="Requer alteração manual para emitir">Edit Manual<span style="position:absolute;margin:4px 0 0 3px;" class="fa fa-question-circle-o"></span></span>','type'=>'checkbox','list'=>['s'=>'Sim'],],

                    /*'cfilter'=>['label'=>'Filtro','class_group'=>'','type'=>'select','list'=>[
                        ''=>'',
                        'process_test:s'=>'Teste',
                        'error_code:not_insurer'=>'Erro: Seguradora não encontrado',
                        'error_code:repeat'=>'Ignorado: Apólice repetida',
                        'error_code:endosso'=>'Ignorado: Endosso não programado',
                        'error_code:not_product'=>'Ignorado: Produto inválido',
                        'error_code:other'=>'Erro: Outros',
                    ]],*/
                    //'bt'=>['title'=>false,'alt'=>'Filtrar','class_group'=>'','type'=>'submit','color'=>'info','icon'=>'fa-filter','attr'=>'style="margin-top:24px;"','class'=>'j-btn-submit'],
                ]
            ];
            if($prefix=='admin'){
                unset($params['autocolumns']['account_id']);
            }
            if($filter['status']!='c'){//somente ao filtrar os status Operador Manual (c) é que deve exibir este filtro
                unset($params['autocolumns']['req_fill_manual']);
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
                                $offset=$prefix=='admin' ? 11 : 12;
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


            //**** botão filtrar ***
            $params['autocolumns']['bt'] =['title'=>false,'alt'=>'Filtrar','class_group'=>'','type'=>'submit','color'=>'info','icon'=>'fa-filter','attr'=>'style="margin-top:24px;"','class'=>'j-btn-submit'];


            echo view('templates.ui.auto_fields',$params);
        }
]);






//**** informações de filtros adicionais para o programador ***
if(in_array($user_logged_level,['superadmin','dev'])){
    echo '<a href="#" class="text-black" onclick="$(this).next().fadeToggle();"><strong>Filtros adicionais <i class="fa fa-filter" style="font-size:0.9em;"></i></strong></a>';
    echo '<div class="hiddenx" id="div-dev-filters">
        Enviados: <a href="?process_auto=s">Manualmente</a> &nbsp;|&nbsp; <a href="?process_auto=s">Área de Seguradoras</a><br>
        Finalizado Manualmente: <a href="#" class="strong j-dev-filter">?st_change_user=</a> <span style="margin-left:10px;" class="text-muted">Valores: s, n</span><br>
        Forma de Pagamento: <a href="#" class="strong j-dev-filter">?prseg,dados,fpgto_tipo_code=</a> <span style="margin-left:10px;" class="text-muted">Valores: '. http_build_query(\App\ProcessRobot\cad_apolice\Classes\Vars\QuiverVar::$pgto_codes_types,'',', ') .'</span><br>

        <script>
        $("#div-dev-filters").on("click",".j-dev-filter",function(e){
            e.preventDefault();
            var a=$(this);
            var c=a.text().replace("?","").split("=")[0];
            var v=prompt("Digite o valor do campo "+c+"=");
            if(!v)return;
            var u=addQS(null,c+"="+v,"string");
            window.location=u;
        });
        </script>
    </div><br>';
}




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
            'process_prod'=>['Ramo','value'=>function($v,$reg) use($configProcessNames){return array_get($configProcessNames,$reg->process_name.'.products.'.$reg->process_prod.'.title'); }],
            'data_type'=>['Tipo','value'=>function($v,$reg) use($types_apolices_list){ return $types_apolices_list[array_get($reg->seg_data,'data_type','-')]??'-'; }],
            'broker'=>['Corretora','value'=>function($v){ return $v->broker_alias ?? '-'; }],
            'insurer'=>['Seguradora','value'=>function($v){ return $v->insurer_alias ?? '-'; }],
            'process_ctrl_id'=>['Nº Apólice','value'=>function($v){return $v?$v:'-';}],
            'field_apolice_num_quiver'=>['Nº Apólice do Quiver','value'=>function($v,$reg){return $reg->seg_data['apolice_num_quiver']??'-';}],
            'nome'=>['Nome','value'=>function($v,$reg){
                $n=array_get($reg->seg_data,'segurado_nome');
                $i=strpos($n,'--');
                if($n && $i!==false)$n=substr($n,$i+2);
                return '<span title="'.$n.'">'. str_limit($n,25) .'</span>';
            }],

            'field_fpgto_tipo'=>['Forma de Pagamento','value'=>function($v,$reg){return $reg->seg_data['fpgto_tipo_code__text']??'-';}],
            'field_quiver_id'=>['Doc Quiver','alt'=>'Documento do Quiver','value'=>function($v,$reg){return $reg->getData('quiver_id') ?? '-';}],
            'st_change_user'=>['Finalizado Manual','alt'=>'Finalizado manualmente','value'=>function($v,$reg){ return $reg->getData('st_change_user')?'Sim':'-';}],

            'created_at'=>['Cadastro','value'=>function($v){ return FormatUtility::dateFormat($v,'d/m/Y H:i');  }],
            'updated_at'=>['Processamento','value'=>function($v){ return FormatUtility::dateFormat($v,'d/m/Y H:i');  }],
            'status_msg'=>['Status','value'=>function($v,$reg) use($filter,$thisClass, $count_regs_order){
                $s=($reg->process_test?'<span class="label bg-orange margin-r-5">Teste</span> ':'');

                if(in_array($reg->process_status,['e','c','1'])){
                    if(!$filter['status'] || $filter['status']=='allx')$s.= explode(' ',$reg->status_label)[0];
                    $m = array_get($reg->data_array,'error_msg');
                    if($m){
                        $s.= ($s?': ':'') .str_limit($thisClass::getStatusCode($m),30);
                    }
                }else{
                    $s.= $reg->status_label;
                }
                if($reg->getData('req_fill_manual')=='s')$s='<span class="fa fa-exclamation fa-red margin-r-5"></span>'.$s;

                //prioridade dos registros
                if($reg->process_order){
                    $s .= '<span class="pull-right col-item-process_order" title="Registro com prioridade da emissão"><span class="fa fa-arrow-up icon"></span></span>';   // <span class="text">'. ($count_regs_order-($count_regs_order-$reg->process_order)) .'</span>
                }

                return $s;
            }],
        ],
        'columns_show'=>$columns_show,
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
            'actions'=>function($reg) use($user_logged_level){
                $reg->seg_data = $reg->getSegData(false,true);
            },
            'class'=>function($reg){
                if($reg->account->account_status!='a'){
                    return 'text-red';
                }else{
                    return $reg->status_color['text'];
                }
            },
            'lock_del'=>function($reg) use($user_logged_level){
                $is_change_status_data = (in_array($user_logged_level,['dev','superadmin'])) || (!in_array($user_logged_level,['dev','superadmin']) && !in_array($reg->process_status,['e','1']));
                if(!$is_change_status_data)return true;

                if($user_logged_level=='admin'){
                    return in_array($reg->process_status,['f','w']); //finalizado ou pendente de apólice
                }elseif($user_logged_level=='user'){
                    return in_array($reg->process_status,['f','w']); //finalizado ou pendente de apólice
                }else{//dev, superadmin
                    return false;
                }
            },
            'lock_click'=>'deleted'
        ],
        'metabox'=>true,
        'toolbar_buttons'=>[
            ['title'=>false,'alt'=>'Status','icon'=>'fa-pencil','attr'=>'onclick="updateStatus(this);"','class'=>(_GET('is_trash')=='s'?'hiddenx':'j-show-on-select') ],
            'bt_process_order'=>['title'=>false,'alt'=>'Colocar registros como prioridade','icon'=>'fa-arrow-up','class'=>'j-show-on-select','attr'=>'onclick="setOrder();"'],
            'bt_open_multiple' => ['title'=>'<i class="fa fa-plus" style="position:absolute;margin:6px 0 0 -6px;font-size:0.7em;"></i>','alt'=>'Abrir vários registros simultaneamente','icon'=>'fa-mouse-pointer','attr'=>'onclick="openMultiple();"','class'=>'j-show-on-select hiddenx' ],
        ],
    ];


    if(!in_array($user_logged_level,['dev','superadmin'])){
        unset($params['toolbar_buttons']['bt_open_multiple']);
    }


    //campo de ordem / prioridade, somente se estiver na tela 'pronto para emitir', é que será visível
    if($filter['status']!='p')unset($params['toolbar_buttons']['bt_process_order']);

    //*** somente para automóvel ***
    if($filter['process_prod']=='automovel'){
        $params['columns']['automovel_fab'] = ['Fabricante','value'=>function($v,$reg){ return $reg->seg_data['veiculo_fab_code__v_1']??'-';}];
        $params['columns']['automovel_chassi'] = ['Chassi','value'=>function($v,$reg){ return $reg->seg_data['veiculo_chassi_1']??'-';}];

    }




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



    if(in_array($user_logged_level,['dev','superadmin'])){
        if(empty($params['toolbar_buttons_right']))$params['toolbar_buttons_right']=[];
        $params['toolbar_buttons_right'][]=['title'=>false,'alt'=>'Opções Desenvolvedor','icon'=>'fa-codepen','color'=>'link',
            'class_menu'=>'dropdown-menu-right',
            'sub'=>[
                    'get_ids'=>['title'=>'Capturar IDs','onclick'=>'dev_getIds()'],
                    'set_ids'=>['title'=>'Listar IDs','onclick'=>'dev_setIds()'],
                    'review-extract'=>['title'=>'Revisão Manual da Extração','onclick'=>'dev_reviewExtractIds()'],
                ]
            ];
    }else{
        //temporário enquanto estiver em teste
        unset($params['taxs']);//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
    }


    if($prefix=='admin'){
        unset($params['columns']['account']);
    }
    if($user_logged_level=='user'){//negado user_level='user'
        //$params['options']['remove']=false; //desativa a lixeira
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
            $offset=11;
            $params['columns'] = array_slice($params['columns'], 0, $offset, true) +
                                    ['term_'.$term->id.'tx'=>$r] +
                                    array_slice($params['columns'], $offset, NULL, true);

            //***** monta o box da taxonomia padrão **** //obs: este comando pode estar escrito em

        }
    }

echo view('templates.ui.auto_list',$params);



$status_list_change = $status_list;
unset($status_list_change['a'],$status_list_change['o']);//tira os status que são alterados dinamicamente pelo sistema
//if($user_logged_level!='dev')unset($status_list_change['0']);//somente desenvolvedor tem esta permissão
//dd($status_list_change);

//gera um json dos blocos dos processos
$json=[];
foreach($configProcessNames['cad_apolice']['products'] as $prod => $opt){
    $json[$prod] = ['title'=>$opt['title'],'blocks'=>explode(',',$opt['blocks']) ];
}


@endphp
<style>
    .select2-container{width:100% !important;}
    #form-filter-bar .form-group{width:120px;float:left;padding-right:10px;/*display:none;*/}
    #form-filter-bar .form-group-account_id{width:65px;}
    #form-filter-bar .form-group-id{width:80px;}
    #form-filter-bar .form-group-type{width:120px;/*display:block;*/}
    #form-filter-bar .form-group-data_type{width:90px;}
    #form-filter-bar .form-group-dts,#form-filter-bar .form-group-dte,#form-filter-bar .form-group-ctrl_id,#form-filter-bar .form-group-cfilter{width:103px;}
    #form-filter-bar .form-group-status{width:120px;}
    #form-filter-bar .form-group-term_1tx{width:100px;}
    #form-filter-bar .form-group-req_fill_manual{width:90px;}
    #form-filter-bar .j-btn-submit{float:left;}
    .select2-dropdown{min-width:200px;}

    #form-filter-bar .form-group-dtgroup{width:206px;padding-right:0;}
    #form-filter-bar .form-group-dtgroup input{width:calc(50% - 10px);margin-right:10px;}
    #form-filter-bar .form-group-dtgroup .control-label .checkmark{margin-top:-3px !important;}

    .col-account{width:130px;}
    .col-created_at,.col-updated_at{white-space:nowrap;}

    /*col process_order*/
    .col-item-process_order .icon{font-size:10px;}
    .col-item-process_order .text{display:inline-block;width:20px;font-size:12px;position:relative;top:1px;}
</style>
<script>
var configProcessNames={!!json_encode($json)!!};
var oForm=$('#form-filter-bar');
    oForm.append('<input type="hidden" name="ctrl_robo" value="">');//seta um campo vazio para limpar a querystring no submit   //<input type="hidden" name="code" value="">

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
                @if(in_array($user_logged_level,['dev','superadmin']))
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


//botão de alterar order
function setOrder(){
    var list = $('#process_robot_list');
    var ids=list.triggerHandler('get_select');
    if(ids.length==0)return;
    //if(!confirm('Adicionar registros como prioridade na fila no processamento?'))return;
    awAjax({
        url: '{{route($prefix.".app.post",["process_cad_apolice","set_order"])}}',data:{'ids[]':ids},processData:true,
        success: function(r){
            if(!r.success)return false;
            alert((ids.length==1 ? 'Registro adicionado' : 'Registros adicionados') + ' como prioridade na emissão');
            for(var i in ids){
                list.trigger('select',{id:ids[i],select:false});
            }
        }
    });
};


@if(in_array($user_logged_level,['dev','superadmin']))
function dev_getIds(){
    var ids=$('#process_robot_list').triggerHandler('get_select');
    if(ids.length==0){
        alert('Nenhum registro selecionado');
        return;
    }

    var oModal = awModal({
        //class:'modal-vcenter',
        title:'Captura de IDs',
        html:function(oHtml){
            var r=''+
                '<p><span class="j-count-ids">'+ ids.length +'</span> registro(s) selecionado(s)</p> '+
                '<div class="form-group">'+
                    '<div class="control-div">'+
                        '<textarea class="form-control" rows="7" readonly="readonly">'+ ids.join(',') +'</textarea>'+
                    '</div>'+
                '</div>'+
                '<p><button class="btn btn-primary" id="bt-import-ids-all">Importar todos os IDs</button>';

            oHtml.html(r);
            var field=oHtml.find('textarea').on('click',function(){$(this).select();});

            oHtml.find('#bt-import-ids-all').on('click',function(){
                if(confirm('Importar todos os ids desta lista?\nLimite de 10.000 registros.'))
                    awBtnPostData({
                        url:'{{ route($prefix.".app.post",["process_cad_apolice","get_ids_by_qs"]) }}',
                        data:{qs_from:admin_vars.querystring},
                        cb:function(r){
                            if(r.success){field.val(r.ids.join(','));oHtml.find('.j-count-ids').text(r.ids.length);}
                        }
                    },this);
            });
        },
        //btSave:'Salvar',
    });
}
function dev_setIds(){
    var ids=prompt('Informe os ids separados por virgula','');
    if(ids){
        ids=ids.replace(/\n/g,',');
        var url = addQS(admin_vars.url_current+'?'+admin_vars.querystring,'ids='+ids,'string');
        window.location=url;
    }
}


function dev_reviewExtractIds(){
    var ids=prompt('Informe os ids separados por virgula','');
    if(ids){
        ids=ids.replace(/\n/g,',');
        var first_id = ids.split(',')[0];
        var url = admin_vars.url_app+'/process_cad_apolice/'+first_id+'/show?view=review-extract&ids='+ids;
        window.location = url;
    }
}


//Abre vários registros em várias abas
function openMultiple(){
    if(!confirm('Desear abrir os registros selecionados?'))return;
    $('#process_robot_list').triggerHandler('get_select','obj').slice(0,20).each(function(){//limite de 20 registros por vez
        var tr = $(this);
        var url = tr.attr('data-url-click');
        var id = tr.attr('data-id');
        window.open(url,'win'+id);
    });
}

@endif


</script>

@endsection
