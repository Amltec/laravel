@extends('templates.admin.index')

@section('title')
{!! array_get($configProcessNames,'products.'.$process_prod.'.title') .'  <span class="strong" style="font-size:0.7em;margin-left:10px;">'. array_get($configProcessNames,'title').'</span>' !!}  <small class="strong">#{{$model->id}}</small>
{!! $model->process_test?'<span class="label bg-orange" style="margin-left:10px;font-size:10px;">Teste</span>':'' !!}
@endsection




@section('content-view')
@php
/*  Parâmetros esperados:
        $model
        $modelList
        $configProcessNames
        $configPNCadApolice
        $robotModel
        $status_list
        $status_pr
        $statusColor
        $process_prod
        $thisClass
        $filter
        $user_logged
        $execsArr
        $execsModel_total
        $filter_rel_id
*/
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;


$prefix = Config::adminPrefix();
Form::loadScript('inputmask');

$robot_data = $model->data_array;


//****** monta os dados da tabela *********
    $data = [
        ['title'=>'Conta','value'=>'<u>'.$model->account->account_name.' #'.$model->account_id.'</u>','class_row'=>'strong'],
        ['title'=>'Processo','value'=> $configProcessNames['products'][$model->process_prod]['title'],'alt'=>'Processo: '.$model->process_name.'.'.$model->process_prod ],
        //['title'=>'Data Cadastro','value'=>(string)$model->created_at,'type'=>'datetime'],
        //['title'=>'Data processamento','value'=>(string)$model->updated_at,'type'=>'datetime'],
        
        ['title'=>'Seguradora','value'=>str_limit($model->insurer->insurer_name,30),'class_value'=>'strong'],
        ['title'=>'Corretor','value'=>str_limit($model->broker->broker_name,30),'class_value'=>'strong'],

        ['title'=>'Status','value'=>
            '<span style="font-size:0.9em;" class="strong label '. ($model->status_color['bg']??'') .'" title="('. ($robot_data['error_msg']??'') .') '. $model->status_long_label .'">'.
                $model->status_label .
                (in_array($model->process_status,['e','c','1','s']) ? ' - '.$thisClass::getStatusCode($robot_data['error_msg']??'',false) : '').
            '</span>'.
            '<a href="#" onclick="editOpen();return false;" class="btn btn-link" title="Alterar"><span class="fa fa-pencil"></span></a>'.
            ( $robotModel ? '<a href="'. route($prefix.'.app.edit',['robots',$robotModel->id]) .'" class="text-light-blue" style="margin-left:10px;font-size:small;">'. $robotModel->robot_name .'</a>' : '')
        ],
        
        'process_next_at'=>['title'=>'Agendado para','value'=>
                FormatUtility::dateFormat($model->process_next_at).
                '<a href="#" onclick="nextAtClear();return false;" style="margin-left:10px;" class="text-teal" title="Remover agendamento"><span class="fa fa-close"></span></a>'.
                '',
            'class_row'=>'text-teal'],
    ];
    
    //se a data agenda da for menor que a data do último processamento, não precisa exibir
    if(ValidateUtility::ifDate($model->process_next_at,'<=',date("Y-m-d H:i:s")))unset($data['process_next_at']);
    

    
//**** resumo das apólices processadas neste registro ****
    
    /*$data['resume1'] = ['title'=>'Registros na Fila','value'=>function() use($thisClass,$process_prod,$model,$status_pr,$statusColor){
        $list = $thisClass->getDataResume($process_prod,$model->id);
        foreach($list as $st=>$n){
            echo '<span class="margin-r-5 btn btn-xs '. $statusColor[$st]['bg'] .'">'.$n .'</span>'.
                '<span class="margin-r-10 '. $statusColor[$st]['text'] .'">'. $status_pr[$st] .'</span> ';
        }
    }];*/
    


    
//**** exibe a view de visualização dos dados *****
    echo view('templates.components.metabox',[
        'content'=>function() use ($data, $thisClass,$process_prod,$model,$status_pr,$statusColor,$filter){
            echo view('templates.ui.view',[
                'data'=>$data,
                'class'=>'view-col2 view-condensed',
                'arrange'=>'5-7',
            ]);
        },
    ]);



//**** monta a tabela de histórico de execuções ****
if($execsModel->count()>0){
    echo '<div class="box box-primary box-widget row-max1">
        <div class="box-body no-padding">
        <table class="table no-margin">
        <thead>
            <tr>
            <th colspan="2" onclick="shTableRows($(this),$(this).closest(\'table\'),\'auto\');">
                <span class="margin-r-5">Processamentos do Robô</span> <span class="fa icon-collapse fa-angle-down" data-icon="fa-angle-right|fa-angle-down"></span>
            </th>
            <th>Tempo</th>
            <th>Retorno</th>
            <th>Log</th>
            </tr>
        </thead><tbody>
        </tr>';
        foreach($execsModel as $reg){
            $n = $reg->process_end ? FormatUtility::dateDiffFull($reg->process_start,$reg->process_end) : '-';
            $s = $reg->status_code;
            $m = $thisClass->getStatusCode($s,false);
            echo '<tr class="tr-status-'. ($s=='ok' || $s=='ok2' ? 'ok': ($s?'err':'none') ) .'" data-status-code="'.$s.'">
                    <td width="50">'. $reg->id .'</td>
                    <td width="200">'. FormatUtility::dateFormat($reg->process_start) .'</td>
                    <td width="150">'. $n .'</td>
                    <td width="*" title="Code: '. $s .'">'. ($m ? '<span class="fa fa-'. ($s=='ok' || $s=='ok2'?'check':'close') .' margin-r-5"></span> '.$m : '<span class="fa fa-circle-o-notch fa-spin margin-r-5"></span> Aguardando retorno do robô') .'</td>
                    <td width="50">'. ($s ? '<a href="'. route($prefix.".app.get",["process_seguradora_data","execs_file_view"]) .'?process_id='.$model->id.'&exec_id='.$reg->id.'" target="_blank" class="fa fa-file-text-o" title="Arquivo de retorno"></a>' : '-')  .'</td>
                </tr>';
        }
        if($execsModel_total>$execsModel->count()){
            echo '<tr><td colspan="5"><a href="'. route($prefix.".app.get",["process_seguradora_data","execs_list"])  .'?process_id='.$model->id.'" target="_blank">Total de '. $execsModel_total .' execuções - acessar lista completa</a></td></tr>';
        }
    echo '</tbody></table></div></div>';
    
}



//***** lista de registros processados (cadastro de apólices ******    
    echo view('admin.process_robot.seguradora_data._template-show--list',get_defined_vars());
    

    
$status_list_change = $status_list;
unset($status_list_change['a']);//tira a opção: a 'em andamento'
@endphp

<style class="text-muted">
.hidden-tablerows{display:none;}
.tr-status-err{color:#dd4b39;}
.tr-status-ok,
.tr-status-err[data-status-code=segd06]
    {color:#008d4c;}

</style>
<script>
//Limpa o campo de agendamento
function nextAtClear(){
    if(confirm('Remover o agendamento deste processo?'))
    awAjax({
        url: '{{route($prefix.".app.post",["process_seguradora_data","clear_next_at"])}}',data:{id:'{{$model->id}}'},processData:true,
        success: function(){window.location.reload();}
    });
};

function editOpen(){
    var oModal = awModal({
        title:'Alterar Status',
        form:'method="POST" action="{{route($prefix.".app.post",["process_seguradora_data","changeAllStatus"])}}"',
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

//exibe/oculta somente as linhas da tabela deixando o cabeçalho
function shTableRows(oThis,oTable,sh,sel_content){//sh = true,false,auto
    var os=oTable.find(sel_content ? sel_content : 'tbody');
    if(sh=='auto')sh=os.hasClass('hidden-tablerows');
    
    var icon=oThis.find('.icon-collapse');
    var ics=icon.attr('data-icon').split('|');
    icon.removeClass(ics[0]+' '+ics[1]);
    
    if(sh){
        os.removeClass('hidden-tablerows');
        icon.addClass(ics[1]);
    }else{
        os.addClass('hidden-tablerows');
        icon.addClass(ics[0]);
    };
};
</script>

@endsection