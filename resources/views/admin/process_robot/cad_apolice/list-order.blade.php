@extends('templates.admin.index')

@section('title')
@php
    echo 'Prioridades no Processamento <small class="strong">Cadastro de Apólices</small>';
@endphp
@endsection


@section('content-view')
@php
/*  Parâmetros esperados:
        $model
        $configProcessNames
        $types_apolices_list

    Parâmetros personalizados para os includes (arquivos blade) da pasta pr_process
        $title2 - título complementar de @section(title)
*/


//carrega o componente de ordenação de registros
Form::loadScript('html5sortable');


$prefix = Config::adminPrefix();
//$count_total = $model->total();
$n_page = (int)Request::input('page'); if(!$n_page)$n_page=1;
$n_count = 0;

$params = [
        'list_id'=>'process_robot_list',
        'data'=>$model,
        'columns'=>[
            //'date_group'=>['Data Grupo','value'=>function($v,$reg){ return FormatUtility::dateFormat($reg->created_at,'d/m/Y'); }],
            'order'=>['','value'=>function($v,$reg) use(&$n_count,$n_page){
                $n_count++;
                return '<label class="label label-default nostrong j-order-num">'. ($n_page==1 ? $n_count : (($n_count*($n_page-1))+$n_page) ) .'</small>';
            }],
            'id'=>['ID','value'=>function($v,$reg){ return $v . ($reg->process_auto?'*':''); }],
            'account'=>['Conta','value'=>function($v,$reg){
                $n=$reg->account->account_name;
                $account_cancel = $reg->account->account_status!='a';
                return '<span title="'. ($account_cancel?'Cancelado - ':'') .'#'.$reg->account_id.' - '.$n.'" style="'. ($account_cancel?'text-decoration:line-through;':'') .'">'.str_limit($n,20) .'</span>';
            }],
            'process_prod'=>['Ramo','value'=>function($v,$reg) use($configProcessNames){return array_get($configProcessNames,$reg->process_name.'.products.'.$reg->process_prod.'.title'); }],
            'data_type'=>['Tipo','value'=>function($v,$reg) use($types_apolices_list){ return $types_apolices_list[array_get($reg->seg_data,'data_type','-')]??'-'; }],
            'broker'=>['Corretora','value'=>function($v){ return $v->broker_alias ?? '-'; }],
            'insurer'=>['Seguradora','value'=>function($v){ return $v->insurer_alias ?? '-'; }],
            'nome'=>['Nome','value'=>function($v,$reg){ $n=array_get($reg->seg_data,'segurado_nome','-'); return '<span title="'.$n.'">'. str_limit($n,25) .'</span>'; }],

            //'created_at'=>['Cadastro','value'=>function($v){ return FormatUtility::dateFormat($v,'d/m/Y H:i');  }],
            'status_msg'=>['Status','value'=>function($v,$reg){return $reg->status_label;}],
            'action'=>['','value'=>function($v){return '<div class="btn btn-primary btn-xs" style="width:60px;">Abrir</div>';}],
        ],
        'options'=>[
            'checkbox'=>true,
            'select_type'=>2,
            'pagin'=>true,
            'confirm_remove'=>true,
            'toolbar'=>true,
            //'regs'=>false,
            'remove'=>false,
            'search'=>false,
        ],
        'routes'=>[
            'click'=>function($reg) use($prefix){return route($prefix.'.app.show',['process_cad_apolice',$reg->id,'rd='. urlencode(Request::fullUrl()) ]);},
        ],
        'field_click'=>'action',
        'row_opt'=>[
            'actions'=>function($reg){
                $reg->seg_data = $reg->getSegData(false,true);
            },
            'class'=>function($reg){
                return $reg->status_color['text'];
            },
        ],
        'metabox'=>true,
        'toolbar_buttons'=>[
            ['title'=>false,'alt'=>'Retirar da lista de prioridade','icon'=>'fa-eraser','class'=>'j-show-on-select','attr'=>'onclick="setClearOrder();"'],
            ['title'=>'Reordenar','attr'=>'onclick="setReOrder(this);"' ],
            ['title'=>'Salvar','attr'=>'onclick="setSaveOrder(this);"','color'=>'info', 'id'=>'j-btn-order-save', 'class'=>'hiddenx'],
        ],
    ];


    if($prefix=='admin'){
        unset($params['columns']['account']);
    }


echo view('templates.ui.auto_list',$params);




@endphp


<script>
var list = $('#process_robot_list');
var oBtSave = $('#j-btn-order-save:eq(0)');

//detecta que ocorreram alterações na ordem das linhas
list.find('tbody')[0].addEventListener('sortupdate',function(e){
    order_change=true;
    oBtSave.show();
    //$('.j-order-num').each(function(i){ $(this).text(i+1); });//atualiza o campo de número da ordem //desnecessário por enquanto
});

//ativa a reordenação dos registros
var order_active=false;
var oBtReorder=null;
var order_change=false;
function setReOrder(bt){
    if(!oBtReorder)oBtReorder=$(bt);
    if(order_active){
        list.removeClass('list-reorder');
        oBtReorder.removeClass('btn-primary');
        oBtSave.hide();
        order_active=false;
        sortable('#process_robot_list tbody', 'destroy');

    }else{
        order_active=true;
        list.addClass('list-reorder');
        oBtReorder.addClass('btn-primary');
        if(order_change)oBtSave.show();
        sortable('#process_robot_list tbody',{
            placeholder: '<tr><td colspan="10">&nbsp;</td></tr>'
        });
    }
}

//limpa a ordem dos registros
function setClearOrder(){
    var ids=list.triggerHandler('get_select');
    if(ids.length==0)return;
    awAjax({
        url: '{{route($prefix.".app.post",["process_cad_apolice","set_order"])}}',data:{'ids[]':ids,action:'clear'},processData:true,
        success: function(){
            for(var i in ids){
                list.trigger('remove',{id:ids[i],confirm:false});
            }
        }
    });
}

//salva a ordem dos registros
function setSaveOrder(){
    var ids=list.find('.row-item').map(function(){ return $(this).attr('data-id'); }).get();
    awAjax({
        url: '{{route($prefix.".app.post",["process_cad_apolice","set_order"])}}',data:{'ids[]':ids},processData:true,
        success: function(r){
            if(r.success)window.location.reload();
        }
    });
}


</script>
<style>
#process_robot_list.list-reorder .col-check .checkmark{pointer-events:none;visibility:hidden;}
#process_robot_list.list-reorder tbody .col-check:after{content:'\f0c9';font:normal normal normal 14px/1 FontAwesome;display:block;position:absolute;margin:-20px 0 0 0;font-size:1.2em;}
#process_robot_list .row-item{user-select:none;}
</style>

@endsection
