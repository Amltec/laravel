@extends('templates.admin.index')

@php
$value_text = '
<h1>Qua igitur re ab deo vincitur, si aeternitate non vincitur?</h1>

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Occultum facinus esse potuerit, gaudebit; Tria genera bonorum; At certe gravius. Ea possunt paria non esse. Fortasse id optimum, sed ubi illud: Plus semper voluptatis?

Hoc Hieronymus summum <strong>bonum</strong> esse dixit. De vacuitate doloris eadem sententia erit. Duo Reges: constructio interrete. Hunc vos beatum;
';


//exemplo de html com código para inserir dentro do editor
$value_html = '<!-- Create a simple CodeMirror instance -->
<link rel="stylesheet" href="lib/codemirror.css">
<script src="lib/codemirror.js"></script>
<script>
  var editor = CodeMirror.fromTextArea(myTextarea, {
    lineNumbers: true
  });
</script>';


$value_markdown='GitHub Flavored Markdown
========================

Everything from markdown plus GFM features:

## URL autolinking

Underscores_are_allowed_between_words.

## Strikethrough text

GFM adds syntax to strikethrough text, which is missing from standard Markdown.

~~Mistaken text.~~
~~**works with other formatting**~~

~~spans across
lines~~

## Fenced code blocks (and syntax highlighting)

```javascript
for (var i = 0; i < items.length; i++) {
    console.log(items[i], i); // log them
}
```

## Task Lists

- [ ] Incomplete task list item
- [x] **Completed** task list item

## A bit of GitHub spice

See http://github.github.com/github-flavored-markdown/.

(Set `gitHubSpice: false` in mode options to disable):

* SHA: be6a8cc1c1ecfe9489fb51e4869af15a13fc2cd2
* User@SHA ref: mojombo@be6a8cc1c1ecfe9489fb51e4869af15a13fc2cd2
* User/Project@SHA: mojombo/god@be6a8cc1c1ecfe9489fb51e4869af15a13fc2cd2
* \#Num: #1
* User/#Num: mojombo#1
* User/Project#Num: mojombo/god#1

(Set `emoji: false` in mode options to disable):

* emoji: :smile:
';




//Teste de variável autodata
$autodata_test = (object)[
    'editor01'=>'Meu Texto 2',
    'editor01_html_pre'=>true,
];


@endphp





@section('title')
Editores de Código
@endsection




@section('content-view')


@include('templates.ui.auto_fields',[
    'metabox'=>true,
    'form'=>[
        'url_action' => route('super-admin.app.post',['example','testSaveEditor']),
        'bt_save' => true,
        'data_opt'=>[
            'fields_log'=>false,
        ],
        'class'=>'form-no-padd',
    ],
    'layout_type'=>'Vertical',
    //'autodata'=>$autodata_test,
    'autocolumns'=>[
        'editor01'=>['type'=>'editorcode','label'=>'Editor com tema padrão e botões customizados','filemanager'=>true,'value'=>$value_text,
            'html_pre'=>true,   //'value_pre'=>true,
            'toolbar'=>[
                //'fullscreen','filemanager',
                true,
                ['title'=>'Botão 1','color'=>'link','onclick'=>'$("[data-type=editorcode][name=editor01]").trigger("setValue","xxxx");'],
                //view('templates.components.button',['title'=>'Botão 2','color'=>'link']),
                function(){ return '<span class="margin-r-10 btn cursor-default">Meu texto de exemplo</span>'; },
            ]
        ],
        'editor02'=>['type'=>'editorcode','label'=>'Editor com tema escuro, altura automática e menções com @link2','value'=>$value_html,
            'theme_dark'=>true,'height'=>200,'auto_height'=>400,
            'mention'=>['key'=>'@link2'],
        ],
        
        'editor03_css'=>['type'=>'editorcode','label'=>'Editor CSS','height'=>100,'auto_height'=>true,
            'editor_mode'=>'css',
            'value'=>'.class{color:red;}',
        ],
        'editor04_js'=>['type'=>'editorcode','label'=>'Editor JS','height'=>100,'auto_height'=>true,
            'editor_mode'=>'js',
            'value'=>'var a=123;console.log(a);',
        ],
        'editor05_markdown'=>['type'=>'editorcode','label'=>'Editor Markdown','height'=>100,'auto_height'=>true,
            'editor_mode'=>'markdown',
            'value'=>$value_markdown,
        ]
    ]
])



@endsection
