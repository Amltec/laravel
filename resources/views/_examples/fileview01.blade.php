@extends('templates.admin.index')


@section('title')
Visualizador de Arquivos
@endsection


@section('content-view')
Padrão de visualizador do gerenciador de arquivos.<br><br><br>

@php
    echo '<h3>Arquivo na tabela Files</h2>';
    echo view('templates.ui.files_view',[
        'controller'=>'files',
        'file'=>112,
        'onRemove'=>'@function(r){ console.log(r);alert(r.action);window.location.reload(); }',
        //'fields'=>'title,size,link' 
    ]);
    
    echo '<br><hr><br>';
    
    
    echo '<h3>Arquivo com acesso direto em diretório</h2>';
    echo view('templates.ui.files_view',[
        'filename'=>'logo-main.png',
        'folder'=>'files',
        'account_off'=>true,
    ]);
    
    
@endphp


@endsection
