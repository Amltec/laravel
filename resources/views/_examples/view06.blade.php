@extends('templates.admin.index')


@section('title')
Visualizador de Dados 06 - Exemplo de filtros de campos
@endsection

@section('content-view')
@php
    $file = \App\Services\FilesService::getModel()->findOrFail(554);
@endphp


<p>Escreve a tabela filtrando/restringindo campos.
<br>Usando como referência o registro <strong>files.id=554</strong>.
<br>Mais detalhes veja o código deste arquivo.</p>


@include('templates.ui.view',[
    'class'=>'view-bordered',//view-col4
    'class_field'=>'text-muted',
    //'arrange'=>'',
    'model'=>$file,
    
    //filtra os campos
    'filter'=>['id','file_title','file_size'],
])
<br>


Filtro por campos não vazios<br>
@include('templates.ui.view',[
    'class'=>'view-bordered',//view-col4
    'class_field'=>'text-muted',
    //'arrange'=>'',
    'model'=>$file,
    
    //filtra os campos
    'filter'=>'not_empty',
])
<br><br>


@endsection
