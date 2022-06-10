@extends('templates.admin.index')

@php
$value = '
<h1>Qua igitur re ab deo vincitur, si aeternitate non vincitur?</h1>

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Occultum facinus esse potuerit, gaudebit; Tria genera bonorum; At certe gravius. Ea possunt paria non esse. Fortasse id optimum, sed ubi illud: Plus semper voluptatis?

Hoc Hieronymus summum <strong>bonum</strong> esse dixit. De vacuitate doloris eadem sententia erit. Duo Reges: constructio interrete. Hunc vos beatum;

';

//formata o $value adicionando a formatação padrão
$value = \App\Utilities\HtmlUtility::formatHTML($value);

//carrega os dados do editor
//Form::loadScript('ckeditor');
//Form::loadScript('select2');
@endphp



@section('title')
Editores de Conteúdo 
@endsection


@section('content-view')

<strong>Importante</strong>: veja no código deste arquivo a utilização da classe \App\Utilities\HtmlUtility::formatHTML() e sanitizeHTML() para formatação e limpeza do HTML.<br>
<strong>Objetivo</strong>: manter o código HTML mais limpo possível para padronzização e armazenamento.

<br><br><br>

@include('templates.ui.auto_fields',[
    'form'=>[
        'url_action' => route('super-admin.app.post',['example','testSaveEditor']),
        'bt_save' => true,
    ],
    'layout_type'=>'Vertical',
    'autocolumns'=>[
        'editor01'=>['type'=>'editor','label'=>'CkEditor <span class="nostrong">(opções do gerenciador de arquivos customizadas)</span>','height'=>200,
            'filemanager'=>['private'=>true,'show_trash'=>false,'show_remove'=>false,'show_regs'=>false,'show_view_img'=>false]
        ],
        
        'editor02'=>['type'=>'editor','label'=>'CkEditor - Curto <span class="nostrong">(com gerenciador de arquivos desativado)</span>','template'=>'short', 'filemanager'=>false],
        
        'editor03'=>['type'=>'editor','label'=>'CkEditor - Somente para texto','template'=>'text_short'],
        
        'editor04'=>['type'=>'editorcode','label'=>'Editor de Código','filemanager'=>true,'value'=>' wqe '.chr(10).' qeqwe w'.chr(10).' qeqwe w'],
    ]
])



@endsection
