@extends('templates.admin.index')
@php
use App\Services\PrSegService;
use App\Utilities\FormatUtility;

@endphp



@section('title')
    Revisão dos dados da apólice pela Seguradora <small>#{{$model->id}}</small>
@endsection


@section('content-view')
@php
/*  Parâmetros esperados:
        $id
        $pr_process
        $prCadApoliceService
        $model
        $modelPr
*/



function equalDataPdfApoliceCheck($pdf,$apoliceCheck,$prod_name){
    $r=[];
    $vs=[];
    if($pdf && $apoliceCheck){
        if($apoliceCheck['dados']??false){
            foreach($apoliceCheck['dados'] as $f=>$v){
                if($pdf[$f]!=$v){
                    $r[]=$f;
                    $vs[$f]=$v;
                }
            }
        }
        foreach(['parcelas',$prod_name] as $tb){
            if($apoliceCheck[$tb]??false){
                foreach($apoliceCheck[$tb] as $i=>$arr){
                    foreach($arr as $f=>$v){
                        if($pdf[$f.'_'.$i]!=$v)$r[]=$f.'_'.$i;
                        $vs[$f.'_'.$i]=$v;
                    }
                }
            }
        }
    }
    return $r?[$r,$vs]:true;
}



    $PrSegService = new PrSegService;
    $prefix = Config::adminPrefix();
    
    //captura os dados do registro associado responsável pela verificação automática (do process pr_seguradora_data.process_prod='apolice_check')
    $segdata_apolice_check  = \App\Models\PrSeguradoraData::where(['process_rel_id'=>$model->id, 'process_prod'=>'apolice_check','status'=>'w'])->first();//w - finalizado com alterações
    //dd($segdata_apolice_check,$modelPr);
    if(!$segdata_apolice_check){
        exit('erro ao localizar registro');
    }
    
    
    //captura os dados retornados da verificação de apólices
    $labels = $PrSegService->getSegClass('dados')->fields_labels();
    $dataPdf = $PrSegService->getDataPdf($model,'view','view',false);
    $dataApoliceCheck = $model->getText('apolice_check');//obs: aqui os dados já estão formatados
    
    
    //link do pdf
    $link_pdf = route('super-admin.app.get',['process_cad_apolice','file_load',$model->id]);
    
    

    echo '<div style="position:absolute;top:69px;left:620px;">'.
            '<a class="btn btn-xs btn-primary" href="'. route($prefix.'.app.show',['process_cad_apolice',$model->id]) .'" target="_blank">Log da Baixa</a> '.
            '<a class="btn btn-xs btn-primary" style="margin-left:20px;" href="'.$link_pdf.'" target="_blank">Abrir apólice em nova janela</a>'.
        '</div>';
    echo '<table class="table-sty1" id="table-sty1">
            <td class="td1" valign="top">
                <div class="box" style="position:relative;top:-1px;min-height:800px;"><div class="box-body">
                <h4>Dados divergentes na verificação automática</h4>
                
                ID do Processo de Controle: '. $segdata_apolice_check->process_id .'<br>
                Verificação - Início '. $segdata_apolice_check->created_at .' - Fim '. $segdata_apolice_check->finished_at .'<br><br>';
                
                $fields_err=[];
                $fields=equalDataPdfApoliceCheck($dataPdf, $dataApoliceCheck, $model->process_prod);
                if($fields===true){
                    echo '<p class="strong">Nenhum campo divergente</p>';
                }else{
                    echo '<table class="table table-condensed table-bordered table-hover">
                            <tr><th>Campo</th><th>No PDF</th><th>Na Seguradora</th></tr>';
                    foreach($fields[0] as $f){
                        $v0 = ($dataPdf[$f]??'-');
                        $v1= ($fields[1][$f]??'-');
                        
                        if($v0!=$v1){
                            $fields_err[$f] = ['pdf'=>$v0,'seguradora'=>$v1];
                            echo '<tr>
                                    <td>'. $f .'</td>
                                    <td>'. $v0 .'</td>
                                    <td>'. $v1 .'</td>
                                </tr>';
                        }
                                
                    }
                    echo '</table>';
 
                }
                
                
                //echo '<br><button class="btn btn-success"><span class="fa fa-check"></span> Marcar como concluído</button>';
                echo '<br>';
                echo view('templates.ui.auto_fields',[
                    'form'=>[
                        'id'=>'form1',
                        'url_action'=> route('super-admin.app.post',['process_cad_apolice_pr','apolice_check_revisao_manual2',$model->id]),
                        'data_opt'=>[
                            'focus'=>true,
                            'fields_log'=>false,
                            'onBefore'=>'@function(){ return confirm("Confirmar ação?"); }',
                            'onSuccess'=>'@function(opt){ setTimeout(function(){ if(opt.next){window.location.href=opt.next;}else{window.location.reload();} },500); }',
                        ],
                        'bt_save'=>'Marcar como concluído',
                    ],
                    'autocolumns'=>[
                        'fields_err'=>['type'=>'hidden','name'=>'fields_err','value'=> ($fields_err?json_encode($fields_err):'')],
                        'obs'=>['type'=>'textarea','name'=>'obs','label'=>'Adicionar observação ao log','rows'=>2],
                    ],
                ]);
                
                echo '</div></div>';
                
                

      echo '</td><td class="td2" valign="top" id="table-sty1-td2">
                <iframe src="'. $link_pdf .'" id="iframe-view" class="iframe-view" style="background:#e2e2e2;"></iframe>
            </td>
            </tr>
    </table>';
    


@endphp


<style>
.table-sty1{width:100%;}
.table-sty1 .td1{width:500px;}
.iframe-view{width:100%;border:0;}
.control-label{margin-bottom:0 !important;}
.form-group{margin-bottom:10px !important;}
</style>
<script>
$('#header-push-menu').click();
$('#iframe-view').height( $('#table-sty1-td2').height()-20 );

var oForm=$('#form1');
function frmOnBefore(r){
    //console.log(action);
    //return false;
}
function frmOnSuccess(r){
    //console.log('ok',r);
    setTimeout(function(){
        if(r.next){window.location.href=r.next;}else{window.location.reload();}
    },1500);
}
function frmOnError(r){
    if(r.msg=='diff' || r.msg=='diff_manual'){
        awModal({
            title: r.msg=='diff' ? 'Revise os dados e confime o envio' : 'Dados divergentes - Reportar ao desenvolvedor',
            btSave:'Confirmar',
            msg_type: r.msg=='diff' ? 'success' : 'danger',
            form:'method="POST" action="{{route($prefix.".app.post",["process_cad_apolice_pr","apolice_check_revisao_manual_err",$model->id])}}"',
            html:function(obj){
                var n='';
                if(r.msg=='diff'){
                    n+='<table>';
                    oForm.find('.form-control').each(function(){
                        var o=$(this);
                        var v=$.trim(o.val());if(v=='')v='-';
                        n+='<tr><td>'+o.attr('data-label')+'</td><td style="padding-left:15px;">'+ v +'</td></tr>';
                    });
                    n+='</table>'+
                       '<p><a href="{{$link_pdf}}" target="_blank">Ver apólice</a></p>';
                    n+='<p>Observação (opcional)</p>';
                }else{
                    n+='<p>Observação para o programador</p>';
                };
                n+= '<div class="form-group">'+
                        '<div class="control-div">'+
                            '<textarea class="form-control first-field-focus" rows="6" name="obs"></textarea>'+
                        '</div>'+
                    '</div>';
                obj.html(n);
            },
            form_opt:{
                dataFields:{fields_err:r.fields_err,fields_ok:r.fields_ok,type:r.msg},
                onSuccess:function(opt){
                    setTimeout(function(){
                        if(opt.next){window.location.href=opt.next;}else{window.location.reload();}
                    },1500);
                },
            }
        })
        .on('shown.bs.modal',function(){ $(this).find('textarea').focus(); });
    };
    console.log('err',r);
}


$('#bt-set-error').on('click',function(){
    frmOnError({msg:'diff_manual'});
});
</script>

@endsection
