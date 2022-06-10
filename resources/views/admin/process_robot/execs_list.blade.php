@extends('templates.admin.index')


@section('title')
{{ array_get($configProcessNames,'products.'.$process_prod.'.title') .' - '. array_get($configProcessNames,'title') }}
@endsection


@section('content-view')
@php
/***
Arquivo de visualização padrão dos registros da tabela process_robot_execs

Parâmetros esperados:
    $process_name
    $process_prod
    $model
    $execsModel
    $thisClass
***/



$prefix = Config::adminPrefix();


//******* lista de registros *********

    echo '<div class="box box-primary box-widget">
        <div class="box-body">';
        
    echo '<table class="table no-margin">
        <thead>
            <tr>
                <th colspan="2"><span class="margin-r-5">Processamentos do Robô</span></th>
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
                    <td width="*">'. ($m ? '<span class="fa fa-'. ($s=='ok' || $s=='ok2'?'check':'close') .' margin-r-5"></span> '.$m : '<span class="fa fa-circle-o-notch fa-spin margin-r-5"></span> Aguardando retorno do robô') .'</td>
                    <td width="50">'. ($s ? '<a href="'. route($prefix.".app.get",["process_seguradora_data","execs_file_view"]) .'?process_id='.$model->id.'&exec_id='.$reg->id.'" target="_blank" class="fa fa-file-text-o" title="Arquivo de retorno"></a>' : '-')  .'</td>
                </tr>';
        }
        
    echo '</tbody></table>';
    
    
    //paginação
        if($execsModel->total()>$execsModel->count()){
            $info = 'Exibindo '. (($execsModel->currentpage()-1)*$execsModel->perpage()+1).' - '. ((($execsModel->currentpage()-1)*$execsModel->perpage())+$execsModel->count()). ' de '. $execsModel->total() .' registros';
        }else if($execsModel && method_exists($execsModel,'total') && $execsModel->count()>0){
            $info = 'Exibindo '.$execsModel->total().' registro'.($execsModel->total()>1?'s':'');
        }else{
            $info = '';
        }
        echo'<div class="pull-left padding" style="padding-top:10px;">'.$info . '</div>'.
            '<div class="pull-right" style="margin:-20px 0;">'. $execsModel->appends(request()->except('page')) .'</div>';
    
    
    echo '</div></div>';
    
@endphp

<style>
.hidden-tablerows{display:none;}
.tr-status-ok{color:#008d4c;}
.tr-status-err{color:#dd4b39;}
</style>


@endsection