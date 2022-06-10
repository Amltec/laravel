@extends('templates.admin.index')


@section('title')
Visualizador de Dados 04 - Customização visual
@endsection

@section('content-view')
@php

$data1=[
    'a'=>'Meu texto 1',
    ['title'=>'Campo 1','value'=>'Valor do campo 1'],
    ['title'=>'Campo 2','value'=>function(){ return 'Valor do campo 2'; }],
    ['title'=>'Campo 3 (echo)','value'=>function(){ echo 'Valor do campo 3<br>a<br>b<br>c'; }],
    ['title'=>'Campo 4','value'=>'2019-10-25', 'type'=>'date'],
    ['title'=>'Campo 5','value'=>'123.56', 'type'=>'price','class'=>'text-danger','id'=>'id-field-5','attr'=>['data-a'=>'1','data-b'=>'2'] ],
    ['title'=>'Campo 6 - Array','value'=>['1',2,'a'=>'b']],
];

$data2=[
    ['title'=>'Campo 1','value'=>'Valor do campo 1'],
    ['title'=>'Campo 2','value'=>'Valor do campo 2'],
    ['title'=>'Campo 3','value'=>'Valor do campo 3'],
    ['title'=>'Campo 4','value'=>'Valor do campo 4'],
    ['title'=>'Campo 5','value'=>'Valor do campo 5'],
    ['title'=>'Campo 6','value'=>'Valor do campo 6'],
    ['title'=>'Campo 7','value'=>'Valor do campo 7'],
    ['title'=>'Campo 8','value'=>'Valor do campo 8'],
];
@endphp

<h4>Exemplo geral</h4>
<div class="col-sm-4">
    Padrão
    @include('templates.ui.view',[
        'data'=>$data2,
        'arrange'=>'4-8',
        'class'=>'view-hover view-bordered',
    ])
</div>
<div class="col-sm-4">
    Campos alinhados ao centro
    @include('templates.ui.view',[
        'data'=>$data2,
        'arrange'=>'5-7',
        'class'=>'view-hover view-striped',
        'class_field'=>'text-right text-muted',
    ])
</div>
<div class="col-sm-4">
    Campos e valores em linhas
    @include('templates.ui.view',[
        'data'=>$data2,
        'arrange'=>'',
        'class'=>'view-hover view-bordered view-fields-line',
        'class_field'=>'text-muted',
    ])
</div>



<h4>Estilizando - Borda, ao passar o mouse, linha par/impar</h4>
@include('templates.ui.view',[
    'data'=>$data1,
    //'arrange'=>'5-7',
    'class'=>'view-hover view-bordered view-striped',
    'class_field'=>'text-muted',
])


<h4>Estilizando - tabela compressada</h4>
@include('templates.ui.view',[
    'data'=>$data1,
    'class'=>'view-condensed view-bordered',
])


<h4>Estilizando - tabela larga</h4>
@include('templates.ui.view',[
    'data'=>$data1,
    'class'=>'view-large view-bordered',
])


<h4>Divisão de 2 Colunas</h4>
@include('templates.ui.view',[
    'data'=>$data1,
    'arrange'=>'4-8',
    'class'=>'view-hover view-bordered view-col2',
])

<h4>Divisão de 3 Colunas</h4>
@include('templates.ui.view',[
    'data'=>$data1,
    'arrange'=>'4-8',
    'class'=>'view-hover view-bordered view-col3 view-col-break-520',
])

<h4>Divisão de 4 Colunas</h4>
@include('templates.ui.view',[
    'data'=>$data2,
    'arrange'=>'4-8',
    'class'=>'view-hover view-bordered view-col4',
])

@endsection
