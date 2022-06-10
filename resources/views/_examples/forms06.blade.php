@extends('templates.admin.index')


@section('title')
Criação do formulário direto pelo componente
@endsection


@section('content-view')

@include('templates.ui.form',[
    'url'=>route('admin.app.post',['example','testSaveAuto']),
    'method'=>'post',
    'content'=>function(){
        echo 'Este formulário foi gerado automaticamente pelo template view "templates.ui.form"<br>Consulte o arquivo e documentação para mais informações.';
    },
])


<br><br><br>

@endsection