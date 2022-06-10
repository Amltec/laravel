@extends('templates.admin.index')

@section('title')
Editor com Visualizador HTML - <span style='color:red;'>Em análise / desenvolvimento...</span>
@endsection


@section('content-view')
<p>Editor de texto com uma janela de visualização HTML instantânea e integrador com o gerenciador de arquivos.</p>

@php
    echo view('templates.ui.editor_view',[
        //'editor_type'=>'editorcode',
    ]);

@endphp

@endsection