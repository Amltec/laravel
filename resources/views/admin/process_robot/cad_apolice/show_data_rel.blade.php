@extends('templates.admin.index')

@php
/*
Parâmetros esperados:
    $model
    $userLogged
    $thisClass
*/
use App\Models\User;
use App\Models\ProcessRobotExecs;
use App\Models\PrSeguradoraData;
use App\Services\PrCadApoliceService;
use App\ProcessRobot\cad_apolice\Classes\Vars\QuiverVar;
use App\Models\UserLog;
use App\Services\LogsService;


    $prefix = Config::adminPrefix();
    $model_data = $model->data_array;
    $userModel = new User();
    $servicePrCadApolice = $thisClass->servicePrCadApolice();
    
    
    //Se != '', quer dizer que usuário alterou manualmente o status do processo para finalizada.
    $st_change_user = $model_data['st_change_user']??null;
    if($st_change_user){
        $tmp = explode('|',$st_change_user); //Sintaxe: status|user_id|datetime
        $st_change_user = [
            'status'=>$tmp[0],
            'user'=>$userModel->find($tmp[1]),
            'date'=>$tmp[2],
        ];
    }
    
    
    $execsModel = (new ProcessRobotExecs)->where('process_id',$model->id)->orderBy('id','asc')->get();
    
    $pr_status = $servicePrCadApolice::$status;
    
    
    $pgto_code = $model->getSegData()['fpgto_tipo_code']??'';
    $pgto_label = QuiverVar::$pgto_all_codes[ $pgto_code ]  ?? $pgto_code;
    $is_boleto = in_array($pgto_code,['10','2','9','62884']);   //10 boleto, 2 carne, 9|62884 primeira parcela no boleto apenas
    
    $timeline=[];
    $timeline[(string)$model->created_at .'.'. rand(10,99) ] = 'Enviado '. ($model->process_auto?'pela Área de Seguradoras':'Manualmente');
    
@endphp


@section('title')
@php
    echo 'Log da Baixa - '. $configProcessProd['title_long'] .' - <span style="font-size:0.8em;">Id</span> <large class="strong">'. $model->id . ($model->process_auto?'*':'') .'</large> ';
    echo $model->process_test?'<span class="label bg-orange" style="margin-left:10px;font-size:10px;">Teste</span>':'';
        
    if(Config::adminPrefix()=='super-admin'){
        $account_data = $model->account->getData();
        echo '<div style="display:inline-block;margin-left:70px;position:relative;top:-2px;">';
            if($account_data['logo_icon']){
                echo '<span style="top:9px;" class="account-logo-icon"><img src="'. $account_data['logo_icon'].'?'. $account_data['updated_at'] . '" /></span>';
            }
            echo '<span  class="label bg-navy" style="font-size:12px;position:relative;margin-left:5px;">'.$model->account->account_name.' #'. $model->account_id .'</span>';
        echo '</div>';
    }
@endphp
@endsection



@section('content-view')
<h4>Emissão no Quiver</h4>
<a target="_blank" href="{{route($prefix.'.app.show',['process_cad_apolice',$model->id])}}" style="position:absolute;margin:-30px 0 0 200px" class="btn btn-xs btn-primary">Acessar Log da Baixa</a>
<div class="box box-primary box-widget width1"><div class="box-body no-padding">
    <table class="table">
        <tr><td>Seg / Cor</td><td>{{ $model->insurer->insurer_alias .' / '. $model->broker->broker_name }}</td></tr>
        <tr><td>Ramo</td><td>{{$configProcessProd['title']}}</td></tr>
        <tr><td>Cadastro</td><td>{{$model->created_at}}</td></tr>
        <tr><td>Status</td><td>{{ $model->status_label }}</td></tr>
        <tr><td>Forma de Pgto</td><td>{{ $pgto_label .' ('. $pgto_code .')' }}</td></tr>
        @if(in_array($model->process_status,['f','w']))
            <tr><td>Emissão {{$st_change_user ? 'Manual' : 'Automática' }}</td><td>{{$st_change_user ? $st_change_user['user']->user_name : 'Robô' }}</td></tr>
            <tr><td>Data da Emissão</td><td>{{$st_change_user ? $st_change_user['date'] : $model->updated_at}}</td></tr>
        @endif
    </table>
</div></div>


<h4>Execuções da Emissão</h4>
<div class="box box-primary box-widget width2"><div class="box-body no-padding">
@php
    if($execsModel->count()>0){
        echo '<table class="table">
                <tr><th>&nbsp;</th><th>Início</th><th>Fim</th><th>Tempo</th><th>Status</th><th>Log</th>';
        foreach($execsModel as $reg){
            $timeline[(string)$reg->process_start .'.'. rand(10,99) ] = 'Início da emissão no Quiver #'.$reg->id;
            if($reg->process_end)$timeline[(string)$reg->process_end.'.'. rand(10,99) ] = 'Retorno do robô para emissão no Quiver #'.$reg->id;
            
            $n = $reg->process_end ? FormatUtility::dateDiffFull($reg->process_start,$reg->process_end) : '-';
            $s = $reg->status_code;
            $m = $s .' - '. $thisClass::getStatusCode($s,false);
            echo '<tr class="tr-status-'. ($s=='ok' || $s=='ok2' ? 'ok': ($s?'err':'none') ) .'" data-status-code="'.$s.'">
                    <td width="50">'. $reg->id .'</td>
                    <td width="200">'. $reg->process_start .'</td>
                    <td width="200">'. $reg->process_end .'</td>
                    <td width="150">'. $n .'</td>
                    <td width="*">'. ($m ? $m :    (!in_array($model->process_status,['p','a'])? 'Não iniciado' : '<span class="fa fa-circle-o-notch fa-spin margin-r-5"></span> Aguardando retorno do robô')     ) .'</td>
                    <td width="50">'. ($s ? '<a href="'. route($prefix.".app.get",["process_cad_apolice_pr","file_data_view"]) .'?process_id='.$model->id.'&type=exec&id='.$reg->id.'" target="_blank" class="fa fa-file-text-o" title="Arquivo de retorno"></a>' : '-')  .'</td>
                </tr>';
        }
        echo '</table>';
    }else{
        echo '<p class="margin">Nenhum registro encontrado</p>';
    }
@endphp
</div></div>


@if($is_boleto)
<h4>Revisão no Quiver</h4>
<div class="box box-primary box-widget width2"><div class="box-body no-padding">
@php
    $list = $servicePrCadApolice->get($model->id,'review');
    if($list->count()>0){
        echo '<table class="table">
                <tr><th>&nbsp;</th><th>Status</th><th>Responsável</th><th>Cadastro</th><th>Término</th><th>Log</th>';
        foreach($list as $reg){
            $user_label = $reg->user_id ? $reg->user->user_name : 'Automático';
            $timeline[(string)$reg->created_at .'.'. rand(10,99) ] = 'Cadastrado para Revisão #'.$reg->num;
            if($reg->finished_at)$timeline[(string)$reg->created_at .'.'. rand(10,99) ] = 'Retorno do robô da revisão #'.$reg->num;
            
            echo '<tr class="tr-status-'. $reg->status .'" data-status-code="'. $reg->status .'">
                    <td width="50">'. $reg->num .'</td>
                    <td width="200">'. ($pr_status[$reg->status]??$reg->status) .'</td>
                    <td width="200" title="Usuário ID'. $user_label .'">'. $user_label .'</td>
                    <td width="200">'. $reg->created_at .'</td>
                    <td width="200">'. $reg->finished_at .'</td>
                    <td width="50">'. 'XXX' .'</td>
                </tr>';
        }
        echo '</table>';
    }else{
        echo '<p class="margin">Nenhum registro encontrado</p>';
    }
@endphp
</div></div>
@endif




<h4>Verificação da Apólice</h4>
<div class="box box-primary box-widget width2"><div class="box-body no-padding">
@php
    $list = $servicePrCadApolice->get($model->id,'apolice_check');
    if($list->count()>0){
        echo '<table class="table" id="table-apolice_check">
                <tr><th>&nbsp;</th><th>Status</th><th>Responsável</th><th>Cadastro</th><th>Término</th><th>Concluído</th><th title="Ação do Desenvolvedor">Dev.</th><th>Dados</th><th>Log</th>';
        $list=$list->reverse();//inverte, pois no padrão, será exibido em ordem crescente
        foreach($list as $reg){
            $timeline[(string)$reg->created_at .'.'. rand(10,99) ] = 'Cadastrado para verificação de apólice #'.$reg->num;
            if($reg->finished_at)$timeline[(string)$reg->created_at .'.'. rand(10,99) ] = 'Retorno do robô para a verificação de apólice #'.$reg->num;
            
            $is_auto = $reg->user_id ? false : true;
            $user_label = $reg->user_id ? $reg->user->user_name : 'Automático';
            
            $s=$reg->status;
            $cls='bg-gray';
            if($s=='m' || $s=='n')$cls='bg-primary';
            if($s=='c')$cls='bg-navy';
            if($s=='e')$cls='bg-red';
            $st='<span class="btn-clr1 btn btn-xs '.$cls.' no-events">'. ($pr_status[$reg->status]??$reg->status) .'</span>';
            
            $bt_action='';
            if($s=='m'){//precisa de revisão manual 1
                $bt_action = '<a href="'. route($prefix.'.app.get',['process_cad_apolice_pr','apolice_check_revisao_manual','?id='.$reg->process_id ]) .'" class="btn btn-primary btn-xs strong" target="_blank">Editar</a>';
            }elseif($s=='n'){//precisa de revisão manual 2 
                if($reg->is_done){
                    $bt_action = '<a href="#" class="j-status-change btn btn-success btn-xs strong" data-id="'. $reg->process_id .'" data-num="'. $reg->num .'" data-is_done="'. ($reg->is_done?'s':'n') .'">Feito</a>';
                }else{
                    $bt_action = '<a href="'. route($prefix.'.app.get',['process_cad_apolice_pr','apolice_check_revisao_manual2','?id='.$reg->process_id ]) .'" class="btn btn-primary btn-xs strong" target="_blank">Editar</a>';
                }
                
            }elseif($s=='c'){//precisa de correção manual (admin dev)
                $bt_action = '<a href="#" class="j-status-change btn btn-'. ($reg->is_done?'success':'warning') .' btn-xs strong" data-id="'. $reg->process_id .'" data-num="'. $reg->num .'" data-is_done="'. ($reg->is_done?'s':'n') .'">'. ($reg->is_done?'Feito':'Marcar OK') .'</a>';
            }
            
            $data_apolice_check_m = $reg->getData($model,'apolice_check_m');
            
            echo '<tr class="tr-status-'. $reg->status .' tr-is_done-'. ($reg->is_done?'s':'n') .'" data-status-code="'. $reg->status .'">
                    <td width="30">'. $reg->num .'</td>
                    <td>'. $st .'</td>
                    <td title="Usuário ID'. $user_label .'">'. $user_label .'</td>
                    <td>'. $reg->created_at .'</td>
                    <td>'. $reg->finished_at .'</td>
                    <td>'. ($reg->is_done?'Sim':'Não') .'</td>
                    <td>'. $bt_action .'</td>
                    <td>'. ($data_apolice_check_m ? '<a href="#" class="fa fa-plus" onclick="$(this).closest(\'tr\').next().fadeToggle(\'fast\');return false;"></a>' : '-') .'</td>
                    <td><a href="'. route('super-admin.app.index','logs') .'?area_name=cad_apolice.apolice_check'.  ($is_auto?'':'.'.$reg->num)  .'&area_id='.$model->id.'" class="fa fa-file-text-o" target="_blank"></a></td>
                </tr>';
                if($data_apolice_check_m){
                    echo '<tr style="display:none;">
                            <td colspan="1" style="border-top:1px solid #fff">&nbsp;</td>
                            <td colspan="8" style="border-top:1px solid #fff">';
                                dump($data_apolice_check_m);
                        echo'</td>
                        </tr>';
                }
        }
        echo '</table>';
    }else{
        echo '<p class="margin">Nenhum registro encontrado</p>';
    }
@endphp
</div></div>




@if(true)
<h4>Boletos no Site da Seguradora</h4>
<div class="box box-primary box-widget width2"><div class="box-body">
@php
    $reg_seg = PrSeguradoraData::where(['process_rel_id'=>$model->id,'process_prod'=>'boleto_seg'])->first();
    $reg_quiver = PrSeguradoraData::where(['process_rel_id'=>$model->id,'process_prod'=>'boleto_quiver'])->first();
    
    if($reg_seg){
        $timeline[(string)$reg_seg->created_at .'.'. rand(10,99) ] = 'Cadastrado para busca de boleto na Seguradora';
        if($reg_seg->finished_at)$timeline[(string)$reg_seg->finished_at .'.'. rand(10,99) ] = 'Retorno do robô para a busca de boleto na Seguradora';
    }
    if($reg_quiver){
        $timeline[(string)$reg_quiver->created_at .'.'. rand(10,99) ] = 'Cadastrado para upload do boleto no Quiver';
        if($reg_quiver->finished_at)$timeline[(string)$reg_quiver->finished_at .'.'. rand(10,99) ] = 'Retorno do robô para o upload do boleto no Quiver';
    }
    
    
    
    echo '
    <div class="row">
        <div class="col-sm-6">
            <strong>Ação na Seguradora</strong>';
            if($reg_seg){
                $boleto_seg = $model->getBoletoSeg();
                echo '<table class="table table-condensed no-margin">
                        <tr><td>Status</td><td>'. (PrCadApoliceService::$status[$reg_seg->status]??$reg_seg->status) .'</td></tr>
                        <tr><td>Processo Início</td><td>'. $reg_seg->created_at .'</td></tr>
                        <tr><td>Processo Término</td><td>'. $reg_seg->finished_at .'</td></tr>
                        <tr><td>Boletos Capturados</td><td>'. ($boleto_seg ? count($boleto_seg) : '-') .'</td></tr>
                    </table>';
            }else{
                echo '<p>Sem registro</p>';
            }
    echo'</div>
        <div class="col-sm-6">
            <strong>Ação no Quiver</strong>';
            if($reg_quiver){
                echo '<table class="table table-condensed no-margin">
                        <tr><td>Status</td><td>'. (PrCadApoliceService::$status[$reg_quiver->status]??$reg_quiver->status) .'</td></tr>
                        <tr><td>Processo Início</td><td>'. $reg_quiver->created_at .'</td></tr>
                        <tr><td>Processo Término</td><td>'. $reg_quiver->finished_at .'</td></tr>
                    </table>';
            }else{
                echo '<p>Sem registro</p>';
            }
    echo'</div>
    </div>';
    
    
@endphp
</div></div>
@endif


<h4>Histórico Resumido</h4>
<div class="box box-primary box-widget width2"><div class="box-body no-padding">
    <table class="table">
        <tr><th>&nbsp;</th><th>Data</th><th>Ação</th></tr>
    @php
        $i=1;
        foreach($timeline as $dt => $label){
            echo '<tr>
                    <td width="50">'. $i .'</td>
                    <td width="180">'. $dt .'</td>
                    <td width="*">'. $label .'</td>
                </tr>';
            $i++;
        }
    @endphp
    </table>
</div></div>



<h4>Logs</h4>
<a href="{{ URL::to('/') .'/super-admin/logs?area_name=process_robot&area_id='. $model->id }}" target="_blank" style="position:absolute;margin:-30px 0 0 75px" class="btn btn-xs btn-primary">Acessar</a>
<div class="box box-primary box-widget width2"><div class="box-body no-padding">
@php
    $list = UserLog::selectRaw('user_logs.*,(select users.user_name from users where users.id=user_logs.user_id) as user_name',[])
                ->where(function($q){
                    $q->where('area_name','like','%cad_apolice%')->orWhere('area_name','process_robot')->orWhere('area_name','like','%boleto_seg%')->orWhere('area_name','like','%boleto_quiver%');
                })
                ->where('area_id',$model->id)
                ->orderBy('id','desc')
                ->get();
    if($list->count()>0){
        echo '<table class="table table">
                <tr><th>ID</th><th>Usuário</th><th>Área</th><th>Log</th><th>Data</th><th>&nbsp;</th>';
        foreach($list as $reg){
            $n = strpos($reg->area_name,'cad_apolice')!==false ? substr($reg->area_name,strlen('cad_apolice.')) : $reg->area_name;
            echo '<tr class="tr-status-'. $reg->status .'" data-status-code="'. $reg->status .'">
                    <td width="50">'. $reg->id .'</td>
                    <td width="200" title="'. $reg->user_name .'">'. ($reg->user ? str_limit($reg->user_name,20).' <small class="text-muted">'.$reg->user_level.'</small>' : '-' ) .'</td>
                    <td width="">'. ($n ? strtoupper($n): '-') .'</td>
                    <td width="">'. LogsService::getResumeData($reg) .'</td>
                    <td width="">'. $reg->created_at .'</td>
                    <td width=""><a href="#" class="fa fa-plus" onclick="$(this).closest(\'tr\').next().fadeToggle(\'fast\');return false;"></a></td>
                </tr>
                <tr style="display:none;">
                    <td colspan="1" style="border-top:1px solid #fff">&nbsp;</td>
                    <td colspan="4" style="border-top:1px solid #fff" class="no-padding">';
                        //dump(LogsService::formatLogData($reg->log_data));
                        echo view('templates.ui.view',[
                            'data'=>[
                                ['title'=>false,'value'=>LogsService::formatLogData($reg->log_data)],
                            ],
                            'class'=>'vxiew-bordered view-condensed',
                        ]);
                echo'</td>
                </tr>';
        }
        echo '</table>';
    }else{
        echo '<p class="margin">Nenhum registro encontrado</p>';
    }
@endphp
</div></div>





<style>
.width1{max-width:500px;}
.width2{max-width:1100px;}

.tr-status-f,.tr-status-w{color:#008d4c;}
.tr-status-e,.tr-status-y,.tr-status-x{color:#dd4b39;}
.tr-is_done-s{color:#008d4c;}
.tr-is_done-s .btn-clr1{background:#008d4c !important;color:#fff !important;}
</style>
<script>
$('#table-apolice_check .j-status-change').on('click',function(e){
    e.preventDefault();
    var a=$(this);
    awModal({
        title:'Verificação de Apólice - Ação do Desenvolvedor',
        html:function(oHtml){
            oHtml.html(
                '<div class="form-group">'+
                    '<div class="control-div">'+
                        '<label><input type="checkbox"  name="is_done" value="s" data-label="" '+ (a.attr('data-is_done')=='s'?'checked':'') +'><span class="checkmark "></span> Marcar como concluído</label>'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'+
                ''
            );
            setTimeout(function(){ oHtml.find('textarea').focus(); },500);
        },
        btClose:false,
        btSave:'Salvar',
        form:'method="POST" action="{{route($prefix.'.app.post',['process_cad_apolice_pr','change_fields'])}}" accept-charset="UTF-8" ',
        form_opt:{
            dataFields:{process:'apolice_check',id:a.attr('data-id'),num:a.attr('data-num'),action:'is_done'},
            onSuccess:function(r){
                if(r.success)window.location.reload();
            },
            fields_log:false
        }
    });
    return false;
});
    
</script>
@endsection



