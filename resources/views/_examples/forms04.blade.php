@extends('templates.admin.index')


@section('title')
Formulários - Autofields com forms
@endsection


@section('content-view')
   
<h4>Template autofield com formulário e botões automático</h4>
@include('templates.ui.auto_fields',[
    'form'=>[
        'url_action'=>'xxx',
        'url_back'=>'xxx',
        //'data_opt'=>['focus'=>true],
        //'url_success'=>'xxx',
        'bt_save'=>true,
        'bt_back'=>true,
    ],
    'metabox'=>[
        'header'=>  false,
        //'footer'=>  '<button type="submit" class="btn btn-primary" id="submit">Salvar</button>'
    ],
    'autocolumns'=>[
        'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
        'fieldname2'=>['label'=>'Campo Input','class_group'=>'require','button'=>['title'=>'Action']],
        'fieldname3'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
    ]
])

<br><br><br>


   
<h4>Template autofield apenas com os campos e o formulário gerado manualmente no html</h4>
{{Form::open(['url'=>'','form-auto-init'=>'on'])}}
@include('templates.ui.auto_fields',[
    'metabox'=>[
        'is_border'=>false,
        'title'=> 'Bloco 1',
    ],
    'autocolumns'=>[
        'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
        'fieldname2'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
    ]
])
@include('templates.ui.auto_fields',[
    'metabox'=>[
        'is_border'=>false,
        'title'=> 'Bloco 2',
    ],
    'autocolumns'=>[
        'fieldname3'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
        'fieldname4'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require'],
    ]
])
<button type="submit" class="btn btn-primary">Salvar</button>

@include('templates.components.button',['type'=>'submit'])

<a href="#" class="btn btn-default pull-right">Voltar</a>
<div class="clearfix"></div><br>

@include('templates.components.alert-structure')

{{Form::close()}}



<br><br><br>

@endsection