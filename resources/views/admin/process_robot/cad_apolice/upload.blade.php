@extends('templates.admin.index')

@section('title')
{{$configProcessNames['cad_apolice']['title']}} - Envio de Arquivos
@endsection

@section('description')
Arquivos no formato PDF para processamento do robô
@endsection


@section('content-view')
@php
    //dd($configProcessNames);
    /*$sel_processos = [];
    foreach($configProcessNames as $name=>$val){
        $sel_processos[$name]=$val['title'];
    }*/
    
    
    $accountConfig = Config::accountConfig();
    $products_active = array_filter(array_get($accountConfig,'cad_apolice.products_active')??[]);
    //dd($configProcessNames, $products_active );
    
    $prod_list = [''=>''];
    foreach($configProcessNames['cad_apolice']['products'] as $name=>$val){
        if(!$products_active || in_array($name,$products_active))$prod_list[$name]=$val['title'];
    }
    
    echo view('templates.ui.auto_fields',[
        'metabox'=>true,
        'layout_type'=>'horizontal',
        'form'=>[
            'id'=>'form-upload',
            'url_action'=>route('admin.app.post',['process_cad_apolice','upload']),
            'files'=>true,
            //'alert'=>false,
            'bt_save'=>'Enviar Arquivos',
            'data_opt'=>[
                'btAutoUpload'=>'[name=file]',
                'submitAutoUpload'=>false,
                'onUploadProgress'=>'@onUploadProgress',
                //'onBefore'=>'@function(v){console.log("before file",v);}',
                //'onProgress'=>'@function(v){console.log("progress file",v);}',
                //'onSuccess'=>'@function(v){console.log("uploaded file",v);}',
                //'onError'=>'@function(v){console.log("uploaded file",v);}',
            ],
        ],
        'autocolumns'=>[
            //'process_name'=>['type'=>'select','label'=>'Processo','list'=>$sel_processos,'id'=>'field-process_name'],
            'process_prod'=>['type'=>'select','label'=>'Produto','list'=>$prod_list,'id'=>'field-process_prod'],
            
            'file'=>['type'=>'upload','label'=>'Upload','class_button'=>'primary','value'=>'Selecionar','accept'=>'application/pdf','multiple'=>true],
            'action'=>['type'=>'hidden','value'=>'upload'],//necessário para indicar a ação de upload
        ]
    ]);
    
    echo '<div id="container-page-uplprog"></div>';
    
    
/*
echo '<script>
    var listNames='. json_encode($configProcessNames) .';
    $("#field-process_name").on("change",function(){
        var v=this.value;
        var n=listNames[v];
        var oProd=$("#field-process_prod").html("");
        if(n){
            var r="";
            for(var i in n.products){
                r+="<option value=\'"+i+"\'>"+n.products[i].title+"</option>";
            };
            oProd.html(r);
        };
    }).triggerHandler("change");
    
   //$(".ui-upload-progress-box")
</script>';
*/

echo '<style>
    .ui-upload-progress-box.insert_page{position:relative;right:auto;margin:auto;width:auto;}
    .ui-upload-progress-box.insert_page .box-body{max-height:none;overflow:visible;}
    .ui-upload-progress-box .box-header [data-action=close]{display:none;}
    .ui-upload-title-msg{display:inline-block;}
    .ui-upload-progress-box li{padding-left:20px;}
    .ui-upload-progress-box li .pull-right{left:-10px;position:absolute;}
</style>
';

       
@endphp

<script>
function onUploadProgress(obj){
    obj.base.addClass("insert_page").appendTo("#container-page-uplprog");
}
</script>

@endsection
