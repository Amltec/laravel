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
        
        $pr_process
        $servPrCadApolice
        
    Parâmetros personalizados para os includes (arquivos blade) da pasta pr_process
        $title2 - título complementar de @section(title)
*/

$prefix = Config::adminPrefix();

Form::loadScript('inputmask');


$statusColors = $servPrCadApolice::$statusColor;


//*** seleção de colunas ***
$columns_show = _GET('columns_show');
if($columns_show){//get
    Session::put('process.cad_apolice.'. $pr_process .'.columns_show',$columns_show);
}else{//session
    $columns_show=Session::get('process.cad_apolice.'. $pr_process .'.columns_show');
}
if(!$columns_show)$columns_show='id,account,broker,insurer,nome,term_1tx,pr-status,pr-created_at,pr-finished_at,pr-action,pr-user_id,pr-action,pr-is_done';



echo view('templates.components.metabox',[
        'content'=>function() use($filter,$insurers_list,$brokers_list,$status_list,$prefix,$user_logged_level,$configProcessNames,$pr_process){
            
            //lista do campo status
            $filter_status_list = [''=>'Todos'];
            
            $filter_status_list += $status_list;
                //verifica se o status informado é uma combinação de valores e neste caso, adiciona mais uma opção na lista de status
                $tmp=explode(',',join(',',array_keys($status_list)));
                array_push($tmp,',');
                if($filter['status']!='all' && str_replace($tmp,'',$filter['status'])!=''){//tem um valor customizado
                    $filter_status_list[$filter['status']]='Personalizado';
                }
            
            //formata os valores para exibir no form
            foreach(['dt','dts','dte'] as $dt){
                if($filter[$dt])$filter[$dt]=FormatUtility::dateFormat($filter[$dt]);
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
                    'is_done'=>['label'=>'Concluído','type'=>'checkbox','list'=>['s'=>'']],
                    'id'=>['label'=>'ID','class_group'=>''],
                    'cpf'=>['label'=>'CPF','class_group'=>''],
                    'nome'=>['label'=>'Nome','class_group'=>''],
                    'broker_id'=>['label'=>'Corretora','class_group'=>'','type'=>'select2','list'=>[''=>'']+$brokers_list,'attr'=>'data-allow-clear="true"'],
                    'insurer_id'=>['label'=>'Seguradora','class_group'=>'','type'=>'select2','list'=>[''=>'']+$insurers_list,'attr'=>'data-allow-clear="true"'],
                    'ctrl_id'=>['label'=>'Nº Apólice','class_group'=>''],
                    
                    'dt'=>['label'=>'&nbsp;','class_group'=>'','type'=>'hidden'],
                    'dtgroup'=>function() use($filter){ return '<div class="form-group form-group-dtgroup">'.
                                                        '<label class="control-label ">'.
                                                            'Data '.
                                                                '<span style="margin-left:5px;font-weight:normal;"><input type="radio" name="dtype" value="c" '. ($filter['dtype']!='p'?'checked':'') .'><span class="checkmark small2"></span>Cadastro</span>'.
                                                                '<span style="margin-left:5px;font-weight:normal;"><input type="radio"name="dtype" value="f" '. ($filter['dtype']=='f'?'checked':'') .'><span class="checkmark small2"></span>Término</span>'.
                                                        '</label>'.
                                                        '<div class="control-div input-group">'.
                                                            Form::text("dts",$filter['dts'],["placeholder"=>"Inicial","class"=>"form-control","data-mask"=>"99/99/9999"]).
                                                            Form::text("dte",$filter['dte'],["placeholder"=>"Final","class"=>"form-control","data-mask"=>"99/99/9999"]).
                                                            '<span class="help-block"></span>'.
                                                        '</div>'.
                                                    '</div>'; 
                                        },
                ]
            ];
            if($prefix=='admin'){
                unset($params['autocolumns']['account_id']);
            }
            
            
            $params['autocolumns']['bt'] =['title'=>false,'alt'=>'Filtrar','class_group'=>'','type'=>'submit','color'=>'info','icon'=>'fa-filter','attr'=>'style="margin-top:24px;"','class'=>'j-btn-submit'];
            
            
            echo view('templates.ui.auto_fields',$params);
        }
]);




    $params = [
        'list_id'=>'process_robot_list',
        'data'=>$model,
        'columns'=>[
            'id'=>['ID','value'=>function($v,$reg){ return $v . ($reg->process_auto?'*':''); }],
            'account'=>['Conta','value'=>function($v,$reg){
                $n=$reg->account->account_name; 
                $account_cancel = $reg->account->account_status!='a';
                return '<span title="'. ($account_cancel?'Cancelado - ':'') .'#'.$reg->account_id.' - '.$n.'" style="'. ($account_cancel?'text-decoration:line-through;':'') .'">'.str_limit($n,20) .'</span>'; 
            }],
            'broker'=>['Corretora','value'=>function($v){ return $v->broker_alias ?? '-'; }],
            'insurer'=>['Seguradora','value'=>function($v){ return $v->insurer_alias ?? '-'; }],
            'process_ctrl_id'=>['Nº Apólice','value'=>function($v){return $v?$v:'-';}],
            'nome'=>['Nome','value'=>function($v,$reg){ $n=array_get($reg->getSegData(),'segurado_nome','-'); return '<span title="'.$n.'">'. str_limit($n,25) .'</span>'; }],
            'pr-status'=>['Status','value'=>function($v,$reg) use($status_list,$pr_process,$prefix){
                if($pr_process=='apolice_check' && in_array($reg->_pr->status,['m','n','c'])){
                    $cls='btn-primary';
                    if($reg->_pr->status=='c')$cls='bg-navy';
                
                    return '<span class="btn btn-xs '.$cls.'">'. $status_list[$reg->_pr->status] .'</span>';
                }else{
                    return $status_list[$reg->_pr->status]??'-';
                }
            }],
            'pr-user_id'=>['Usuário','value'=>function($v,$reg){$user=$reg->_pr->user; return $user?$user->user_name:'Automático';}],
            'pr-created_at'=>['Cadastro','value'=>function($v,$reg){return FormatUtility::dateFormat($reg->_pr->created_at,'d/m/Y H:i');}],
            'pr-finished_at'=>['Término','value'=>function($v,$reg){$v = FormatUtility::dateFormat($reg->_pr->finished_at,'d/m/Y H:i');return $v?$v:'-';}],
            'pr-is_done'=>['Feito','value'=>function($v,$reg){ return $reg->_pr->is_done ? '<span class="fa fa-check text-green"></span>' : '-'; }],
        ],
        'columns_show'=>$columns_show,
        'options'=>[
            'checkbox'=>true,
            'select_type'=>2,
            'pagin'=>true,
            'remove'=> $user_logged_level=='dev',
            'toolbar'=>true,
            'confirm_remove'=>'Ao excluir, este processo não irá para a lixeira. '.chr(10).'Deseja excluir para sempre?',
            'list_remove'=>false,
            'search'=>false,
            'columns_sel'=>true,
            'is_trash'=>false,
        ],
        'routes'=>[
            'click'=>function($reg) use($prefix,$pr_process){
                $s=$reg->_pr->status;
                if($pr_process=='apolice_check' && in_array($s,['m','n','c'])){
                    if($s=='c'){//correção manual
                        return route($prefix.'.app.show',['process_cad_apolice',$reg->id,'rd='. urlencode(Request::fullUrl()) ]);
                    }else{
                        return route($prefix.'.app.get',['process_cad_apolice_pr','apolice_check_revisao_manual'. ($s=='n'?'2':'') ,'?id='.$reg->id .'&rd='.urlencode(Request::fullUrl())  ]);
                    }
                }elseif(in_array($s,['f','w'])){
                    return route($prefix.'.app.get',['process_cad_apolice','show_data_rel',$reg->id ]) .'?rd='.urlencode(Request::fullUrl());
                }else{
                    //return route($prefix.'.app.show',['process_cad_apolice',$reg->id,'rd='. urlencode(Request::fullUrl()) ]);
                    return route($prefix.'.app.get',['process_cad_apolice','show_data_rel',$reg->id ]) .'?rd='.urlencode(Request::fullUrl());
                }
            },
            'remove'=>route($prefix.'.app.remove','process_cad_apolice_pr') .'?process='.$pr_process,
        ],
        //'field_group'=>'date_group',
        //'field_click'=>'id',
        'row_opt'=>[
            'actions'=>function($reg) use($pr_process){
                $reg->_pr = $reg->getPrCadApolice($pr_process);
            },
            'class'=>function($reg) use($statusColors){
                if($reg->account->account_status!='a'){
                    return 'text-red';
                }else{
                    return $statusColors[$reg->_pr->status]['text'];
                }
            },
        ],
        'metabox'=>true,
        'toolbar_buttons'=>[
            ['title'=>false,'alt'=>'Status','icon'=>'fa-pencil','attr'=>'onclick="updateStatus(this);"','class'=>(_GET('is_trash')=='s'?'hiddenx':'j-show-on-select') ],
        ],
    ];
    
    
    if($user_logged_level!='dev'){
        unset($params['toolbar_buttons']);
    }
    
    
    //somente para verificação de apólices
    if($pr_process=='apolice_check'){
        $params['toolbar_buttons'][]=['title'=>false,'alt'=>'Limpar registros vazios de revisão manual','icon'=>'fa-eraser','attr'=>'onclick="updateClearRevisaoManual();"','class'=>(_GET('is_trash')=='s'?'hiddenx':'j-show-on-select') ];
    }
    
    
    
    
    //opções do desenvolvedor
    if(empty($params['toolbar_buttons_right']))$params['toolbar_buttons_right']=[];
    $params['toolbar_buttons_right'][]=['title'=>false,'alt'=>'Opções Desenvolvedor','icon'=>'fa-codepen','color'=>'link',
        'class_menu'=>'dropdown-menu-right',
        'sub'=>[
                'get_ids'=>['title'=>'Capturar IDs','onclick'=>'dev_getIds()'],
                'set_ids'=>['title'=>'Listar IDs','onclick'=>'dev_setIds()'],
            ]
        ];
    
        
    
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
    #form-filter-bar .form-group-dts,#form-filter-bar .form-group-dte,#form-filter-bar .form-group-ctrl_id,#form-filter-bar .form-group-cfilter{width:103px;}
    #form-filter-bar .form-group-status{width:120px;}
    #form-filter-bar .form-group-term_1tx{width:100px;}
    #form-filter-bar .form-group-is_done {width:70px;}
    
    #form-filter-bar .j-btn-submit{float:left;}
    .select2-dropdown{min-width:200px;}
    
    #form-filter-bar .form-group-dtgroup{width:206px;padding-right:0;}
    #form-filter-bar .form-group-dtgroup input{width:calc(50% - 10px);margin-right:10px;}
    #form-filter-bar .form-group-dtgroup .control-label .checkmark{margin-top:-3px !important;}
    
    .col-account{width:130px;}
</style>
<script>
var configProcessNames={!!json_encode($json)!!};
var oForm=$('#form-filter-bar');
    oForm.append('<input type="hidden" name="code" value=""><input type="hidden" name="ctrl_robo" value="">');//seta um campo vazio para limpar a querystring no submit

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
        form:'method="POST" action="{{route($prefix.".app.post",["process_cad_apolice_pr","changeAllStatus","id"=>"field-status"])}}"',
        html:function(oHtml){
            var r=''+
                '<p>'+ ids.length +' registro(s) selecionado(s)</p> '+
                '<div class="form-group">'+
                    '<div class="control-div">'+
                        '{!! Form::select("status",[""=>""]+$status_list_change,"",["class"=>"form-control","id"=>"field-status"]) !!}'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'+
                '';
            
            oHtml.html(r);
        },
        btSave:'Salvar',
        form_opt:{
            dataFields:{'ids[]':ids,process:'{{$pr_process}}'},
            onSuccess:function(opt){
               window.location.reload();
            },
        }
    });
};
//Limpa os registros vazios/não concluídos de revisão manual 
function updateClearRevisaoManual(){
    var ids=$('#process_robot_list').triggerHandler('get_select');
    if(ids.length==0)return;
    if(confirm('Esta ação irá remover os registros revisão manual que não foram finalizados. \n\nDeseja continuar?')){
        awAjax({
            url: '{{route($prefix.".app.post",["process_cad_apolice_pr","clear_revisao_manual"])}}',data:{'ids[]':ids},processData:true,
            success: function(){window.location.reload();},
            error:function(xhr){
                awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
            }
        });
    }
};



@if(in_array($user_logged_level,['dev']))
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
                        url:'{{ route($prefix.".app.post",["process_cad_apolice_pr","get_ids_by_qs"]) }}',
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
@endif


</script>

@endsection
