@extends('templates.admin.index')

@php
$value = '
<h1>Qua igitur re ab deo vincitur, si aeternitate non vincitur?</h1>

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Occultum facinus esse potuerit, gaudebit; Tria genera bonorum; At certe gravius. Ea possunt paria non esse. Fortasse id optimum, sed ubi illud: Plus semper voluptatis?

Hoc Hieronymus summum <strong>bonum</strong> esse dixit. De vacuitate doloris eadem sententia erit. Duo Reges: constructio interrete. Hunc vos beatum;

';

//formata o $value adicionando a formatação padrão
$value = \App\Utilities\HtmlUtility::formatHTML($value);

//carrega os dados do editor (não necessário, pois será carregado automaticamente)
//Form::loadScript('ckeditor');
@endphp



@section('title')
Editores de Conteúdo - CKEditor
@endsection


@section('content-view')
<a href="https://ckeditor.com/ckeditor-4/demo/" target="_blank">https://ckeditor.com/ckeditor-4/demo/</a><br>
<a href="https://ckeditor.com/latest/samples/toolbarconfigurator/index.html" target="_blank">https://ckeditor.com/latest/samples/toolbarconfigurator/index.html</a><br><br>

<strong>Importante</strong>: veja no código deste arquivo a utilização da classe \App\Utilities\HtmlUtility::formatHTML() e sanitizeHTML() para formatação e limpeza do HTML.<br>
<strong>Objetivo</strong>: manter o código HTML mais limpo possível para padronização e armazenamento.

<br><br><br>

@include('templates.ui.auto_fields',[
    'form'=>[
        'url_action' => route('super-admin.app.post',['example','testSaveEditor']),
        'bt_save' => true,
    ],
    'layout_type'=>'Vertical',
    'autocolumns'=>[
        'editor01'=>['type'=>'editor','plugin'=>'ckeditor','label'=>'CkEditor <span class="nostrong">(configuração padrão // para menções, utilize: @link2)</span>','height'=>200,
            'filemanager'=>['private'=>true,'show_trash'=>false,'show_remove'=>false,'show_regs'=>false,'show_view_img'=>false],
            'mention'=>['key'=>'@link2'],
        ],
        
        'editor02'=>['type'=>'editor','plugin'=>'ckeditor','label'=>'CkEditor - Curto <span class="nostrong">(com gerenciador de arquivos desativado)</span>','template'=>'short', 'filemanager'=>false, 'mention'=>true],
        
        'editor03'=>['type'=>'editor','plugin'=>'ckeditor','label'=>'CkEditor - Somente para texto','template'=>'text_short', 'mention'=>true],
        
        'editor04'=>['type'=>'editor','plugin'=>'ckeditor','label'=>'CkEditor - Inline com borda','template'=>'inline','class_div'=>'editor-border', 'mention'=>true],
        
        'editor05'=>['type'=>'editor','plugin'=>'ckeditor','label'=>'CkEditor - Inline sem borda','template'=>'inline', 'mention'=>true],
    ]
])

<script>
//*** custom buttom editor ***
ckeditor_fnc_load.push(function(ed){
    if(ed.name!='editor01')return;//somente para este editor
    ed.addCommand("insertImgCmd", {
        exec: function(edt) {
            alert('botão clicado')
        }
    });
    ed.ui.addButton('InsertCustomImage', {
        label: "Insert Image",
        command: 'insertImgCmd',
        toolbar: 'colors',
        icon: 'https://img.icons8.com/color/452/google-logo.png'
    });
});
</script>

@endsection

