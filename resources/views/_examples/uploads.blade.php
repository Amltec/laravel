@extends('templates.admin.index')


@section('title')
Uploads - (tabela files)
@endsection


@section('content-view')


@include('templates.ui.auto_fields',[
    'metabox'=>['title'=>'Manual - Upload por campo input file sem vínculo com o formulário'],
    'form'=>[
        'url_action'=>route('super-admin.file.post','files'),
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
        'file'=>['type'=>'upload','label'=>'Arquivo'],
        'action'=>['type'=>'hidden','value'=>'upload'],//necessário para indicar a ação de upload
    ]
])



@include('templates.ui.auto_fields',[
    'metabox'=>['title'=>'Upload direto após escolher o arquivo <small>(parâmetros em form[data-opt])</small>'],
    'form'=>[
        'id'=>'form2',
        'url_action'=>route('super-admin.file.post','files'),
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
        'file'=>['type'=>'upload','label'=>'Arquivo','class_button'=>'primary','value'=>'Upload','icon'=>'fa-cloud-upload'],
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
        'url_action'=>route('super-admin.file.post','files'),
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
        'file'=>['type'=>'upload','label'=>'Arquivo','class_button'=>'info','value'=>'Upload Múltiplos', 'multiple'=>true],//'icon'=>false, 
        'action'=>['type'=>'hidden','value'=>'upload'],//necessário para indicar a ação de upload
    ]
])






<script>
$().ready(function(){
    var oUplModal;
    //modelo 1 - form escrito diretamente em html
    $('#botao04a').on('click',function(){
        //*** obs: a técnica com a var 'oUplModal' que evita duplicação da instância awModal() não é recomendada neste exemplo de upload, pois algumas alterações no DOM não são recriadas neste caso ***
        //if(oUplModal){
        //    oUplModal.modal('show');
        //}else{
            oUplModal=awModal({
                title:'Selecione o arquivo',
                decription:'Limite de até 123MB',
                html:function(oHtml){
                    var r='<form method="POST" action="<?=route('super-admin.file.post','files')?>" accept-charset="UTF-8" enctype="multipart/form-data">'+
                            '<input type="hidden" name="action" value="upload">'+
                            '<div class="btn btn-info btn-upload">'+
                                '<i class="fa fa-upload" style="margin-right:5px;"></i> '+
                                '<span>Upload Múltiplos</span>'+
                                '<input type="file" class="form-control " name="file" accept="image/*" multiple="multiple"></div>'+
                            '</div>'+
                            //como o form foi escrito diretamente neste html, é necessário o comando abaixo para capturar o retorno após o submit
                            '<br><br><div class="alert alert-danger alert-form hiddenx"><p class="alert-msg"></p></div>'+
                        '</form>';
                    oHtml.html(r);
                    awFormAjax(oHtml.find('form'),{
                        btAutoUpload:'[name=file]',
                        onSuccess:function(opt){
                            console.log('A success',opt);
                        },
                        onComplete:function(opt){
                            console.log('A complete',opt)
                            if(opt.status=='R')setTimeout(function(){ oUplModal.modal('hide'); },1000);
                        }
                    });
                },
            });
        //}
    });
    
    //modelo 2 - form com o recurso padrão do awModal
    $('#botao04b').on('click',function(){
        //*** obs: a técnica com a var 'oUplModal' que evita duplicação da instância awModal() não é recomendada neste exemplo de upload, pois algumas alterações no DOM não são recriadas neste caso ***
        //if(oUplModal && false){
        //    oUplModal.modal('show');
        //}else{
            oUplModal=awModal({
                title:'Selecione o arquivo',
                decription:'Limite de até 123MB',
                html:function(oHtml){
                    var r=  '<input type="hidden" name="action" value="upload">'+
                            '<div class="btn btn-info btn-upload">'+
                                '<i class="fa fa-upload" style="margin-right:5px;"></i> '+
                                '<span>Upload Múltiplos</span>'+
                                '<input type="file" class="form-control " name="file" accept="image/*" multiple="multiple"></div>'+
                            '</div>';
                    oHtml.html(r);
                },
                form:'method="POST" action="{{route('super-admin.file.post','files')}}" accept-charset="UTF-8" enctype="multipart/form-data"',
                btSave:'Salvar',
                form_opt:{
                    //dataFields:{name:value,...},
                    onSuccess:function(opt){
                       console.log('B success',opt);
                    },
                    onComplete:function(opt){
                        console.log('B complete',opt);
                        if(opt.status=='R')setTimeout(function(){ oUplModal.modal('hide'); },1000);
                    }
                }
            });
        //}
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
        'botao04a'=>['type'=>'button','title'=>'Upload A','color'=>'warning','id'=>'botao04a'],
        'botao04b'=>['type'=>'button','title'=>'Upload B ','color'=>'warning','id'=>'botao04b'],
    ]
])




<script>
var count=0;var arr=[];
$().ready(function(){
    $('#botao05').on('click',function(){
        awUploadModal({
            route:'<?=route('super-admin.file.post','files')?>',
            multiple:true,
            form:{
                dataFields:{'custom_1':123,'custom_2':456},
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
            Exemplo da janela modal padrão de uploads.<br>
        '],
        'botao05'=>['type'=>'button','title'=>'Upload','color'=>'danger','id'=>'botao05'],
    ]
])








@include('templates.ui.auto_fields',[
    'metabox'=>['title'=>' Upload em janela Modal - gravando arquivo com nome fixo (utiliza o campo file_name), e fora do diretório /uploads/{year}/{month}, e sem miniatura'],
    'autocolumns'=>[
        'info01'=>['type'=>'info','text'=>'
            <b>Informações:</b><br>
            Nome padrão: "logo01.jpg".<br>
        '],
        'botao06'=>['type'=>'button','title'=>'Upload','id'=>'botao06', 'class'=>'bg-purple'],
    ]
])
<script>
$('#botao06').on('click',function(){
    awUploadModal({
        route:'<?=route('super-admin.file.post','files')?>',
        //multiple:true,
        form:{
            dataFields:{
                'custom_1':123,'custom_2':456,
                'data-opt':{
                    //metadata:{meta_name:'x01',meta_value:'y01'}
                    //account_off:true,
                    folder_date:false,
                    folder:'files',
                    //private:true,
                    thumbnails:false,
                    //max_width:600,
                    //max_height:600,
                    //image_fit:!false,
                    filename:'logo01.jpg',
                    filetitle:'Meu Logo 01'
                }
            },
            onSuccess:function(opt){
                console.log('ex 6 - upload success',opt)
            },
            onComplete:function(opt){
                console.log('ex 6 - terminou',opt)
            }
        }
    });
});
</script>







<br><br><br><br><br><br><br>


@endsection

