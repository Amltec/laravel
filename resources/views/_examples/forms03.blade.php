@extends('templates.admin.index')


@section('title')
Formulários - Opções autofields
@endsection


@section('content-view')
    
<h4>Template autofield direto no corpo da página</h4>
@include('templates.ui.auto_fields',[
    'layout_type'=>'horizontal',
    'autocolumns'=>[
        'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
        'fieldname2'=>['label'=>'Campo Input','class_group'=>'require','button'=>['title'=>'Action']],
        'fieldname3'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
    ]
])<br>


<h4>Template autofield com metabox automático e botões manuais</h4>
@include('templates.ui.auto_fields',[
    'metabox'=>[
        'title'=>'Título do metabox',
        'header'=>  'Texto adicional',
        'footer'=>  '<button class="btn btn-primary">Botão 1</button>'.
                    '<a href="#" class="btn btn-default pull-right">Botão 2</a>'
    ],
    'layout_type'=>'horizontal',
    'autocolumns'=>[
        'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
        'fieldname2'=>['label'=>'Campo Input','class_group'=>'require','button'=>['title'=>'Action']],
        'fieldname3'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
    ]
])<br>


<h4>Template metabox com autofield dentro</h4>
@include('templates.components.metabox',[
        'header'=>false,
        'content'=>function(){
            echo view('templates.ui.auto_fields',[
                'layout_type'=>'horizontal',
                'autocolumns'=>[
                    'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname2'=>['label'=>'Campo Input','class_group'=>'require','button'=>['title'=>'Action']],
                    'fieldname3'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                ]
            ]);
        }
])<br>




@endsection