@extends('templates.admin.index')

@section('title')
Baixa de Boletos das Seguradoras e Upload no Quiver
@endsection


@section('content-view')
@php
/*
Variáveis recebidas
    process_name
    process_prod
    model
    filter
    user_logged
    user_logged_level
    thisClass
    prefix
    configPNCadApolice
*/


$status_pr = $thisClass::$status_pr;
$status_color = $thisClass::$statusColor;


$insurers_list = \App\Models\Insurer::pluck('insurer_alias','id')->toArray();
$brokers_list = \App\Models\Broker::pluck('broker_alias','id')->toArray();

echo view('templates.components.metabox',[
        'content'=>function() use($filter,$status_pr,$prefix,$insurers_list,$brokers_list,$user_logged_level){

            $filter_list_status=[
                ''=>'',
                'Boleto' => [
                    'b_p'=>'Aguardando robô',
                    'b_a'=>'Em Andamento',
                    'b_f'=>'Finalizado sem alterações',
                    'b_w'=>'Finalizado com boleto',
                    'b_s'=>'Parados',
                    'b_e'=>'Erro',
                    'b_1'=>'Em Análise',
                    'no_reg'=>'Sem registro de processo na Ação no Quiver',
                    ''=>'',
                ],
                'Quiver' => [
                    'q_p'=>'Aguardando robô',
                    'q_a'=>'Em Andamento',
                    'q_w'=>'Finalizado',
                    'q_f'=>'Finalizado sem alterações',
                    'q_s'=>'Parados',
                    'q_e'=>'Erro',
                    'q_1'=>'Em Análise',
                ],
            ];
            if($prefix!='super-admin'){
                unset($filter_list_status['Boleto']['no_reg']);
            }
            //dd($filter_list_status);

            //captura a lista de erros agrupados para boleto_seg e boleto_quiver
            $opt = $prefix=='super-admin' ? [] : ['account_id'=>Config::accountID()];
            $calc_boleto_seg = \App\ProcessRobot\ResumeProcessRobot::SeguradoraData_calcBoleto($opt + ['process_prod'=>'boleto_seg']);
            $calc_boleto_quiver = \App\ProcessRobot\ResumeProcessRobot::SeguradoraData_calcBoleto($opt + ['process_prod'=>'boleto_quiver']);

            $list_code_boleto_seg = FormatUtility::pluckKey($calc_boleto_seg['errors'],'text');
            $list_code_boleto_quiver = FormatUtility::pluckKey($calc_boleto_quiver['errors'],'text');

            $list_code_all = [''=>''];
            if($list_code_boleto_seg)$list_code_all['Boleto'] = $list_code_boleto_seg;
            if($list_code_boleto_quiver)$list_code_all['Quiver'] = $list_code_boleto_quiver;



            //seta um valor inicial para que possa vir preenchido o campo no form filter abaixo
            //$filter['code_seg'] = $filter['code'];
            //$filter['code_quiver'] = $filter['code'];

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
                    'account_id'=>['label'=>'ID Conta'],
                    'id'=>['label'=>'Apo. ID','class_group'=>''],
                    //'process_prod'=>['label'=>'Ação','class_group'=>'','type'=>'select','list'=>['boleto_seg'=>'Boleto na Seguradora','boleto_quiver'=>'Ação no Quiver']],//''=>'Todos',
                    'status'=>['label'=>'Status','class_group'=>'','type'=>'select','list'=>$filter_list_status],
                    'cpf'=>['label'=>'CPF','class_group'=>''],
                    'nome'=>['label'=>'Nome','class_group'=>''],
                    'broker_id'=>['label'=>'Corretora','class_group'=>'','type'=>'select2','list'=>[''=>'']+$brokers_list,'attr'=>'data-allow-clear="true"'],
                    'insurer_id'=>['label'=>'Seguradora','class_group'=>'','type'=>'select2','list'=>[''=>'']+$insurers_list,'attr'=>'data-allow-clear="true"'],
                    'dts'=>['label'=>'Dt Process Inicial','class_group'=>'','type'=>'date'],
                    'dte'=>['label'=>'Dt Process Final','class_group'=>'','type'=>'date'],
                    //'code_seg'=>['label'=>'Erros Seg','type'=>'select','list'=>[''=>''] + $list_code_boleto_seg],
                    //'code_quiver'=>['label'=>'Erros Quiv','type'=>'select','list'=>[''=>''] + $list_code_boleto_quiver],
                    'code'=>['label'=>'Erros','type'=>'select','list'=>$list_code_all],
                    'bt'=>['title'=>false,'alt'=>'Filtrar','class_group'=>'','type'=>'submit','color'=>'info','icon'=>'fa-filter','attr'=>'style="margin-top:24px;"','class'=>'j-btn-submit'],
                ]
            ];
            echo view('templates.ui.auto_fields',$params);
        }
]);



    $params=[
            'list_id'=>'pr_seguradora_data_list',
            'data'=>$model,
            'columns'=>[
                'id'=>['Apo. ID','alt'=>'ID da Apólice'], //'id'=>['ID',function($v,$reg){ return $v . ($reg->process_ctrl_id?'.'.$reg->process_ctrl_id:''); }],
                'account'=>['Conta','value'=>function($v,$reg){
                    $n=$reg->account->account_name;
                    $account_cancel = $reg->account->account_status!='a';
                    return '<span title="'. ($account_cancel?'Cancelado - ':'') .'#'.$reg->account_id.' - '.$n.'" style="'. ($account_cancel?'text-decoration:line-through;':'') .'">'.str_limit($n,20) .'</span>';
                }],
                'nome'=>['Segurado','value'=>function($v,$reg) use($configPNCadApolice,$prefix){
                    $n=array_get($reg->getSegData(),'segurado_nome','-');
                    return '<a class="strong" href="'. route($prefix.'.app.show',['process_cad_apolice',$reg->id,'rd='. urlencode(Request::fullUrl()) ]) .'" title="'.$n.'">'. str_limit($n,25) .'</span>';
                }],
                'broker'=>['Corretora','value'=>function($v,$reg){
                    return $reg->broker->broker_alias;
                }],
                'insurer'=>['Seguradora','value'=>function($v,$reg){
                    return $reg->insurer->insurer_alias;
                }],
                /*'apo_num'=>['Apólice','value'=>function($v,$reg){
                    return $reg->ctrl_process_id;
                 }],*/
                'apo_ramo'=>['Ramo','value'=>function($v,$reg) use($configPNCadApolice){
                    return array_get($configPNCadApolice,'products.'.$reg->process_prod.'.title');
                 }],
                'boleto_seg'=>['Boleto','alt'=>'Baixa de Boleto na Seguradora','value'=>function($v,$reg) use($status_pr,$status_color,$thisClass){
                    $prdata = $reg->boleto_seg;
                    $s = $prdata->status??null;
                    if($s=='f' || $s=='w'){//concluído quiver
                        $r = '<i title="Finalizados" class="fa fa-check text-green"></i>';
                    }elseif($s=='e'){
                        $r = '<i title="Erros" class="fa fa-close text-red"></i>';
                    }elseif($s=='a'){
                        $r = '<i title="Em processamento" class="fa fa-circle-o-notch text-muted fa-spin"></i>';
                    }else{;//$s=='p')
                        $r = '<i title="Pronto para processar" class="fa fa-circle-o text-muted"></i>';
                    }
                    return $r;
                }],
                'boleto_quiver'=>['Quiver','alt'=>'Ação no Quiver','value'=>function($v,$reg) use($status_pr,$status_color){
                    $prdata = $reg->boleto_seg;
                    $s = $prdata->status??null;
                    $prdata2 = $reg->boleto_quiver;
                    $s2 = $prdata2->status??null;

                    //$p_s = $reg->seg_parent_status;
                    $p_q = $reg->quiv_parent_status;

                    //if($reg->id==613)dump($s,$s2);
                    if(($s=='a' || $s=='p' || $s=='e') && (empty($s2) || $s2=='p' || $s2=='a')){
                        $r = '<i title="Não iniciado" class="text-green">-</i>';
                    }elseif($s=='f'){
                        $r = '<i title="Finalizados sem alterações" class="fa fa-check text-color-disable"></i>';
                    }elseif($s2=='w'){//concluído quiver
                        $r = '<i title="Finalizados" class="fa fa-check text-green"></i>';
                    }elseif($s2=='e'){
                        $r = '<i title="Erros" class="fa fa-close text-red"></i>';
                    }elseif($s2=='a'){
                        if($p_q=='e'){
                            $r = '<i title="Erros" class="fa fa-close text-red"></i>';
                        }else{
                            $r = '<i title="Em processamento" class="fa fa-circle-o-notch text-muted fa-spin"></i>';
                        }
                    }elseif($s2=='p'){
                        if($p_q=='e'){
                            $r = '<i title="Erros" class="fa fa-close text-red"></i>';
                        }else{
                            $r = '<i title="Pronto para processar" class="fa fa-circle-o text-muted"></i>';
                        }
                    }else{;//$s2==null
                        if($s2=='f'){//quiver - finalizado sem alterção
                            $r = '<i title="Finalizados sem alterações" class="fa fa-check text-color-disable"></i>';
                        }else{
                            $r = '<i title="Erro: não existe registro de ação no Quiver" class="fa fa-minus text-red"></i>';
                        }
                    }
                    //$r.='|s='.$s.', s2='.$s2.', p_q='.$p_q.'|';
                    return $r;

                }],
                'msg_ret'=>['Retorno','value'=>function($v,$reg) use($status_pr,$status_color,$thisClass){
                    $prdata = $reg->boleto_seg;
                    $s1 = $prdata->status??null;
                    $prdata2 = $reg->boleto_quiver;
                    $s2 = $prdata2->status??null;

                    $st = $s2 ?? $s1;
                    $status = $status_pr[$st];
                    $msg = '';

                    $n=null;
                    if($s2 && in_array($s2,['p','a']) && $prdata2->process_next_at){
                        $n=$prdata2->process_next_at;

                    }elseif($s1 && in_array($s1,['p','a']) && $prdata->process_next_at){
                        $n=$prdata->process_next_at;
                    }
                    //if($reg->id==749)dd($prdata);

                    if($s2){//retorno do boleto_quiver
                        //if(\Auth::user() && \Auth::user()->id==1)dd($v,$reg);
                        $msg = $thisClass->getStatusCode($reg->getData('boleto_status_code'),false);
                        if($msg)$status.='<br><small>'.$msg.'</small>';
                    }else{//retorno do boleto_seg

                    }

                    if($n){
                        return '<span class="text-teal">'. $status .' - '. FormatUtility::dateFormat($n,'d/m/Y H:i') .'</span>' ;
                    }else{
                        return '<span class="'. $status_color[$st]['text'] .'">'. $status .'</span>';
                    }
                }],
                'finished_at'=>['Conclusão','alt'=>'Conclusão: Q Quiver, S Seguradora, C Cadastro','value'=>function($v,$reg){
                    $prdata = $reg->boleto_quiver;
                    $prdata2 = $reg->boleto_seg;
                    $s = $prdata->status??null;
                    $s2 = $prdata2->status??null;
                    $l = '';
                    $d = null;
                    if(in_array($s2,['f','w'])){//concluído quiver
                        $d = $prdata2->finished_at;
                        $l = 'Q';
                    }elseif(in_array($s,['f','w'])){//concluído seguradora
                        $d = $prdata->finished_at;
                        $l = 'S';
                    }else{//nada concluído - data cadastro
                        $d = $v;
                        $l = 'C';
                    }
                    return $d ? FormatUtility::dateFormat($d,'d/m/Y H:i') : '-';    //'<span style="color:#ccc;margin-left:5px;">'.$l .'</span>'
                }],
            ],
            'options'=>[
                'checkbox'=>true,
                'select_type'=>2,
                'pagin'=>true,
                'toolbar'=>true,
                'search'=>false,
                'remove'=>false,
            ],
            'row_opt'=>[
                'actions'=>function($reg){
                    $boleto_status_code = $reg->getData('boleto_status_code');//status_code retornado da ação do boleto_quiver

                    $reg->boleto_seg = $reg->getPrSeguradoraData('boleto_seg');
                    $reg->boleto_quiver = $reg->getPrSeguradoraData('boleto_quiver');

                    //dados do registro pai
                    $modelParent = $reg->boleto_seg->process_seguradora_data??null;//registro pai seguradora data
                    $reg->seg_parent_status = $modelParent->process_status??'';
                    $reg->seg_parent_code = $modelParent ? $modelParent->getData('error_msg') : '';

                    $modelParent = $reg->boleto_quiver->process_seguradora_data??null;//registro pai seguradora data
                    $reg->quiv_parent_status = $modelParent->process_status??'';
                    $reg->quiv_parent_code = $boleto_status_code ? $boleto_status_code : ($modelParent ? $modelParent->getData('error_msg') : '');
                },
                'class'=>function($reg) use($status_color){
                    if(in_array(data_get($reg,'boleto_seg.status'),['f','w']) && in_array(data_get($reg,'boleto_quiver.status'),['f','w'])){
                        return $status_color['f']['text'];
                    }
                },
                'attr'=>function($reg){
                    return 'data-boleto_seg-process_id="'. ($reg->boleto_seg->process_id??'') .'" data-boleto_quiver-process_id="'. ($reg->boleto_quiver->process_id??'') .'"';
                }
            ],
            'metabox'=>true,
            'toolbar_buttons'=>[
                ['title'=>false,'alt'=>'Status','icon'=>'fa-pencil','attr'=>'onclick="updateStatusList(this);"','class'=>(_GET('is_trash')=='s'?'hidden':'j-show-on-select') ],
            ]
    ];

    if($prefix=='super-admin'){
        $params['columns']['parent_reg_ctrl']=['Reg. Controle','alt'=>'Registro de Controle Seg. Corretora','value'=>function($v,$reg) use($prefix){
            $b_id = $reg->boleto_seg->process_id??null;
            $q_id = $reg->boleto_quiver->process_id??null;
            $url =  route($prefix.'.app.get',['process_seguradora_data','show']);
            $r='';
            if($b_id)$r.='<a href="'. $url .'?process_prod=boleto_seg&id='. $b_id .'&filter_rel_id='. $reg->id .'" target="_blank" class="strong margin-r-10 text-nowrap" title="Registro de ação na Seguradora">Ação Seg</a>';
            if($q_id)$r.='<a href="'. $url .'?process_prod=boleto_quiver&id='. $q_id .'&filter_rel_id='. $reg->id .'" target="_blank" class="strong text-nowrap" title="Registro de ação no Quiver">Ação Quiver</a>';
            return $r?$r:'-';
        }];


        $configProcessNames = \App\ProcessRobot\VarsProcessRobot::$configProcessNames;
        $terms = $configProcessNames['cad_apolice']['terms']??null;
        $termsList = \App\Models\Term::whereIn('id',$terms)->get();
        //colunas das taxonomias
        if($termsList){
            $r=[];
            foreach($termsList as $term){
                //adiciona a coluna da taxonomia
                $params['columns']['term_'.$term->id.'tx']=[$term->term_title];

                //adiciona os botões de taxonomias na lista
                $r[ $term->id ]=[
                    'show_list'=>'term_'.$term->id.'tx',
                    'button'=>['icon'=>'fa-tags','color'=>'default','title'=>false,'alt'=>$term->term_title],
                    'area_name'=>'cad_apolice',
                    'tax_form_type'=>'set',
                    'term_model'=>$term
                ];
            }
            if($r)$params['taxs']=$r;
        }
    }

    echo view('templates.ui.auto_list',$params);



    $status_list_change = $status_pr;
    unset($status_list_change['a'],$status_list_change['o']);//tira os status que são alterados dinamicamente pelo sistema

@endphp


<style>
.select2-container{width:100% !important;}
.select2-dropdown{min-width:200px;}
#form-filter-bar .form-group{width:120px;float:left;padding-right:10px;}
#form-filter-bar .form-group-account_id{width:65px;}
#form-filter-bar .form-group-id{width:80px;}
#form-filter-bar .j-btn-submit{float:left;}
#form-filter-bar .form-group-dts input{width:110px;}

.col-reg_st_f{color:#008d4c;}
.col-reg_st_e{color:#dd4b39;}
.col-reg_st_a{color:initial;}

.col-boleto_seg,.col-boleto_quiver{text-align:center;white-space:nowrap;}
</style>
<script>
var oForm=$('#form-filter-bar');

//*** barra de filtros ***
(function(){
    /*oForm.append('<input type="hidden" name="code" value="{{ $filter['code'] }}">');//seta um campo vazio para correg

    //ajuste no campo select process_prod
    var oProd = oForm.find('[name=process_prod]').on('change',function(){ f(this.value); });
    var oCodeSeg = oForm.find('[name=code_seg]').on('change',function(){ oForm[0].code.value=this.value; }).closest('.form-group');
    var oCodeQuiver = oForm.find('[name=code_quiver]').on('change',function(){ oForm[0].code.value=this.value; }).closest('.form-group');

    var f=function(v){//v - boleto_seg, boleto_quiver
        oCodeSeg.hide();oCodeQuiver.hide();
        if(v=='boleto_seg'){
            oCodeSeg.show();
        }else{//boleto_quiver
            oCodeQuiver.show();
        };
    };
    f(oProd.val());

    //lógica: antes de aplicar a função awFilterBar, aplica a função que remove os campos code_seg e code_quiver no DOM
    oForm.on('submit',function(e){
        oCodeSeg.remove();
        oCodeQuiver.remove();
    });
    */
    //aplica a função da barra de filtos
    awFilterBar(oForm);
}());



//ao clicar no botão de atualizar
function updateStatusList(bt){
    var ids=$('#pr_seguradora_data_list').triggerHandler('get_select','obj');
    if(ids.length==0)return;

    var process_prod=null;

    var _fUpdate=function(){
        process_prod=oModal.find('[name=process_prod]:checked').val();//atualiza a var com o valor escolhido

        var newdata = {};
        ids.each(function(){
            var o=$(this);
            newdata[ o.attr('data-id') ] = o.attr('data-'+process_prod+'-process_id');
        });
        oModal.find('[name=ids]').val( JSON.stringify(newdata) );
    };

    var oModal = awModal({
        title:'Alteração de Status',
        form:'method="POST" action="{{route($prefix.".app.post",["process_seguradora_data","prChangeAllStatus","id"=>"field-status"])}}"',
        html:function(oHtml){
            var r=''+
                '<input type="hidden" name="ids" value="">'+
                '<p>'+ ids.length +' registro(s) selecionado(s)</p> '+
                '<div class="form-group">'+
                    '<div class="control-div">'+
                        '{!! Form::select("status",[""=>""]+$status_list_change,"",["class"=>"form-control","id"=>"field-status"]) !!}'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'+
                '<div class="form-group">'+
                    'Local da ação '+
                    '<div class="control-div">'+
                        '<label class="nostrong margin-r-10"><input type="radio" name="process_prod" '+ (process_prod=='boleto_seg'?'checked':'') +' value="boleto_seg"><span class="checkmark"></span> Seguradora</label>'+
                        '<label class="nostrong"><input type="radio" name="process_prod" value="boleto_quiver" '+ (process_prod=='boleto_quiver'?'checked':'') +'><span class="checkmark"></span> Quiver</label>'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'+
                @if(in_array($user_logged_level,['dev','superadmin']))
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
            dataFields:{'process_id':'auto'},
            onBefore:function(){
                if(!process_prod){
                    $('[name=process_prod]:eq(0)').trigger('msg','Selecione uma opção');
                    return false;
                }
            },
            onSuccess:function(opt){
               window.location.reload();
            },
        }
    });

    oModal.find('[name=status]').on('change',_fUpdate);
    oModal.find('[name=process_prod]').on('click',_fUpdate);
};
</script>

@endsection

