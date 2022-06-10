@extends('templates.admin.index')


@section('title')
Visualizador de Dados 03 - De objeto Models
@endsection

@section('content-view')
@php
    $obj1 = new stdClass;
    $obj1->a=1;
    $obj1->b=2;
    $obj1->c=[1,2,3];
    
    $file = \App\Services\FilesService::getModel()->findOrFail(553);
    
@endphp


<h4>Escrevendo um objeto stdClass, Model, etc</h4>
@include('templates.ui.view',[
    'class'=>'view-bordered',
    'data'=>[
        'a'=>'Meu texto 1',
        ['title'=>'Array','value'=>'Valor do campo 1'],
        ['title'=>'Object stdClass','value'=>$obj1],
        ['title'=>'Model File','value'=>$file,'type'=>'model'],
    ],
    //'hide_title'=>true,
])

<br><br><br>

<h4>Escrevendo uma Model diretamente em 'data' </h4>
@include('templates.ui.view',[
    'data'=>$file,
    'data_type'=>'model',
    'class'=>'view-bordered',
])

<br><br><br>

<h4>Escrevendo uma Model formatando os dados com os parâmetros 'model' e 'data' </h4>
@include('templates.ui.view',[
    'data'=>[
        'file_title'=>['title'=>'Título'],
        'created_at'=>['type'=>'datetime'],
        'updated_at'=>['title'=>'Atualização'],
        'file_ext'=>['format'=>function($v){ return 'Extensão *'.strtoupper($v).'*'; }],
    ],
    'model'=>$file,
    'class'=>'view-bordered',
])


@endsection
