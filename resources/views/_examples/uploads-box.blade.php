@extends('templates.admin.index')


@section('title')
Upload Box - Campo completo de upload
@endsection


@section('content-view')

@php
//$file = \App\Services\FilesService::getModel()->find(674);
//dd($file->getUrlThumbnailAll(), $file->getPathThumbnails());
@endphp


<h3>Upload 00 - Upload por filemanager</h3>
<p>Exemplo de uploads no controller FilesController@post (gravando dados na tabela Files).<br>
    É informado o ID do arquivo da tabela files<br>
    Armazena o file_id de retorno
</p>
@include('templates.components.uploadbox',[
    'controller'=>'files',
    'name'=>'upload01',
    'class'=>'bg-purple',
    'title'=>'Arquivo',
    'value'=>'112',
    //'filemanager'=>true, //gerenciador de arquivos com opções padrão
    'filemanager'=>[ //gerenciador de arquivos com opções personalizadas
        //'onSelectFile'=>'@upl01FncSelectCB'
        'onSelectFile'=>'@function(opt){console.log("Retorno ao selecionar:",opt)}',
        'controller'=>'files',
    ],
    'upload_view'=>['width'=>200,'height'=>200,'filename_show'=>false]
])




<h3>Upload 01 - Upload direto DB Files</h3>
<p>Exemplo de uploads no controller FilesController@post (gravando dados na tabela Files).<br>
    É informado um nome fixo no arquivo 'logo-test-01.png', e sem miniaturas<br>
    Armazena o file_id de retorno
</p>
@include('templates.components.uploadbox',[
    'controller'=>'files',
    'name'=>'upload01',
    'class'=>'bg-purple',
    'title'=>'Arquivo',
    'value'=>'113',
    'upload_db'=>true, //para registrar na tabela 'files'
    'upload'=>[
        //'max_width'=>500,'max_height'=>500,
        'filename'=>'logo-test-01.png',
        'thumbnails'=>false,
        //'accept'=>'image/*'
    ],
    //'upload_form'=>[]
    'upload_view'=>['width'=>200,'height'=>200,'filename_show'=>false]
])



<br><br><br>
<h3>Upload 02 - Upload direto em diretórios</h3>
<p>Exemplo de uploads no controller FilesController@postDirect (gravando direto na pasta, sem registrar na tabela /account/{id}/files).<br>
    É informado um nome fixo no arquivo 'logo-test-01.png', e sem miniaturas<br>
</p>
@include('templates.components.uploadbox',[
    'controller'=>'files',
    'name'=>'upload02',
    'class'=>'bg-green',
    'title'=>'Arquivo',
    'value'=>'http://localhost/robo-gc/robo-gc-v03/public/storage/app/files/logo-test-01.png',
    'upload_db'=>false, //para não registrar na tabela 'files'
    'upload'=>[
        //parâmetros obrigatórios para localização do arquivo sem registrar no DB
        'filename'=>'logo-test-01.png',
        'folder'=>'files',
        //'private'=>true,
        //'account_off'=>true,
        
        
        //'max_width'=>500,'max_height'=>500,
        'thumbnails'=>false,
    ],
    //'upload_form'=>[]
    'upload_view'=>['width'=>200,'height'=>200]
])



<br><br><br>
<h3>Upload 03 - Vários uploads no mesmo form</h3>
@include('templates.ui.auto_fields',[
    'layout_type'=>'horizontal',
    'metabox'=>true,
    'form'=>[
        'url_action'=>route('super-admin.app.post',['example','testSaveAuto']),
        'bt_save'=>'Enviar',
    ],
    'autocolumns'=>[
        'arquivo01'=>['type'=>'uploadbox','label'=>'Upload DB Files 01',
            'controller'=>'files',
            'upload_view'=>['width'=>150,'height'=>150]
        ],
        'arquivo02'=>['type'=>'uploadbox','label'=>'Upload Directory (not DB Files) 02','title'=>'Enviar Imagem',
            'controller'=>'files',
            'upload_db'=>false,
            'upload'=>[
                'filename'=>'logo-test-03.png',
                'folder'=>'files-sys02',
                'account_off'=>true,
            ],
            'upload_view'=>['width'=>150,'height'=>150]
        ],
        'arquivo03'=>['type'=>'uploadbox','label'=>'Upload DB Files - File PDF','title'=>'Escolher arquivo PDF',
            'controller'=>'files',
            'value'=>609,
            'upload'=>[
                'accept'=>'application/pdf',
            ],
            'upload_view'=>['width'=>150,'height'=>150]
        ],
    ]
])


<br><br><br><br><br><br><br>


@endsection

