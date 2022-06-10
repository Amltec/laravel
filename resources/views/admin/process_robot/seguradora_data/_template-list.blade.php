@extends('templates.admin.index')

@section('title')
{!! array_get($configProcessNames,'products.'.$process_prod.'.title') .' <span style="font-size:0.7em;margin-left:10px;">'. array_get($configProcessNames,'title') .'</span>' !!}
@endsection


@section('content-view')
@php
/*  Parâmetros esperados:
        $model
        $filter
        $configProcessNames
        $status_list
        $ProcessCadApolice
        $user_logged
        $user_logged_level
*/

use App\Utilities\FormatUtility;


$list_deleted = $list_deleted??false;


//captura a lista de seguradoras e corretores
$insurers_list = \App\Models\Insurer::pluck('insurer_alias','id')->toArray();
$brokers_list = \App\Models\Broker::pluck('broker_alias','id')->toArray();



echo view('templates.components.metabox',[
        'content'=>function() use($filter,$status_list,$prefix,$insurers_list,$brokers_list){
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
                    'account_id'=>['label'=>'ID Conta','class_group'=>''],
                    'id'=>['label'=>'ID Grupo','class_group'=>''],
                    'ida'=>['label'=>'ID Apólice','class_group'=>''],
                    'status'=>['label'=>'Status','class_group'=>'','type'=>'select','list'=>[''=>'']+$status_list],
                    'broker_id'=>['label'=>'Corretora','class_group'=>'','type'=>'select2','list'=>[''=>'']+$brokers_list,'attr'=>'data-allow-clear="true"'],
                    'insurer_id'=>['label'=>'Seguradora','class_group'=>'','type'=>'select2','list'=>[''=>'']+$insurers_list,'attr'=>'data-allow-clear="true"'],
                    'dts'=>['label'=>'Dt Process. Inicial','class_group'=>'','type'=>'date'],
                    'dte'=>['label'=>'Dt Process. Final','class_group'=>'','type'=>'date'],
                    'bt'=>['title'=>false,'alt'=>'Filtrar','class_group'=>'','type'=>'submit','color'=>'info','icon'=>'fa-filter','attr'=>'style="margin-top:24px;"','class'=>'j-btn-submit'],
                ]
            ];
            if($prefix=='admin'){
                unset($params['autocolumns']['account_id']);
            }
            echo view('templates.ui.auto_fields',$params);
        }
]);




$params=[
            'list_id'=>'process_robot_list',
            'data'=>$model,
            'columns'=>[
                'id'=>'ID', //'id'=>['ID',function($v,$reg){ return $v . ($reg->process_ctrl_id?'.'.$reg->process_ctrl_id:''); }],
                'account'=>['Conta','value'=>function($v,$reg){
                    $n=$reg->account->account_name; 
                    $account_cancel = $reg->account->account_status!='a';
                    return '<span title="'. ($account_cancel?'Cancelado - ':'') .'#'.$reg->account_id.' - '.$n.'" style="'. ($account_cancel?'text-decoration:line-through;':'') .'">'.str_limit($n,20) .'</span>'; 
                }],
                'broker'=>['Corretora','value'=>function($v){ return $v->broker_alias ?? '-'; }],
                'insurer'=>['Seguradora','value'=>function($v){ return $v->insurer_alias ?? '-'; }],
                'updated_at'=>['Último Process.','value'=>function($v){ return FormatUtility::dateFormat($v,'d/m/Y H:i'); }],
                'status_msg'=>['Status','value'=>function($v,$reg) use($thisClass){
                    $s=($reg->process_test?'<span class="label bg-orange margin-r-5">Teste</span> ':'');
                    if(in_array($reg->process_status,['e','c','1'])){
                        $m = array_get($reg->data_array,'error_msg');
                        $s.= $reg->status_label;
                        if($m){
                            $s.=': '.$thisClass::getStatusCode($m);
                        }
                    }else{
                        $s.= $reg->status_label;
                    }
                    return $s;
                }],
                'total_st_f'=>['<i title="Finalizados" class="fa fa-check text-green"></i>','value'=>function($v,$reg){
                    return explode('|',$reg->total_sts)[0];
                }],
                'total_st_e'=>['<i title="Erros" class="fa fa-close text-red"></i>','value'=>function($v,$reg){
                    return explode('|',$reg->total_sts)[1];
                }],
                'total_st_a'=>['<i title="Em processamento" class="fa fa-circle-o-notch"></i>','value'=>function($v,$reg){
                    return explode('|',$reg->total_sts)[2];
                }],
                'total_st_s'=>['<i title="Parados" class="fa fa-pause text-muted" style="font-size:0.9em;"></i>','value'=>function($v,$reg){
                    return explode('|',$reg->total_sts)[3];
                }],
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
                'click'=>function($reg) use($prefix,$process_prod){return route($prefix.'.app.get',['process_seguradora_data','show','?process_prod='.$process_prod.'&id='.$reg->id.'&rd='. urlencode(Request::fullUrl()) ]);},
                'remove'=>route($prefix.'.app.remove','process_seguradora_data'),
            ],
            //'field_click'=>'id',
            'row_opt'=>[
                'class'=>function($reg){
                    if($reg->account->account_status!='a'){
                        return 'text-red';
                    }else{
                        return $reg->status_color['text']??'';
                    }
                },
                'lock_del'=>function($reg) use($user_logged){
                    if($reg->process_ctrl_id=='manual' && $user_logged->id!==1){
                        return true;
                    }elseif(in_array($user_logged->user_level,['admin','user'])){
                        return $reg->process_status=='f';
                    }else{//dev, superadmin
                        return false;
                    }
                },
                'lock_click'=>'deleted'
            ],
            'metabox'=>true,

    ];
    if($prefix=='admin'){
        unset($params['columns']['account']);
    }
    if($user_logged_level=='user'){//negado user_level='user'
        $params['options']['remove']=false; //desativa a lixeira
        $params['options']['is_trash']=false; //desativa a lixeira
        
    }else if(in_array($user_logged_level,['dev','superadmin'])){
        $params['options']['list_remove']=true; //ativa a opção de exibir da lixeira
    }
    //dump($user_logged_level,$params);
    
    
echo view('templates.ui.auto_list',$params);


Form::loadScript('inputmask');


$count = $model->count()>0;
@endphp

<style>
.select2-container{width:100% !important;}
.select2-dropdown{min-width:200px;}
#form-filter-bar .form-group{width:140px;float:left;padding-right:10px;}
#form-filter-bar .form-group-account_id{width:65px;}
#form-filter-bar .form-group-id{width:80px;}
#form-filter-bar .form-group-ida{width:80px;}
#form-filter-bar .j-btn-submit{float:left;}

.col-reg_st_f{color:#008d4c;}
.col-reg_st_e{color:#dd4b39;}
.col-reg_st_a{color:initial;}
.col-reg_st_s{color:initial;}
</style> 
<script>
var oForm=$('#form-filter-bar');
//aplica a função de barra de filtos
awFilterBar(oForm);
</script>

@endsection