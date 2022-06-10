@extends('templates.admin.index')


@section('title')
Visualizador de Dados 02 - Views Recursivas
@endsection

@section('content-view')

<h4>Escrevendo uma tabela dentro da tabela</h4>

@include('templates.ui.view',[
    'class'=>'view-bordered',
    'data'=>[
        'a'=>'Meu texto 1',
        ['title'=>'Campo 1','value'=>'Valor do campo 1'],
        ['title'=>'Array','value'=>['fruta'=>'Laranja','cidade'=>'Itapeva','uf'=>'SP']],
        ['title'=>'Array by param type','type'=>'array','value'=>['fruta'=>'Laranja','cidade'=>'Itapeva','uf'=>'SP']],
        ['title'=>'Multi Array','value'=>['fruta'=>['Laranja','Pera','Maça'],'cidade'=>'Itapeva','uf'=>'SP']],
        'anonymus_function'=>['title'=>'View by anonymus function - Button 1','value'=>function(){ echo view('templates.components.button',['title'=>'Primary','color'=>'primary']); }],
        ['title'=>'View by param type - Button 1','type'=>'@templates.components.button','value'=>['title'=>'Primary','color'=>'primary'] ],
    ],
    //'hide_title'=>true,
])

<br><br>

<h4>Escrevendo um ARRAY na tabela</h4>

@include('templates.ui.view',[
    'class'=>'view-bordered',
    'data'=>[
        'a'=>'Meu texto 1',
        ['title'=>'Campo 1','value'=>'Valor do campo 1'],
        ['title'=>'Array','value'=>['fruta'=>'Laranja','cidade'=>'Itapeva','uf'=>'SP']],
        ['title'=>'Array by param type','type'=>'array','value'=>['fruta'=>'Laranja','cidade'=>'Itapeva','uf'=>'SP']],
        ['title'=>'Multi Array','value'=>['fruta'=>['Laranja','Pera','Maça'],'cidade'=>'Itapeva','uf'=>'SP']],
        'anonymus_function'=>['title'=>'View by anonymus function - Button 1','value'=>function(){ echo view('templates.components.button',['title'=>'Primary','color'=>'primary']); }],
        ['title'=>'View by param type - Button 1','type'=>'@templates.components.button','value'=>['title'=>'Primary','color'=>'primary'] ],
        
    ],
    
    'data_type'=>'array',
    //'hide_title'=>true,
])



@endsection
