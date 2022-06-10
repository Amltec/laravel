@extends('templates.admin.index')
@php
use App\Services\PrSegService;
use App\Utilities\FormatUtility;

@endphp



@section('title')
    Revisão manual dos dados da apólice <small>#{{$model->id}}</small>
@endsection


@section('content-view')
@php
/*  Parâmetros esperados:
        $id
        $pr_process
        $prCadApoliceService
        $model
        
*/

    $PrSegService = new PrSegService;
    $prefix = Config::adminPrefix();
    
   
    $params = [
            'info1'=>['type'=>'info','text'=>'Dados do Seguro'],
            'proposta_num'=>['label'=>'Número da Proposta','maxlength'=>100,'require'=>true],
            'apolice_num'=>['label'=>'Número da Apólice','maxlength'=>100,'require'=>true],
            'data_emissao'=>['label'=>'Data da Emissão','type'=>'date','require'=>true],
            'inicio_vigencia'=>['label'=>'Início da Vigência','type'=>'date','require'=>true],
            'termino_vigencia'=>['label'=>'Término da Vigência','type'=>'date','require'=>true],
            'fpgto_premio_liquido'=>['label'=>'Prêmio Líquido','type'=>'currency','require'=>true],
            'fpgto_premio_total'=>['label'=>'Prêmio Total','type'=>'currency','require'=>true],
            'fpgto_n_prestacoes'=>['label'=>'Prestações','type'=>'number','require'=>true,'attr'=>'min="1" max="12"'],
            'info2'=>['type'=>'info','text'=>'Dados do Ramo / Produto'],
    ];

    //captura os campos do form do produto
    $prodClass = $PrSegService->getSegClass($model->process_prod);

    //captura os campos do formulário do produto
    $prodForm = $prodClass::fields_html();
    $prodForm = array_intersect_key($prodForm,array_flip($prodClass::fields_review_manual()));
    
    //*** !IMPORTANTE: este código não está totalmente adaptado para mais um produto (em desenvolvimento) ***
        $prodForm = FormatUtility::addPrefixArray($prodForm,'_1',false,true);//add sufix key _{N}
        $params['_prod_count']=['type'=>'hidden','value'=>'1'];
        $params = $params + $prodForm;
    
    

    $link_pdf = route('super-admin.app.get',['process_cad_apolice','file_load',$model->id]);

    echo '<a style="position:absolute;top:69px;left:520px;" href="'.$link_pdf.'" target="_blank">Abrir apólice em nova janela</a>';
    echo '<table class="table-sty1" id="table-sty1">
            <td class="td1" valign="top">';

                echo view('templates.ui.auto_fields',[
                    'form'=>[
                        'id'=>'form1',
                        'url_action'=> route('super-admin.app.post',['process_cad_apolice_pr','apolice_check_revisao_manual',$model->id]),
                        'data_opt'=>[
                            'focus'=>true,
                            'fields_log'=>false,
                            'onBefore'=>'@frmOnBefore',
                            'onSuccess'=>'@frmOnSuccess',
                            'onError'=>'@frmOnError',
                        ],
                        'bt_save'=>'Confirmar',
                    ],
                    'autocolumns'=>$params,
                    'metabox'=>[
                        'footer'=>'<a class="btn btn-danger pull-right last-focus" id="bt-set-error">Reportar Erro</a>'
                    ],
                ]);


      echo '</td><td class="td2" valign="top" id="table-sty1-td2">
                <iframe src="'. $link_pdf .'" id="iframe-view" class="iframe-view" style="background:#e2e2e2;"></iframe>
            </td>
            </tr>
    </table>';
    


@endphp


<style>
.table-sty1{width:100%;}
.table-sty1 .td1{width:350px;}
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
