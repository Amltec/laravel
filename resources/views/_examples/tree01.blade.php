@extends('templates.admin.index')


@section('title')
Diretórios (manual)
@endsection


@section('content-view')
<p>Tabela de dados usando o componente tree.blade. Testando opções.</p>

@include('templates.components.tree',[
    //'class_menu'=>'tree-condensed tree-bordered tree-hover',
    //'icon_def'=>'fa-refresh',
    //'collapse_def'=>false,
    //'show_caret'=>false,
    'id_menu'=>'tree01',
    'select'=>'b7',
    'sub'=>[
            'a'=>'Opção A',
            'b'=>'Opção B',
            'sub1'=>array('title'=>'Sub Menus 1x','icon_color'=>'#ffcc33','icon_def'=>'fa-check',//,'collapse'=>false
                'sub'=>[//,'link'=>'http://www.google.com.br'
                    'a1'=>'Opção Sub A',
                    'b2'=>'Opção Sub B',
                    'c3'=>['title'=>'Opção Sub C','sub'=>[
                        'a6'=>'Opção Sub A6',
                        'b7'=>'Opção Sub B7',
                    ]],
                ]
             ),
            'c'=>'Opção C',
            'sep',
            'd'=>'Opção D',
            'h1'=>array('title'=>'Cabeçalho 1','header'=>true),
            'e'=>array('title'=>'Opção E - Link','icon'=>'fa-star','link'=>'http://www.google.com.br'),
            
            'f'=>array('title'=>'Opção F - uppercase','icon'=>'fa-user','class'=>'text-uppercase'),
            'g'=>array('title'=>'Opção G - cor','icon'=>'fa-user','class'=>'text-red'),
            'h'=>array('title'=>'Opção H - cor + Alt','icon'=>'fa-star','class'=>'text-aqua','alt'=>'Descritivo adicional'),
            'sep',
            'sub2'=>array('title'=>'Sub Menus 2','sub'=>[
                    'a1'=>array('title'=>'X01','sub'=>
                            [
                                'a1'=>'Opção Sub A',
                                'b2'=>'Opção Sub B',
                            ]),
                    'a2'=>array('title'=>'X01','sub'=>
                            [
                                'a1'=>'Opção Sub A',
                                'b2'=>'Opção Sub B',
                            ])
                ]
             ),
            'j'=>'Opção J',
        ]
])


<br>
<strong>Eventos</strong><br>
<a href="#" onclick="$('#tree01').addClass('tree-condensed');return false;">Condensar: on</a> |
<a href="#" onclick="$('#tree01').removeClass('tree-condensed');return false;">Condensar: off</a>

@endsection

