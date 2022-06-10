@extends('templates.admin.index')


@section('title')
Formulários 
@endsection


@section('content-view')
    
    
<strong>Organização de Campos (diretamente pelo autofields)</strong><br><br>

@include('templates.components.metabox',[
        'title'=>'Form Horizontal',
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
])

@include('templates.components.metabox',[
        'title'=>'Form Vertical',
        'content'=>function(){
            echo view('templates.ui.auto_fields',[
                'layout_type'=>'Vertical',
                'autocolumns'=>[
                    'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname2'=>['label'=>'Campo Input','class_group'=>'require','button'=>['title'=>'Action']],
                    'fieldname3'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                ]
            ]);
        }
])

@include('templates.components.metabox',[
        'title'=>'Form Horizontal -> Vertical',
        'content'=>function(){
            echo view('templates.ui.auto_fields',[
                'layout_type'=>'horizontal',
                'autocolumns'=>[
                    'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require','class_label'=>'col-sm-2 text-left'],
                    'fieldname2'=>['label'=>'Campo Input','class_group'=>'require','class_group'=>'form-group-line'],
                    'fieldname3'=>['label'=>'Campo Input - divisão de colunas 5/7','maxlength'=>40,'class_group'=>'require','class_label'=>'col-sm-5 text-left','class_div'=>'col-sm-7'],
                ]
            ]);
        }
])

<br><h3>Divisão de Colunas</h3><br>

@include('templates.components.metabox',[
        'title'=>'2 colunas',
        'content'=>function(){
            echo view('templates.ui.auto_fields',[
                'layout_type'=>'two_column',
                'autocolumns'=>[
                    'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname2'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname3'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname6'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                ]
            ]);
        }
])


@include('templates.components.metabox',[
        'title'=>'3 colunas',
        'content'=>function(){
            echo view('templates.ui.auto_fields',[
                'layout_type'=>'three_column',
                'autocolumns'=>[
                    'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname2'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname3'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname4'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname5'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname6'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                ]
            ]);
        }
])


@include('templates.components.metabox',[
        'title'=>'4 colunas',
        'content'=>function(){
            echo view('templates.ui.auto_fields',[
                'layout_type'=>'four_column',
                'autocolumns'=>[
                    'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname2'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname3'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname4'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname5'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname6'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname7'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname8'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                ]
            ]);
        }
])

@include('templates.components.metabox',[
        'title'=>'Form Horizontal - 2 colunas',
        'content'=>function(){
            echo view('templates.ui.auto_fields',[
                'layout_type'=>'horizontal_two_column',
                'autocolumns'=>[
                    'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname2'=>['label'=>'Campo Input','class_group'=>'require','button'=>['title'=>'Action']],
                    'fieldname3'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname4'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                ]
            ]);
        }
])



@include('templates.components.metabox',[
        'title'=>'Form Row (with form)',
        'content'=>function(){
            echo view('templates.ui.auto_fields',[
                'layout_type'=>'row',
                'autocolumns'=>[
                    'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                    'fieldname2'=>['label'=>'Campo Input','class_group'=>'require','button'=>['title'=>'Action']],
                    'fieldname3'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
                ],
                'form'=>[
                    'bt_save'=>true,
                ]
            ]);
        }
])


@endsection