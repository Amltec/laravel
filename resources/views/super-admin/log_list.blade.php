@extends('templates.admin.index')

@php
use App\Services\LogsService;

/*
Vars esperadas:
    $model
    $filter
    $userLogged
*/
@endphp


@section('title')
Logs do Sistema
@endsection


@section('content-view')
@php

$level_list = \App\Services\UsersService::$levels;
if($userLogged->user_level!='dev')unset($level_list['dev']);
$prefix = Config::adminPrefix();

echo view('templates.ui.toolbar',[
    'form_id'=>'form-filter-bar',
    'autodata'=>$filter,
    'autocolumns'=>[
        'account_id'=>['label'=>'ID Conta','width_group'=>70],
        'user_id'=>['label'=>'ID Usuário','width_group'=>73],
        'user_level'=>['label'=>'Nível','type'=>'select','list'=>[''=>'']+$level_list],
        'area_name'=>['label'=>'Nome da Área','width_group'=>105],
        'area_id'=>['label'=>'ID Área','width_group'=>70],
        'action'=>['label'=>'Ação','type'=>'select','list'=>[''=>'']+LogsService::$log_label],
        'dts'=>['label'=>'Data Inicial','type'=>'date','width_group'=>105],
        'dte'=>['label'=>'Data Final','type'=>'date','width_group'=>105],
    ]
]);

echo '<div class="row">
        <div class="col-md-7">
            <div class="box box-primary"><div class="box-body" id="log-list">';
    
            echo view('templates.ui.auto_list',[
                'data'=>$model,
                'columns'=>[
                    'id'=>'ID',
                    'account'=>['Conta Logada','value'=>function($v){ return $v??'-'; }],
                    'user'=>['Usuário Logado','value'=>function($user_name,$reg){ return '<span title="Usuário ID #'.$reg->user_id.' - '.$user_name.'">'. ($user_name ? str_limit($user_name,10) .' <small class="text-muted">'.$reg->user_level.'</small>' : '-' ) .'</span>'; }],
                    'area'=>['Área','value'=>function($v,$reg){return $reg->area_name .' #'.$reg->area_id;}],
                    'log_data'=>['Log','value'=>function($v,$reg){
                        return LogsService::getResumeData($reg);
                    }],
                    'created_at'=>'Data'
                ],
                'routes'=>[
                    //'click'=>function($reg){return route('super-admin.app.show',['logs',$reg->id,'rd='. urlencode(Request::fullUrl()) ]);},
                ],
                'metabox'=>false,
                'list_class'=>'table-condensed',
            ]);
            
            echo '</div></div>'; //end.box

    echo '</div>
        <div class="col-md-5" style="padding-left:0px;">
            <div class="box box-primary">
                <div class="box-body" id="log-view">Clique em um item da lista para carregar</div>
            </div>
        </div>
    </div>';



@endphp



<script>
(function(){
    //resize view
    var oList=$('#log-list');
    var oView=$('#log-view');
    var fResize=function(){
        oView.height('auto');
        oList.height('auto');
        var h1=oList.height();
        var h2=oView.height();
        var h=h1>h2?h1:h2;
        oView.height(h);
        oList.height(h);
    };
    fResize();
    $(window).on('resize',fResize);
    
    //list
    oList.on('click','tr[data-id]',function(e){
        var tr=$(this);
        var id=tr.attr('data-id');
        var url=String('{{route($prefix.".app.show",["logs",':id'])}}').replace(':id',id);
        if(e.ctrlKey || e.shiftKey){
            goToUrl(url);
        }else{
            oView.html('Carregando...');
            awAjax({
                url:url,processData:true,dataType:'html',method:'GET',
                success: function(r){
                    oView.html(r);
                    oView.find('.ui-view:eq(0)').removeClass('view-bordered');//remove a primeira borda
                    fResize();
                },
                error:function(xhr){
                    oView.html(xhr.responseText);
                    fResize();
                }
            });
        };
    })
}());
</script>

@endsection