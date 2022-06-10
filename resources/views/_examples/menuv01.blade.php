@extends('templates.admin.index')


@section('title')
Menu Vertical 01
@endsection


@section('content-view')

@include('templates.components.menuv',[
    'id_menu'=>'menuv01',
    'select'=>'b7',
    'sub'=>[
            'h1'=>array('title'=>'Cabeçalho 1','header'=>true),
            'a'=>['title'=>'Item 1','icon'=>'fa-amazon','link'=>'#'],
            'b'=>['title'=>'Item 2','icon'=>'fa-internet-explorer','link'=>'#'],
            'c'=>['title'=>'Item 3','icon'=>'fa-opera','link'=>'#',
                'sub'=>[
                    'c1'=>'Opção Sub A',
                    'c2'=>'Opção Sub B',
                    'c3'=>[
                        'title'=>'Opção Sub C',
                        'sub'=>[
                            'c3b'=>'Opção Sub C a',
                            'c3c'=>'Opção Sub C b',
                        ]
                    ]
                ]
            ],
            'd'=>['title'=>'Item 4','icon'=>'fa-bank','link'=>'#'],
            'e'=>['title'=>'Item 5','icon'=>'fa-book','link'=>'#'],
            'sep',
            'x'=>'Opção X',
            'y'=>'Opção Y',
            'z'=>'Opção Z',
        ]
])


@endsection

