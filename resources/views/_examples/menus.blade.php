@extends('templates.admin.index')


@section('title')
Menus
@endsection


@section('content-view')
    
<div class="box box-primary">
<div class="box-body">
    
    <div class="btn-group">
        <a href="#" data-toggle="dropdown">Abrir menu com link 1<span class="caret"></span></a>
        @include('templates.components.menu',[
            'id_menu'=>'test_id_menu',
            'sub'=>[
                'a'=>'Opção A',
                'b'=>'Opção B',
                'c'=>'Opção C',
                'd'=>['title'=>'Opção D - Link','link'=>'http://www.google.com.br'],
                'e'=>['title'=>'Opção E - Attr (onclick)','attr'=>'onclick="window.open(\'http://www.google.com.br\');return false;"'],
            ]
        ])
    </div>
    <br><br><br>
    
    
    
    
    <div class="btn-group">
        <a href="#" data-toggle="dropdown">Abrir menu com link 2 <span class="caret"></span></a>
        @include('templates.components.menu',[
            'sub'=>[
                'a'=>'Opção A',
                'b'=>'Opção B',
                'sep',
                'h1'=>array('title'=>'Cabeçalho','header'=>true),
                'c'=>'Opção C',
                'd'=>array('title'=>'Opção D - Link','icon'=>'fa-star','link'=>'http://www.google.com.br'),
                'e'=>array('title'=>'Opção E - negrito','icon'=>'fa-user','class'=>'strong'),
                'e2'=>array('title'=>'Opção E2 - .dropdown-selected','icon'=>'fa-user','class'=>'dropdown-selected'),
                'f'=>array('title'=>'Opção F - cor','icon'=>'fa-user','class'=>'bg-aqua'),
                'g'=>array('title'=>'Opção G - cor + Alt','icon'=>'fa-star','class'=>'text-aqua','alt'=>'Descritivo adicional'),
                'sep',
                'sub1'=>array('title'=>'Sub Menus 1','sub'=>[
                        'a1'=>'Opção Sub A',
                        'b2'=>'Opção Sub B',
                    ]
                 ),
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
                'h'=>'Opção H',
            ]
        ])
    </div>
    <br><br><br>
    
    
    
    
    <div class="btn-group">
        <a href="#" data-toggle="dropdown">Abrir menu com link 3 <span class="caret"></span></a>
        @include('templates.components.menu',[
            'sub'=>[
                'a'=>['title'=>'Option 1','checkbox'=>true],
                'sep',
                'h1'=>array('title'=>'Cabeçalho','header'=>true),
                'b'=>['title'=>'Option 2','checkbox'=>true, 'checked'=>true],
                'c'=>['title'=>'Option 3','checkbox'=>true,'sub'=>[
                    'a'=>['title'=>'Option 1','checkbox'=>true, 'checked'=>true],
                    'b'=>['title'=>'Option 2','checkbox'=>true, 'checked'=>false],
                    'c'=>['title'=>'Option 3','checkbox'=>true],
                ]],
                'cr'=>['title'=>'Option 3 Right','checkbox'=>true,'class_li'=>'pull-left','sub'=>[
                    'a'=>['title'=>'Option 1','checkbox'=>true],
                    'b'=>['title'=>'Option 2','checkbox'=>true],
                    'c'=>['title'=>'Option 3','checkbox'=>true],
                ]],
                'd'=>['title'=>'Option 4','checkbox'=>true],
                'f'=>['title'=>'Texto 1','icon'=>'fa-refresh'],
                'e'=>['title'=>'Texto 2'],
                'sep',
                'g'=>['title'=>false,'html'=>'<a class="btn btn-primary margin-r-5">Sim</a> <a class="btn btn-danger">Não</a>'],
            ]
        ])
    </div>
    
    
    
    <br><br><br><br><br><br><br><br><br><br><br>
    
</div>
</div>
@endsection