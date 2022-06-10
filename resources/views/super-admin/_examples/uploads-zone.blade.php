@extends('templates.admin.index')


@section('title')
Zona de Uploads - Drag and Drop - (tabela files)
@endsection


@section('content-view')


@include('templates.ui.auto_fields',[
    'metabox'=>['title'=>'Manual - Upload por campo zona de upload sem vínculo com o formulário'],
    'form'=>[
        'url_action'=>route('admin.file.post'),
        'bt_save'=>'Enviar',
        'files'=>true,
        'data_opt'=>[
            'onSuccess'=>'@function(v){console.log("uploaded file",v);}',
        ]
    ],
    'autocolumns'=>[
        'info01'=>['type'=>'info','text'=>'
                <b>Observação:</b><br>
                Neste modo não foi informado o parâmetro [form][data_opt][btAutoUpload] e por isto perde-se o retorno dos eventos: onBefore,onSuccess... podem faltar parâmetros de retorno.
        '],
        'file'=>['type'=>'uploadzone','label'=>'Arquivo','id'=>'filezone_01'],
        'line01'=>function(){
            return '<a href="#" onclick="$(\'#filezone_01\').trigger(\'set\',{status:\'R\'});">Event Uploadzone success</a>';
        },
        'action'=>['type'=>'hidden','value'=>'upload'],//necessário para indicar a ação de upload
    ]
])



@include('templates.ui.auto_fields',[
    'metabox'=>['title'=>'Upload direto após escolher o arquivo <small>(parâmetros em form[data-opt])</small>'],
    'form'=>[
        'id'=>'form2',
        'url_action'=>route('admin.file.post'),
        'files'=>true,
        'data_opt'=>[
            'btAutoUpload'=>'[name=file]',
            'onBefore'=>'@function(v){console.log("before file",v);}',
            'onProgress'=>'@function(v){console.log("progress file",v);}',
            'onSuccess'=>'@function(v){console.log("uploaded file",v);}',
        ],
    ],
    'autocolumns'=>[
        'info01'=>['type'=>'info','text'=>'
            <b>Informações:</b><br>
            Foi usado o campo input[name=data-opt] para passar parâmetros específicos do upload.<br>
            Foi usado a função o parâmetro de data-opt do formulário para passar as configurações para este botão.<br>
            Utilizado o parâmetro "onProgress" como função callback no formato de string ("@function")(Veja em console.log())
        '],
        'file'=>['type'=>'uploadzone','label'=>'Arquivo','title'=>'Título personalizado do UploadZone'],
        'action'=>['type'=>'hidden','value'=>'upload'],//necessário para indicar a ação de upload
        //parâmetros adicionais
        'data-opt'=>['type'=>'hidden','value'=>
            json_encode(['private'=>true])
         ]
    ]
])




<script>
function fnc_uplBefore(v){
    console.log("before",v);
}
function fnc_uplSuccess(v){
    console.log("success",v);
}
function fnc_uplError(v){
    console.log("error",v);
}
function fnc_uplProgress(v){
    console.log("progress",v);
}
function fnc_uplStart(v){
    console.log("----- start -----",v);
}
function fnc_uplComplete(v){
    console.log("----- complete ----",v);
    
}

/*
$().ready(function(){
    awUploadProgress({
        add_files:[
            {id:'001',title:'meu arquivo 1.jpg', status:'A', ext:'jpg'},
            {id:'002',title:'meu arquivo 2.jpg', status:'A', ext:'jpg'},
            {id:'003',title:'meu arquivo 3.jpg', status:'A', ext:'jpg'},
        ],
        //set_files:{id:'001',status:'R',perc:50}
    });
    setTimeout(function(){
        awUploadProgress({set_files:{id:'001',status:'L'}});
    },1000);
    /*setInterval(function(){
        awUploadProgress({add_files:[
            {id:'001',title:'meu arquivo 1.jpg', status:'A', ext:'jpg'},
        ]});
    },2000);* /
});
*/

</script>
@include('templates.ui.auto_fields',[
    'metabox'=>['title'=>' Upload múltiplo'],
    'form'=>[
        'id'=>'form3',
        'url_action'=>route('admin.file.post'),
        'files'=>true,
        'data_opt'=>[
            'btAutoUpload'=>'[name=file]',
            'onBefore'=>'@fnc_uplBefore',
            'onSuccess'=>'@fnc_uplSuccess',
            'onError'=>'@fnc_uplError',
            'onProgress'=>'@fnc_uplProgress',
            'onStart'=>'@fnc_uplStart',
            'onComplete'=>'@fnc_uplComplete',
        ],
    ],
    'autocolumns'=>[
        'info01'=>['type'=>'info','text'=>'
            <b>Informações:</b><br>
            Foi usado a função o parâmetro de data-opt do formulário para passar as configurações para este botão.<br>
            Utilizado o parâmetro "on[Before|Success|Error|Progress|Start|Complete]" como função callback no formato de string ("@function") para obter status do upload (Veja em console.log())<br>
            Utilizado a janela de informações de progresso do upload.
        '],
        'file'=>['type'=>'uploadzone','label'=>'Arquivo','class_button'=>'info','title'=>'Upload Múltiplos', 'multiple'=>true],//'icon'=>false, 
        'action'=>['type'=>'hidden','value'=>'upload'],//necessário para indicar a ação de upload
    ]
])



@include('templates.ui.auto_fields',[
    'metabox'=>['title'=>' Upload múltiplo com UploadZone em todo o formulário +botão'],
    'form'=>[
        'id'=>'form3-formzone1',
        'url_action'=>route('admin.file.post'),
        'files'=>true,
        'data_opt'=>[
            //seta para criar dinamicamente o campo file com o filezone
            //é recomendado que tenha a mesma configuração de botão de upload abaixo
            //obs: não precisa adicionar o nome, pois tem o botão de upload abaixo
            'fileszone'=>['multiple'=>true],
            
            //botão de upload dentro do form
            'btAutoUpload'=>'[name=file]',
            
            'onBefore'=>'@fnc_uplBefore',
            'onSuccess'=>'@fnc_uplSuccess',
            'onError'=>'@fnc_uplError',
            'onProgress'=>'@fnc_uplProgress',
            'onStart'=>'@fnc_uplStart',
            'onComplete'=>'@fnc_uplComplete',
        ],
    ],
    'autocolumns'=>[
        'info01'=>['type'=>'info','text'=>'
            <b>Informações:</b><br>
            Aqui foi usado o parâmetro data-opt do formulário adicionar "fileszone" para criar dinamicamente o campo de zona de upload.<br>
        '],
        'file'=>['type'=>'upload','label'=>'Arquivo','class_button'=>'info','title'=>'Upload Múltiplos', 'multiple'=>true],//'icon'=>false, 
        'info02'=>['type'=>'info','text'=>'
            <h3>Arraste o arquivo em qualquer local por aqui ou clique no botão acima</h3><br>
        '],
        'action'=>['type'=>'hidden','value'=>'upload'],//necessário para indicar a ação de upload
    ]
])




@include('templates.ui.auto_fields',[
    'metabox'=>['title'=>' Upload múltiplo com UploadZone em todo o formulário -botão'],
    'form'=>[
        'id'=>'form3-formzone2',
        'url_action'=>route('admin.file.post'),
        'files'=>true,
        'data_opt'=>[
            //seta para criar dinamicamente o campo file com o filezone
            //é recomendado que tenha a mesma configuração de botão de upload abaixo
            //obs: não precisa adicionar o nome, pois tem o botão de upload abaixo
            'fileszone'=>['name'=>'file','multiple'=>true],
            
            //botão de upload dentro do form
            'btAutoUpload'=>'[name=file]',
            
            'onBefore'=>'@fnc_uplBefore',
            'onSuccess'=>'@fnc_uplSuccess',
            'onError'=>'@fnc_uplError',
            'onProgress'=>'@fnc_uplProgress',
            'onStart'=>'@fnc_uplStart',
            'onComplete'=>'@fnc_uplComplete',
        ],
    ],
    'autocolumns'=>[
        'info01'=>['type'=>'info','text'=>'
            <b>Informações:</b><br>
            Aqui foi usado o parâmetro data-opt do formulário adicionar "fileszone" para criar dinamicamente o campo de zona de upload.<br>
        '],
        'info02'=>['type'=>'info','text'=>'
            <h3>Arraste o arquivo em qualquer local por aqui apenas</h3><br>
        '],
        'action'=>['type'=>'hidden','value'=>'upload'],//necessário para indicar a ação de upload
    ]
])






<script>
$().ready(function(){
    var oUplModal;
    $('#botao04').on('click',function(){
        if(oUplModal){
            oUplModal.modal('show');
        }else{
            oUplModal=awModal({
                //obs: o form precisa ser criado por este parâmetro para ser compatível com o filezzone
                form:'method="POST" action="<?=route('admin.file.post')?>" accept-charset="UTF-8" enctype="multipart/form-data"',
                title:'Selecione ou arraste o arquivo',
                decription:'Limite de até 123MB',
                html:function(oHtml){
                    var r=  '<input type="hidden" name="action" value="upload">'+
                            '<div class="btn btn-info btn-upload">'+
                                '<i class="fa fa-upload" style="margin-right:5px;"></i> '+
                                '<span>Upload Múltiplos</span>'+
                                '<input type="file" class="form-control " name="file" accept="image/*" multiple="multiple"></div>'+
                            '</div>';
                    oHtml.html(r);
                    awFormAjax(oHtml.closest('form'),{
                        btAutoUpload:'[name=file]',
                        onComplete:function(opt){
                            if(opt.status=='R')setTimeout(function(){ oUplModal.modal('hide'); },1000);
                        },
                        fileszone:{maximize:true}
                    });
                },
            });
        }
    });
});
</script>
@include('templates.ui.auto_fields',[
    'metabox'=>['title'=>' Upload em janela Modal - criado manualmente'],
    'autocolumns'=>[
        'info01'=>['type'=>'info','text'=>'
            <b>Informações:</b><br>
            O form é montado diretamente via javascript dentro de uma janela Modal.<br>
            Os parâmetros do formulário são passados diretamente na função awFormAjax()
        '],
        'botao04'=>['type'=>'button','title'=>'Upload','color'=>'warning','id'=>'botao04'],
    ]
])




<script>
var count=0;var arr=[];
$().ready(function(){
    $('#botao05').on('click',function(){
        awUploadModal({
            route:'<?=route('admin.file.post')?>',
            multiple:true,
            form:{
                //msg:'',//para não criar o campo mensagem
                //fileszone:{maximize:true},
                fileszone:{maximize:true},
                dataFields:{
                    'data-opt':{private:true},
                    custom_field:123,
                },
                onSuccess:function(opt){
                    console.log('ex 5 - file',opt)
                },
                onComplete:function(opt){
                    console.log('ex 5 - terminou')
                }
            }
        });
    });
});
</script>
@include('templates.ui.auto_fields',[
    'metabox'=>['title'=>' Upload em janela Modal - usando a função awUploadModal()'],
    'autocolumns'=>[
        'info01'=>['type'=>'info','text'=>'
            <b>Informações:</b><br>
            Exemplo da janela modal padrão de uploads e uploadzone.<br>
            Campos adicionados do formulários enviado pelo parãmetro form.dataFields. Ex: seta a pasta privada
        '],
        'botao05'=>['type'=>'button','title'=>'Upload','color'=>'danger','id'=>'botao05'],
    ]
])

<br><br><br><br><br><br><br>


@endsection

