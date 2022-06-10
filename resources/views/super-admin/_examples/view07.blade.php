@extends('templates.admin.index')


@section('title')
Visualizador de Dados 07 - Exemplo com metabox e botões
@endsection

@php
$file = \App\Services\FilesService::getModel()->findOrFail(554);
@endphp

@section('content-view')
<p>Usando como referência o registro <strong>files.id=554</strong>.</p>



@include('templates.components.metabox',[
    'title'=>'Dados do arquivo '.$file->file_title .' <span class="badge ">ID '.$file->id.'</span>',
    'content'=>function() use ($file){
            echo view('templates.ui.view',[
                'class'=>'view-hover',
                'class_field'=>'text-right text-muted',
                'model'=>$file,
                'filter'=>['file_name','file_size','file_mimetype','file_path'],
                'data'=>[
                    'id'=>['title'=>'ID'],
                    'file_name'=>['title'=>'Arquivo'],
                    'file_size'=>['title'=>'Tamanho'],
                    'file_mimetype'=>['title'=>'Tipo'],
                    'file_path'=>['title'=>'Caminho'],
                ],
            ]);
    },
    'footer'=>[
        'bt'=>['title'=>'Editar dados','color'=>'primary'],
    ]
])
<br>


@endsection
