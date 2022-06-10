@extends('templates.admin.index')


@section('title')
Barra de Ferramentas
@endsection


@section('content-view')
Padrão de barra de ferramentas para adicionar acima de uma view, form ou lista de dados.<br><br>

@php

echo '<strong>Exemplo 1</strong>';
echo view('templates.ui.toolbar',[
    //'metabox'=>false,
    //'autodata'=>['type'=>'x'],
    //'class'=>'ui-toolbar-line',
    'autocolumns'=>[
        'type'=>['label'=>'Tipo','class_group'=>'','type'=>'select','list'=>[
            'id'=>'ID',
            'nome'=>'Nome',
            'cpf'=>'CPF',
            'ctrl_id'=>'Nº da Apólice',
            'fields'=>'Outros',
        ]],
        'nome'=>['label'=>'Nome','class_group'=>''],
        'status'=>['label'=>'Status','class_group'=>'','type'=>'select','list'=>[''=>'','a'=>'Em andamento','b'=>'Bloqueado','x'=>'Excluído']],
        'msg'=>['label'=>'Mensagem','class_group'=>''],
        'cfilter'=>['label'=>'Filtro','class_group'=>'','type'=>'select','list'=>[
            ''=>'',
            'process_test:s'=>'Teste',
            'error_code:not_insurer'=>'Erro: Seguradora não encontrado',
            'error_code:repeat'=>'Ignorado: Apólice repetida',
            'error_code:endosso'=>'Ignorado: Endosso não programado',
            'error_code:not_product'=>'Ignorado: Produto inválido',
            'error_code:other'=>'Erro: Outros',
        ]],
    ]
]);




echo '<strong>Exemplo 2</strong>';
echo view('templates.ui.toolbar',[
    'metabox'=>['is_border'=>false],
    'class'=>'ui-toolbar-line ui-toolbar-marg-label',
    'autocolumns'=>[
        'field1'=>['type'=>'text','label'=>'Meu campo'],
        'status'=>['class_group'=>'','type'=>'select','list'=>[''=>'','a'=>'Em andamento','b'=>'Bloqueado','x'=>'Excluído']],
        'bt1'=>['type'=>'button','title'=>'Botão 1'],
        'bt2'=>['type'=>'button','icon'=>'fa-edit','title'=>false,'color'=>'primary'],
        'bt3'=>['type'=>'button','icon'=>'fa-refresh','title'=>false,'color'=>false,'class'=>'btn-danger'],
        'field2'=>['type'=>'text'],
        //'btgroup1'=>['type',
    ]
]);



echo '<strong>Exemplo 3</strong>';
echo view('templates.ui.toolbar',[
    'metabox'=>['is_border'=>false],
    'is_filter'=>false,
    'class'=>'ui-toolbar-line ui-toolbar-marg-label form-no-padd',
    'autocolumns'=>[
        'field1'=>['type'=>'text','label'=>'Meu campo'],
        'status'=>['class_group'=>'','type'=>'select','list'=>[''=>'','a'=>'Em andamento','b'=>'Bloqueado','x'=>'Excluído']],
        'bt1'=>['type'=>'button','title'=>'Botão 1'],
        'bt2'=>['type'=>'button','icon'=>'fa-refresh','title'=>false,'color'=>false,'class'=>'btn-danger'],
        'field2'=>['type'=>'text'],
        'bt4'=>['type'=>'button','title'=>'Botão R2','color'=>'primary','class'=>'pull-right margin-right-none'],
        'bt3'=>['type'=>'button','title'=>'Botão R1','color'=>'primary','class'=>'pull-right'],
    ]
]);

@endphp
@endsection