@extends('templates.admin.index')

@section('title')
{{array_get($configProcessNames,$process_name.'.title')}}
@endsection


@section('toolbar-header')
@can('admin')
<a href="#" class="btn btn-primary" id="bt-add-process">Nova Busca de Apólices</a>
@endcan
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


$prefix = Config::adminPrefix();


//*** test ***
//dd(\App::call('App\Http\Controllers\Process\ProcessSeguradoraFilesController@doExtracted',[246]));


echo view('templates.components.metabox',[
        'content'=>function() use($filter,$status_list,$prefix){
            if($filter['status'] && !in_array($filter['status'],$status_list)){//o status informado, não existe na lista, portanto adiciona-o como opção personalizada
               $status_list = $status_list + [$filter['status'] => 'Personalizado: ' . $filter['status']];
            }

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
                    'id'=>['label'=>'ID','class_group'=>''],
                    'status'=>['label'=>'Status','class_group'=>'','type'=>'select','list'=>[''=>'']+$status_list],
                    'dts'=>['label'=>'Data Inicial','class_group'=>'','type'=>'date'],
                    'dte'=>['label'=>'Data Final','class_group'=>'','type'=>'date'],
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
                'process_name_prod'=>['Processo','value'=>function($v,$reg) use($configProcessNames){return array_get($configProcessNames,$reg->process_name.'.products.'.$reg->process_prod.'.title'); }],
                'dts_search'=>['Período Busca','value'=>function($v,$reg){
                    if($reg->process_ctrl_id=='manual'){
                        return 'Manual';
                    }else{
                        $dts = $reg->data_array['dts_search']??'';
                        if($dts){
                            $dts=explode('|',$dts);//dti,dtf
                            return FormatUtility::dateFormat($dts[0],'date') .' - '. FormatUtility::dateFormat($dts[1],'date');
                        }else{
                            return '-';
                        }
                    }
                }],
                'created_at'=>['Data','value'=>function($v){ return FormatUtility::dateFormat($v,'d/m/Y H:i'); }],
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
                'mark_done'=>['Quiver','value'=>function($v,$reg){
                    return $reg->status_mark_done;
                }],
                'mark_done_err'=>['Erros <br>ao Marcar','value'=>function($v,$reg){
                    $sts=$reg->count_status_mark_done;
                    $n = $sts['e'] + $sts['i'];
                    return '<span class="'. ($n>0?'text-red':'') .'">'. $n .'</span>';
                }],
                'mark_done_st_0'=>['Aguardando <br>Indexação','value'=>function($v,$reg) use($prefix){
                    $sts=$reg->count_status_wait_index;
                    $n = $sts['0'];
                    return '<a class="'. ($n>0?'text-blue':'') .'" href="'.   route($prefix.'.app.show',['process_seguradora_files',$reg->id,'st=0&rd='. urlencode(Request::fullUrl()) ])   .'">'. $n .'</a>';
                }],
                'mark_done_st_p'=>['Pronto <br>para Marcar','value'=>function($v,$reg){
                    $sts=$reg->count_status_ready_mark;
                    $n = $sts['a'] + $sts['b'];
                    return '<span class="'. ($n>0?'text-blue':'') .'">'. $n .'</span>';
                }],
                /*'files'=>['Download','title'=>'Marcado como concluído no Quiver','value'=>function($v,$reg){
                    if($reg->process_status=='f'){
                        $r='<a href="'. $reg->link_file_download .'" class="btn btn-primary" title="Download dos arquivos baixados"><i class="fa fa-files-o"></i></a>';
                    }else{
                        $r='-';
                    }
                    return $r;
                }]*/
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
                'click'=>function($reg) use($prefix){return route($prefix.'.app.show',['process_seguradora_files',$reg->id,'rd='. urlencode(Request::fullUrl()) ]);},
                'remove'=>route($prefix.'.app.remove','process_seguradora_files'),
            ],
            //'field_click'=>'id',
            'row_opt'=>[
                'class'=>function($reg){
                    if($reg->account->account_status!='a'){
                        return 'text-red';
                    }else{
                        return $reg->status_color['text'];
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
    #form-filter-bar .form-group{width:140px;float:left;padding-right:10px;}
    #form-filter-bar .form-group-account_id{width:65px;}
    #form-filter-bar .form-group-id{width:80px;}
    #form-filter-bar .j-btn-submit{float:left;}
</style>
<script>
(function(){
    var oForm=$('#form-filter-bar');

    //aplica a função de barra de filtos
    awFilterBar(oForm);

    $('#bt-add-process').on('click',function(){
        awModal({
            title:'Novo processo',
            html:function(oHtml){
                oHtml.html(
                    @if($prefix=='super-admin')
                    '<p>IDs das contas</p>'+
                    '<div class="form-group" id="form-group-account_id">'+
                        '<div class="control-div"><input type="password" value="" autocomplete="off" class="form-control" name="account_id" placeholder="Separar ids por virgula - caso vazio processa em todas as contas"><span class="help-block"></span></div>'+
                    '</div>'+
                    @endif
                    '<div class="row">'+
                        '<div class="form-group col-md-6" id="form-group-dti">'+
                            'Data Inicial<div class="control-div"><input type="text" data-mask="99/99/9999" value="{{$count>0 ? '' : date("d/m/Y",strtotime("-1 days",strtotime(date("Y-m-d"))))}}" class="form-control" name="dti" placeholder="Deixe vazio para última data de processamento" ><span class="help-block"></span></div>'+
                        '</div>'+
                        '<div class="form-group col-md-6" id="form-group-dtf">'+
                            'Data Final<div class="control-div"><input type="text" data-mask="99/99/9999" value="{{$count>0 ? '' : date("d/m/Y")}}" class="form-control" name="dtf" placeholder="Deixe vazio a data atual" ><span class="help-block"></span></div>'+
                        '</div>'+
                    '</div>'+
                    @if($prefix=='super-admin')
                    '<div class="row">'+
                        '<div class="form-group col-md-12">'+
                            '<label class="nostrong"><input type="checkbox" name="upload_manual" value="s"><span class="checkmark"></span> Upload Manual</label>'+
                        '</div>'+
                    '</div>'+
                    @endif
                    ''
                );
                @if($prefix=='super-admin')setTimeout(function(){oHtml.find('input:eq(0)').attr('type','text').focus();},600);@endif
            },
            btClose:false,
            btSave:'Salvar',
            form:'method="POST" action="{{route($prefix.'.app.post',['process_seguradora_files','add_process'])}}" accept-charset="UTF-8" ',
            form_opt:{
                onSuccess:function(r){
                    if(r.success)window.location.reload();
                },
                fields_log:false
            }
        });
        return false;
    });
}());
</script>

@endsection
