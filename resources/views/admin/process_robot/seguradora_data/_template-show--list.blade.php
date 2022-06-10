@php
/**
    Arquivo carregado dentro da visualização do processo principal (ex /super-admin/process_seguradora_data/show?process_prod=...&id=...)
*/


//******* barra de filtros *********
    $params_filter = [
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
            'pr_ids'=>['type'=>'hidden'],//campo oculto apenas para não setar o parâmetro interno 'ids' na querystring
            'pr_id'=>['label'=>'ID','class_group'=>''],
            'cpf'=>['label'=>'CPF','class_group'=>''],
            'nome'=>['label'=>'Nome','class_group'=>''],
            'ctrl_id'=>['label'=>'Nº Apólice','class_group'=>''],
            'status'=>['label'=>'Status','class_group'=>'','type'=>'select','list'=>[''=>'Todos']+$status_pr],
            'dtype'=>['label'=>'Data','type'=>'select','list'=>['dtc'=>'Cadastro Apólice','dtp'=>'Processamento Apólice','dtc_pr'=>'Cadastro Processamento','dtp_pr'=>'Término Processamento']],
            'dts'=>['label'=>'Dt Inicial','class_group'=>'','type'=>'date','placeholder'=>'Inícial'],
            'dte'=>['label'=>'Dt Final','class_group'=>'','type'=>'date','placeholder'=>'Final'],
            'bt'=>['title'=>false,'alt'=>'Filtrar','class_group'=>'','type'=>'submit','color'=>'info','icon'=>'fa-filter','attr'=>'style="margin-top:24px;"','class'=>'j-btn-submit']
        ]
    ];
    echo view('templates.ui.auto_fields',$params_filter);


//******* lista de registros *********
    $params=[
            'list_id'=>'pr_seguradora_data_list',
            'data'=>$modelList ,
            'columns'=>[
                'id'=>'ID', //'id'=>['ID',function($v,$reg){ return $v . ($reg->process_ctrl_id?'.'.$reg->process_ctrl_id:''); }],
                'nome'=>['Nome','value'=>function($v,$reg){ $n=array_get($reg->getSegData(),'segurado_nome','-'); return '<span title="'.$n.'">'. str_limit($n,25) .'</span>'; }],
                'process_prod'=>['Ramo','value'=>function($v) use($configPNCadApolice){return array_get($configPNCadApolice,'products.'.$v.'.title'); }],
                'process_ctrl_id'=>'Nº Apólice',
                'pr_status'=>['Status da Ação','value'=>function($v,$reg) use($status_pr,$model,$thisClass){
                    $prReg = $reg->getPrSeguradoraData($model->process_prod);
                    //dd($v,$reg,$prReg);
                    if(in_array($prReg->status,['p','a']) && $prReg->process_next_at){
                        return '<span class="text-teal">'. ($status_pr[$v]??'-') .' - '. FormatUtility::dateFormat($prReg->process_next_at,'d/m/Y H:i') .'</span>' ;
                    }else{
                        $m = in_array($prReg->status,['p','a']) ? '' : $thisClass->getStatusCode($reg->getData('boleto_status_code'),false);
                        $r = $status_pr[$v]??'';
                        return trim($r .' - '. $m,' - ');
                    }
                }],
                'pr_created_at'=>['Cadastro','value'=>function($v){ return FormatUtility::dateFormat($v,'d/m/Y H:i'); }],
                'pr_finished_at'=>['Término','value'=>function($v){ return FormatUtility::dateFormat($v,'d/m/Y H:i'); }],
            ],
            'options'=>[
                'checkbox'=>true,
                'select_type'=>2,
                'pagin'=>true,
                'confirm_remove'=>true,
                'toolbar'=>true,
                'list_remove'=>false,
                //'regs'=>false,
                'search'=>false
            ],
            'routes'=>[
                'remove'=>route($prefix.'.app.post',['process_seguradora_data','pr_remove_regs']).'?process_id='. $model->id .'&process_prod='. $process_prod,
                'click'=>function($reg) use($prefix){return route($prefix.'.app.show',['process_cad_apolice',$reg->id,'rd='. urlencode(Request::fullUrl()) ]);},
            ],
            'field_click'=>'id',
            'row_opt'=>[
                'class'=>function($reg) use($statusColor){ return $statusColor[$reg->pr_status]['text']; }
            ],
            'metabox'=>true,
            'toolbar_buttons'=>[
                ['title'=>false,'alt'=>'Status','icon'=>'fa-pencil','attr'=>'onclick="updateStatusList(this);"','class'=>(_GET('is_trash')=='s'?'hidden':'j-show-on-select') ],
            ]

    ];
    //dump($user_logged_level,$params);


    //filtro por id da apólice
    if($filter_rel_id){
        $params['toolbar_buttons'][]=['title'=>'Remover filtro #'.$filter_rel_id,'icon'=>'fa-close','href'=>'?process_prod='. $process_prod .'&id='. $model->id ];
    }



echo view('templates.ui.auto_list',$params);


$status_list_change = $status_pr;
unset($status_list_change['a'],$status_list_change['o']);//tira os status que são alterados dinamicamente pelo sistema
@endphp

<style>
    #form-filter-bar .form-group{width:120px;float:left;padding-right:10px;/*display:none;*/}
    #form-filter-bar .form-group-pr_id{width:80px;}
    #form-filter-bar .form-group-data_type{width:90px;}
    #form-filter-bar .form-group-dts,#form-filter-bar .form-group-dte,#form-filter-bar .form-group-ctrl_id{width:103px;}
    #form-filter-bar .form-group-status{width:120px;}
    #form-filter-bar .j-btn-submit{float:left;}
</style>

<script>
var oForm=$('#form-filter-bar');
//aplica a função de barra de filtos
awFilterBar(oForm);



//ao clicar no botão de atualizar
function updateStatusList(bt){
    var ids=$('#pr_seguradora_data_list').triggerHandler('get_select');
    if(ids.length==0)return;

    var oModal = awModal({
        title:'Alteração de Status',
        form:'method="POST" action="{{route($prefix.".app.post",["process_seguradora_data","prChangeAllStatus","id"=>"field-status"])}}"',
        html:function(oHtml){
            var r=''+
                '<p>'+ ids.length +' registro(s) selecionado(s)</p> '+
                '<div class="form-group">'+
                    '<div class="control-div">'+
                        '{!! Form::select("status",[""=>""]+$status_list_change,"",["class"=>"form-control","id"=>"field-status"]) !!}'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'+
                @if(in_array(Auth::user()->user_level,['dev','superadmin']))
                '<div class="form-group hiddenx" id="div-fields-process">'+
                    '<label class="control-label" title="Para limpar o campo, digite: 00/00/0000 00:00">Agendar processo <span class="fa fa-info" style="margin-left:5px;"></label>'+
                    '<div class="control-div">'+
                        '{!! Form::text("next_at","",["placeholder"=>"dd/mm/aaaa hh:mm","class"=>"form-control","data-mask"=>"99/99/9999 99:99"]) !!}'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                    '<br>'+
                '</div>'+
                @endif
                '';

            oHtml.html(r);

            oHtml.find('#field-status').on('change',function(){
                var o=oHtml.find('#div-fields-process').hide();
                var v=this.value;
                if(v=='p')o.show();//0 indexação, p pronto robo
            });
        },
        btSave:'Salvar',
        form_opt:{
            fields_log:false,
            dataFields:{'ids[]':ids,'process_id':'{{$model->id}}',process_prod:'{{$model->process_prod}}'},
            onSuccess:function(opt){
               window.location.reload();
            },
        }
    });
};
</script>
