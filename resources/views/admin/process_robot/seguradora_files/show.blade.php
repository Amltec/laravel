@extends('templates.admin.index')


@section('title')
{{$configProcessNames[$model->process_name]['title']}} <small class="strong">#{{$model->id}}</small>
{!! $model->process_test?'<span class="label bg-orange" style="margin-left:10px;font-size:10px;">Teste</span>':'' !!}
@endsection


@section('content-view')
@php
/*  Parâmetros esperados:
        $model
        $execsModel
        $modelData
        $configProcessNames
        $robotModel
        $status_list
        $user_logged_level
        $filesProcess
        $filesProcess_countClone
        $ProcessCadApolice
        $prsegfiles_status
        $f_status
        $thisClass
*/
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;


Form::loadScript('forms');
Form::loadScript('inputmask');

$prefix = Config::adminPrefix();
$robot_data = $model->data_array;
$dts_search = ($robot_data['dts_search']??false) ? explode('|',$robot_data['dts_search']) : false;
$path = $model->getPaths();
$is_change_status = in_array($user_logged_level,['dev','superadmin']);
$this_auto_id=uniqid();


//conta os totais
$count_all=0;
$count_clone=$filesProcess_countClone;
foreach($prsegfiles_status as $st_v => $st_opt){
    $count_all+=$st_opt['count'];
}

$data = [
    'account'=>['title'=>'Conta','value'=>'<u>'.$model->account->account_name.' #'.$model->account_id.'</u>','class_row'=>'strong'],
    ['title'=>'Processo','value'=> $configProcessNames[$model->process_name]['title']  .' - '.   $configProcessNames[$model->process_name]['products'][$model->process_prod]['title'],'alt'=>'Processo: '.$model->process_name.'.'.$model->process_prod ],
    ['title'=>'Cadastro no sistema','value'=>(string)$model->created_at,'type'=>'datetime'],
    ['title'=>'Data do processamento','value'=>(string)$model->updated_at,'type'=>'datetime'],

    'process_next_at'=>['title'=>'Agendado para','value'=>
            FormatUtility::dateFormat($model->process_next_at).
            ($is_change_status?'<a href="#" onclick="nextAtClear();return false;" style="margin-left:10px;" class="text-teal" title="Remover agendamento"><span class="fa fa-close"></span></a>':'').
            '',
        'class_row'=>'text-teal'],

    ['title'=>'Período de busca','value'=>function() use($dts_search,$model){
        if($model->process_ctrl_id=='manual'){
            return '<strong>Manual</strong>';
        }else{
            return $dts_search ? (FormatUtility::dateFormat($dts_search[0],'date') .' - '. FormatUtility::dateFormat($dts_search[1],'date')) : '-';
        }
    }],
    ['title'=>'Status','value'=>
        '<span style="font-size:0.9em;" class="strong label '. ($model->status_color['bg']??'') .'" title="'. $model->status_long_label .'">'.
            $model->status_label .
            (in_array($model->process_status,['e','c','1']) ? ' - '.$thisClass::getStatusCode($robot_data['error_msg']??'',false) : '').
        '</span>'.
        ($is_change_status?'<a href="#" onclick="editOpen();return false;" class="btn btn-link" title="Alterar"><span class="fa fa-pencil"></span></a>':'').
        (in_array($user_logged_level,['dev','superadmin']) && $robotModel ? '<a href="'. route($prefix.'.app.edit',['robots',$robotModel->id]) .'" class="text-light-blue" style="margin-left:10px;font-size:small;">'. $robotModel->robot_name .'</a>' : '')
    ],
    ['title'=>'Concluído no Quiver','value'=>
        '<span style="font-size:0.9em;" class="strong label bg-gray" title="Marcado como concluído no Quiver">'. $model->status_mark_done .'</span>'
    ],
];

//if(in_array($model->process_status,['p','a','f']) && .....?'<br><span class="label label-info">Marcar como concluído</span>':'')

//se a data agenda da for menor que a data do último processamento, não precisa exibir
if(ValidateUtility::ifDate($model->process_next_at,'<=',date("Y-m-d H:i:s")))unset($data['process_next_at']);

if($prefix=='admin')unset($data['account']);


/*
//**** campos de tempo de execução ******
$p_start = $robot_data['process_start_1']??null;
$p_end = $robot_data['process_end_1']??null;
if($p_start){
    $data[]=['title'=>'Início da execução','value'=>$p_start,'type'=>'datetime'];
    if($p_end??false){
        $data[]=['title'=>'Término (horas)','alt'=>$p_end  ,'value'=>FormatUtility::dateDiffFull($p_start,$p_end)];
    }else{
    //    $data[]=['title'=>'Término','value'=>'-'];
    }
}
*/


//include de padrões de retorno
$data[]=['class_value'=>'no-padding','value'=>function() use($robot_data,$model,$user_logged_level,$execsModel,$thisClass){
    echo '<a href="#" style="margin-left:15px;" onclick=\'$("#div-view-all-returns-robot").fadeToggle();return false;\'>Visualizar todos os retornos do robô</a>';
    echo '<div id="div-view-all-returns-robot" class="hiddenx" style="padding-left:15px;"><br>';
        if($execsModel->count()==0){
            echo '<div>Nenhum retorno registrado</div><hr>';
        }else{
            echo '<table class="table no-margin table-bordered table-condensed">
                <tr>
                    <th colspan="2">Processamentos do Robô</th>
                    <th>Tempo</th>
                    <th>Retorno</th>
                </tr>';
            foreach($execsModel as $reg){
                if($reg->process_end){
                    $n = FormatUtility::dateDiffFull($reg->process_start,$reg->process_end);
                }else{
                    $n = '<span class="fa fa-circle-o-notch fa-spin margin-r-5"></span> Aguardando retorno do robô';
                }
                $s = $reg->status_code;
                echo '<tr class="tr-status-'. ($s=='ok' ? 'ok': 'err' ) .'" data-status-code="'.$s.'">
                        <td>'. $reg->id .'</td>
                        <td>'. FormatUtility::dateFormat($reg->process_start) .'</td>
                        <td>'. $n .'</td>
                        <td><strong class="margin-r-10">'. $thisClass::getStatusCode($s,false) .'</strong> '. print_r($reg->getText($model),true) .'</td>
                    </tr>';
            }
            echo '</table>';
        }
    echo '</div>';
}];




//verifica quais registros de processamento estão na lista
if($filesProcess->count()>0){
    $num_exec=null;
    $r='';
    $count=0;
    foreach($filesProcess as $reg){
        $cls='';
        $s=$prsegfiles_status[$reg->status];
        if(is_null($reg->process_count))$reg->process_count=0;

        if($reg->status=='0'){//ag. indexação
            $cls='text-muted';
        }elseif($reg->status=='1'){//Nenhuma ação
            $cls='text-muted';
        }elseif(in_array($reg->status,['a','b'])){//aguardando
            $cls='text-aqua';
        }elseif($reg->status=='f'){//finalizado
            $cls='text-green';
        }elseif(in_array($reg->status,['e','i','x'])){//erro
            $cls='text-red';
        }

        if($num_exec!==$reg->process_count){
                $r=str_replace('{n}', $count.' arquivo'.($count>1?'s':'') ,$r);

                $num_exec=$reg->process_count;
                if($count>0)$r.='<tr><td colspan="8">&nbsp;</td></tr>';
                $r.='<tr><td colspan="8">'.
                        ($model->process_ctrl_id ?
                            '<strong>Execução manual</strong>'
                        :
                            '<strong>Execução '. ($num_exec+1) .' - '. FormatUtility::dateFormat($reg->created_at,'d/m/Y H:i') .'</strong>'.
                            '<span class="fa fa-info" style="margin-left:5px;" title="Data e hora da inserção dos registros abaixo no banco"></span>'.
                            '<span style="margin-left:5px;"> - {n}</span>'
                        ).
                    '</td></tr>
                <tr>
                    <td>Produto</td>
                    <td>ID</td>
                    <td>Status do Processo</td>
                    <td>Cadastro</td>
                    <td>Nº da Apólice</td>
                    <td>Status no Quiver</td>
                    <td>Nº do Quiver</td>
                    <td title="ID do registro de controle da Área de Seguradoras que contém o id do processo duplicado">Duplicado Area<span class="fa fa-info" style="font-size:0.8em;margin-left:5px;"></span></td>'.
                    ($user_logged_level=='dev' ? '<td></td>' : '').
                '</tr>';

                $count=0;
        }
        $apolice_num = array_get($reg->getText('data'),'apolice_num_quiver');

        $r.='<tr class="tr-item-mark '. $cls .'" data-st="'.$reg->status.'">
                <td>'. array_get($configProcessNames,'cad_apolice.products.'. $reg->process_prod .'.title') .'</td>
                <td><a target="_blank" href="'. route($prefix.'.app.show',['process_cad_apolice',$reg->id]) .'">'.$reg->id.'</a></td>
                <td>'. ($reg->deleted_at ? 'Removido' : $ProcessCadApolice::$status[$reg->process_status]) .'</td>
                <td>'. FormatUtility::dateFormat($reg->created_at,'date') .'</td>
                <td>'. ($apolice_num ? $apolice_num : '-') .'</td>
                <td title="Code='. strtoupper($reg->status) .' - '. $s['info'] .'">'. $s['text'] .'</td>
                <td>'. ($reg->quiver_id ? $reg->quiver_id : '-') .'</td>
                <td>'. ($reg->process_clone_id ? '<a href="'. route($prefix.'.app.show',['process_seguradora_files',$reg->process_clone_id]) .'?rel_id='.$reg->id .'" target="_blank">'.$reg->process_clone_id.'</a>' : '-') .'</td>'.
                ($user_logged_level=='dev' ? '<td><a href="#" title="Alterar status" class="fa fa-edit" onclick="fPrSegFilesChangeSt(\''.$reg->id.'\');return false;"></a></td>' : '').
            '</tr>';
        $count++;
    }
    if($r){
        $r=str_replace('{n}', $count.' arquivo'.($count>1?'s':'') ,$r);
        $r='<table class="table table-condensed table-bordered" style="max-width:800px;" id="list-mark-'.$this_auto_id.'">'.$r.'</table>';
        if(method_exists($filesProcess,'appends')){
            $r.='<div style="margin:-20px 0;">'. $filesProcess->appends(request()->except('page')) .'</div>'; //paginação
        }

        //filter status mark
                $n='<hr><strong>Inseridos '. ($model->process_ctrl_id=='manual'?'manualmente':'automaticamente') .'</strong>'.
                    '<div class="pull-right">
                            <small style="margin-right:30px;">Filtros querystring: quiver_id, st, rel_id, rel_st, regs</small>
                            <a href="#" class="fa fa-edit margin-r-10" onclick="fPrSegFilesChangeStAll();return false;" title="Alterar Status de todos os registros"></a>
                            Filtro: <select style="margin-left:10px;" onchange="fPrSegFiles_filterList(this.value);">'.
                                '<option value="">Todos</option>';
                            foreach($prsegfiles_status as $st_v => $st_opt){
                                $n.='<option value="'. $st_v .'" '. ((string)$f_status===(string)$st_v?'selected':'') .'>('. $st_opt['count'] .') '. $st_opt['text'] .'</option>';
                            }
                            $n.='</select>
                    </div>';

        $data['line1']=$n;
        $data['files_extract']=[
            'title'=> $configProcessNames['cad_apolice']['title'].
                        ' <br><span class="text-muted">'. ($count_all-$count_clone) .' arquivo'. ($count_all-$count_clone>1?'s':'') .' extraído'. ($count_all-$count_clone>1?'s':'') .'</span>'.
                        ' <br><a href="'. route($prefix.'.app.get',['process-cad-apolice','list']).'?seguradora_files-down_apo-id='. $model->id .'&status=allx&seguradora_files-down_apo-clone=s" target="_blank" class="text-muted">'. ($count_clone) .' duplicado'. ($count_clone>1?'s':'') .' de outros processo'. ($count_clone>1?'s':'') .'</a>'.
                        ' <br><a href="'. route($prefix.'.app.get',['process-cad-apolice','list']).'?seguradora_files-down_apo-id='. $model->id .'&status=allx" target="_blank">(listar todos)</a>'.
                        '',
            'value'=>$r
        ];
    }else{
        $data['files_extract']=['title'=>'Extração','value'=>'Tabela pr_process_files vazia'];
    }
}else{

    //filter status mark
    $n='<hr><strong>Inseridos '. ($model->process_ctrl_id=='manual'?'manualmente':'automaticamente') .'</strong>'.
        '<div class="pull-right">
                Filtro: <select style="margin-left:10px;" onchange="fPrSegFiles_filterList(this.value);">'.
                    '<option value="">Todos</option>';
                foreach($prsegfiles_status as $st_v => $st_opt){
                    $n.='<option value="'. $st_v .'" '. ((string)$f_status===(string)$st_v?'selected':'') .'>('. $st_opt['count'] .') '. $st_opt['text'] .'</option>';
                }
                $n.='</select>
        </div>';
    $data['line1']=$n;
    $data['files_extract']=[
            'title'=> $configProcessNames['cad_apolice']['title'].
                        ' <br><span class="text-muted">'. $count_all .' arquivo'. ($count_all>1?'s':'') .' extraído'. ($count_all>1?'s':'') .'</span>'.
                        ' <a href="'. route($prefix.'.app.get',['process-cad-apolice','list']).'?seguradora_files-down_apo-id='. $model->id .'&status=all" target="_blank">(listar)</a>'.
                        '',
            'value'=>'Nenhum registro'
        ];
}


if($robot_data['files_error']??false){
    $t=false;
    foreach($robot_data['files_error'] as $prod => $reg){
        if($reg)$t=true;
    }
    if($t)$data['files_error']=['title'=>'Erros na Extração','value'=>$robot_data['files_error'],'type'=>'dump'];
}





if(in_array($user_logged_level,['dev','superadmin'])){
    if($model->process_status=='w'){//espera upload de arquivo manual
        $data['upload_manual']=['title'=>'Upload manual','value'=>function() use($model,$robot_data){
                echo view('templates.components.uploadbox',[
                    'controller'=>'files',
                    'name'=>'upload-manual',
                    'title'=>'Selecionar arquivo',
                    'upload_db'=>false, //para não registrar na tabela 'files'
                    'upload'=>[
                        'private'=>true,
                        'filename'=>($robot_data['filename_tmp']??null),
                        'folder'=>'seguradora_files/down_apo/'.$model->id,
                        'account_off'=>false,
                        'thumbnails'=>false,
                        'accept'=>'application/zip',
                        'account_id'=>$model->account_id,
                    ],
                    'upload_form'=>[
                        'onSuccess'=>'@uplSuccess',
                    ],
                    'upload_view'=>false,
                ]);
                echo '<div style="position:absolute;margin-top:-30px;font-size:small;color:#999;">Arquivo temporário "'. ($robot_data['filename_tmp']??'-').'"</div>';
        }];
    }

    if($user_logged_level=='dev'){
        $data['dev_info']=['title'=>'Para desenvolvedor','value'=>function() use($path,$robot_data){
            echo '<a href="#" onclick="$(\'#div_dev_paths\').fadeToggle();return false;">Visualizar caminhos</a>';
            echo '<div id="div_dev_paths" class="hiddenx">'; dump($path); echo '</div>';
            echo '<br>';

            echo '<a href="#" onclick="$(\'#div_dev_datas\').fadeToggle();return false;">Visualizar Dados</a>';
            echo '<div id="div_dev_datas" class="hiddenx">'; dump($robot_data); echo '</div>';
        }];
    }
}


//***** botões finais *****

$r='';
if($model->process_ctrl_id!='manual' && (in_array($user_logged_level,['dev','superadmin']) || ($model->process_status!='f'))){
    if($user_logged_level!='user'){//usuário não pode excluir
        $r.=view('templates.components.button',['title'=>false,'icon'=>'fa-trash','alt'=>'Remover processo','color'=>'danger','class'=>'margin-r-5',
            'post'=>[
                'url'=>route($prefix.'.app.remove','process_'.$model->process_name),
                'data'=>['id'=>$model->id,'_method'=>'DELETE','action'=>'trash'],
                'confirm'=>'Confirmar exclusão deste processo?',
                'cb'=>'@function(r){ if(r.success){alert("Processo excluído com sucesso");window.location="'. route($prefix.'.app.index','process_'.$model->process_name) .'";}else{alert("Erro ao excluir: "+ r.msg)} }',
            ]
        ]);
    }

    if($model->process_ctrl_id!='manual'){
        if(($robot_data['new_process']??'')!='down_apo' || in_array($model->process_status,['f','e','c','1']))$r.=view('templates.components.button',['title'=>'Buscar Apólices','icon'=>'fa-refresh','color'=>'info','class'=>'margin-r-5','onclick'=>'fNewProcess()']);
    }
}

if(file_exists($path['file'])){
    $r.=view('templates.components.button',['title'=>'Download','icon'=>'fa-files-o','color'=>'primary','alt'=>'Download de todos os arquivos baixados da Área de Seguradoras','class'=>'margin-r-5','onclick'=>'alert("Atenção: se este arquivo está disponível, é porque ocorreu algum erro no processo de extração e remoção deste arquivo!");','href'=>$path['url']]);
    //$r.='<span class="btn text-danger disabled">!Erro ao excluir arquivo ZIP</span>';
}


$step = isset($robot_data['extraction_step']) ? (int)$robot_data['extraction_step'] : false;
if($model->process_status=='0' || $step!==false){//em indexação     //$step!==false -quer dizer que o processo de extração falhou em algum ponto e por isto exibir um botão para processar novamente o método doExtracted()
    $r.=view('templates.components.button',['title'=>false,'icon'=>'fa-refresh','alt'=>'Processar extração de arquivos','color'=>'info','class'=>'margin-r-5',
        'post'=>[
            'url'=>route($prefix.".app.post",["process_seguradora_files","doExtracted"]),
            'data'=>['id'=>$model->id],
            'cb'=>'@function(r){ if(r.success){alert("Dados reprocessados com sucesso");}else{alert("Erro ao processar: "+ r.msg);}; if(!r.data)window.location.reload(); }',
        ]
    ]);
}

$footer_html=$r;


if($user_logged_level=='dev'){
    echo '<a href="'. URL::to('/') .'/super-admin/logs?area_name=process_robot&area_id='. $model->id .'" class="btn btn-default" target="_blank" style="position:absolute;right:110px;top:65px;">Logs</a>';
}

echo view('templates.components.metabox',[
    'content'=>function() use ($data){
        echo view('templates.ui.view',[
            'data'=>$data
        ]);
    },
    'footer'=>$footer_html
]);




$status_list_change = $status_list;
unset($status_list_change['a']);//tira a opção 'em andamento'

$status_markdone_list_change=[];
foreach($prsegfiles_status as $k=>$v){
    $status_markdone_list_change[$k]=$v['text'];
}
//dd($status_markdone_list_change);
@endphp



@if($user_logged_level=='dev' || $user_logged_level=='superadmin')
<script>
function uplSuccess(opt){//espera apenas um json {success:true}
    if(!opt.success)return false;
    //atualiza a url da imagem retornada
    awAjax({
        url: "{{route($prefix.'.app.post',['process_seguradora_files','upload_manual_confirm'])}}",
        data: {id:'{{$model->id}}'},
        processData: true,
        success:function(r){
            if(r.success){//oculta a janela de progresso do upload*/
                setTimeout('window.location.reload();',500);
            }else{
                alert(r.msg);
            }
        },
        error:function(xhr){
            awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger','btSave':false});
        }
    });
};



function editOpen(){
    var oModal = awModal({
        title:'Alterar Status',
        form:'method="POST" action="{{route($prefix.".app.post",["process_seguradora_files","changeAllStatus"])}}"',
        html:function(oHtml){
            oHtml.html(
                '<div class="form-group">'+
                    '<div class="control-div">'+
                        '{!! Form::select("status",[""=>""]+$status_list_change,"",["class"=>"form-control","id"=>"field-status"]) !!}'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'+
                '<div class="form-group hiddenx" id="div-field-next-at">'+
                    '<label class="control-label" title="Para limpar o campo, digite: 00/00/0000 00:00">Agendar processo <span class="fa fa-info" style="margin-left:5px;"></label>'+
                    '<div class="control-div">'+
                        '{!! Form::text("next_at","",["placeholder"=>"dd/mm/aaaa hh:mm","class"=>"form-control","data-mask"=>"99/99/9999 99:99"]) !!}'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'
            );
            oHtml.find('#field-status').on('change',function(){
                var o=oHtml.find('#div-field-next-at').hide();
                var v=this.value;
                if(v=='0' || v=='p')o.show();//0 indexação, p pronto robo
            });
        },
        btSave:'Alterar',
        form_opt:{
            dataFields:{'ids[]':[{{$model->id}}]},
            onSuccess:function(opt){
               window.location.reload();
            }
        }
    });
}


//Limpa o campo de agendamento
function nextAtClear(){
    if(confirm('Remover o agendamento deste processo?'))
    awAjax({
        url: '{{route($prefix.".app.post",["process_seguradora_files","clear_next_at"])}}',data:{id:'{{$model->id}}'},processData:true,
        success: function(){window.location.reload();}
    });
};

//Registra para procurar novos arquivos na área de seguradoras
function fNewProcess(){
    if(confirm('Confirmar ação de nova busca de apólices?'))
    awAjax({
        type:'GET',url: '{{route($prefix.".app.get",["process_seguradora_files","add_process_auto"])}}',data:{id:'{{$model->id}}'},processData:true,
        success: function(){window.scrollTo(0,0);window.location.reload();}
    });
};

//Filtra a lista de registros de controle que são marcador como concluído/não concluído
function fPrSegFiles_filterList(st){
    var url = addQS(admin_vars.url_current+'?'+admin_vars.querystring,'st='+st,'string');
    window.location=url;
};

@if($user_logged_level=='dev')
//Altera o status do registro de arquivos marcados na área de seguradoras
function fPrSegFilesChangeSt(rel_id){
    var s=prompt('Digite o novo status (valores: 0,1,a,b,f,e,i,x):');
    if($.trim(s)=='')return;
    awAjax({
        url: '{{route($prefix.".app.get",["process_seguradora_files","set_seg_file_new_status"])}}',data:{id:'{{$model->id}}',rel_id:rel_id,st:s},processData:true,
        //success: function(r){if(r.success)window.location.reload();}
    });
};

//Altera o status de todos os registros por grupo de status
function fPrSegFilesChangeStAll(){
    var oModal = awModal({
        title:'Alterar Status para todos os registros',
        form:'method="POST" action="{{route($prefix.".app.post",["process_seguradora_files","set_seg_file_new_status"])}}"',
        html:function(oHtml){
            oHtml.html(
                'Alterar de: '+
                '<div class="form-group">'+
                    '<div class="control-div">'+
                        '{!! Form::select("st_from",[""=>""]+$status_markdone_list_change,"",["class"=>"form-control","id"=>"field-status"]) !!}'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'+
                'Para o status: '+
                '<div class="form-group">'+
                    '<div class="control-div">'+
                        '{!! Form::select("st",[""=>""]+$status_markdone_list_change,"",["class"=>"form-control","id"=>"field-status"]) !!}'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'+
                ''
            );
        },
        btSave:'Alterar',
        form_opt:{
            dataFields:{id:'{{$model->id}}',rel_id:'all'},
            onSuccess:function(opt){
               window.location.reload();
            }
        }
    });
}
@endif
</script>
@endif



@endsection
