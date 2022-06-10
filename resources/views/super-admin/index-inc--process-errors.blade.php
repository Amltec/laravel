@php

/******** Carrega as pendências de configuração *************
    Obs:Incluído nos arquivos index.blade de cada dashboard (admin e super-admin)
    Parâmetros:
        account_id - (int) id da conta (opcional)
        regs       - (int) registros por página. Default null. Se > 0, exibe link de 'mais registros'.
        is_title   - (bool) exibe o título conteúdo dentro do metabox. Default false.
***/

$prefix = Config::adminPrefix();
$regs = $regs??0;
$is_title = $is_title??false;

if($prefix=='super-admin'){
    $account_id = $account_id ?? null;
}else{
    $account_id = Config::accountID();
}

$ProcessErrorsCtrl = new \App\Http\Controllers\Process\ProcessErrorsController;
$list = $ProcessErrorsCtrl->get_data(['regs'=>$regs,'account_id'=>$account_id]);
if(!$list)return;

//echo '<h4>Pendências de Configuração do Operador</h4>';
if($list){
    $total = $list['total'];
    $list = new \App\Utilities\CollectionUtility($list['data']);
    
    
    $params=[
        'list_id'=>'list_process_errors',
        'data'=>$list,
        'columns'=>[
            'title'=>['Título','value'=>function($v,$reg) use($prefix){
                    return
                        ($prefix=='super-admin'?'<strong class="margin-r-10 text-blue">'.$reg->account_name.'</strong>':'').
                        ($reg->process_name=='cad_apolice' ? '' : '<span class="margin-r-10"> <span title="Corretora: #'. $reg->broker_id .' '. $reg->broker.' ">'. $reg->broker.'</span> &nbsp; / &nbsp; '.$reg->insurer .'</span> ' ).
                            '<span class="small" style="color:#999;">'. $reg->process_label .'</span>'.
                        '<br><strong class="text-red" data-status_code="'. $reg->status_code .'" title="Código: '. strtoupper($reg->status_code) .'">'. $reg->error .'</strong>'.
                        //'<br><span class="small" style="color:#999;"> <span title="Corretora: #'. $reg->broker_id .' '. $reg->broker.' ">'. $reg->broker.'</span> &nbsp; / &nbsp; '.$reg->insurer .'</span>'. 
                        '';
            }],
            'actions'=>['Ações','value'=>function($v,$reg) use($prefix){            
                $list_link='';
                if($reg->process_name=='cad_apolice' && substr($reg->status_code,0,4)=='quil'){//erro de login
                    if($prefix=='super-admin'){
                        $list_link = route($prefix.'.app.edit',['accounts',$reg->account_id]).'?pag=pass';
                    }else{
                        $list_link = route($prefix.'.app.index','account_pass').'?pag=pass';
                    }
                    
                }elseif($reg->process_name=='seguradora_data'){
                    $list_link = route($prefix.'.app.get',['process_seguradora_data','boletos_list']).'?broker_id='. $reg->broker_id .'&insurer_id='. $reg->insurer_id .'&code='.$reg->status_code.'&status='. ($reg->process_prod=='boleto_seg'?'b':'q') .'_s';
                }
                //dd($reg);
            
                $r= ($list_link ? '<button class="btn btn-link btn-sm margin-r-10 " onclick="goToUrl(\''. $list_link .'\')">Acessar</button>' : '').
                    '<button class="btn btn-success btn-sm margin-r-10 j-confirm"><i class="fa fa-check" title="Marcar como concluído"></i></button>'.
                    ($prefix=='super-admin' ? '<button class="btn btn-link btn-sm margin-r-10 j-remove" title="Fechar"><i class="text-danger fa fa-close"></i></button>' : '').
                    '';
                return $r;
            }],
        ],
        'options'=>['header'=>false,'footer'=>false],
        'metabox'=>[
            'is_padding'=>false,
            'is_border'=>false,
            'title'=>$is_title?'<span class="strong">Pendências de Configuração do Operador</span>':null,
            'footer'=>function() use($prefix){
                echo '<a href="'. route($prefix.'.app.get',['process_errors','list']) .'" class=""><i class="fa fa-caret-right margin-r-5"></i> Visualizar todos os registros</a>';
            }
        ],
        'html_not'=>'Nenhuma pendência',
    ];
    if($total<=$regs || !$regs)unset($params['metabox']['footer']);
    
    echo view('templates.ui.auto_list',$params);
    
}else{
    echo '<p>Nenhum registro</p>';
}


@endphp

<style>
#list_process_errors .col-actions{vertical-align:middle !important;text-align:right;}
#list_process_errors .tr-finish td *{color:#666 !important;}
</style>
<script>
(function(){
    $('#list_process_errors')
    .on('click','.j-confirm',function(){ f(this,'confirm'); })
    .on('click','.j-remove',function(){ f(this,'remove'); });
    
    var f=function(bt,ac){
        if(!confirm(ac=='confirm'?'Confirmar reprocessamento?':'Deseja remover?'))return false;
        bt=$(bt);
        var tr=bt.closest('tr');
        
        if(ac=='confirm')bt.find('i').attr('class','fa fa-circle-o-notch fa-spin');
        
        awAjax({
            url: ac=='confirm' ? '{{route($prefix.".app.post",["process_errors","finish"])}}' : '{{route($prefix.".app.post",["process_errors","remove"])}}',
            data: {id:tr.attr('data-id')},
            processData:true,
            success: function(j){
                //console.log('ok2',j);
                if(!j.success)return;
                if(ac=='confirm'){
                    setTimeout(function(){
                        //bt.find('i').attr('class','fa fa-check');
                        tr.addClass('tr-finish');
                        tr.find('.btn').remove();
                        tr.find('.col-actions').html('<i class="fa fa-check" style="margin-right:20px"></i>');
                    },400);
                }else{
                    tr.fadeOut('slow');
                }
            }
        });
    }
}());
</script>