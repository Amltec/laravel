@extends('templates.admin.index')


@section('title')
Botões - Post Dados
@endsection


@section('content-view')

<p>Exemplos de botões que fazem um post de dados diretamente no controller.</p>

Dados postados: 'data'=>['a'=>1,'b'=>true,'c'=>'teste'] - ver retorno no console.log<br>
@include('templates.components.button',[
    'title'=>'Seta Post Data 1','color'=>'primary',
    'post'=>[
        'url'=>route('super-admin.app.post',['example','testSaveAuto']),
        'data'=>['test_json'=>'ok','a'=>1,'b'=>true,'c'=>'teste'],
        'cb'=>'@function(r){console.log("ex1",r)}'
    ]
])


<br><br><br>


Dados postados: 'data'=>['a'=>1,'b'=>true,'c'=>'teste'] - ver retorno no console.log<br>
@include('templates.components.button',[
    'title'=>'Seta Post Data 2 - com mensagem','color'=>'primary',
    'post'=>[
        'url'=>route('super-admin.app.post',['example','testSaveAuto']),
        'data'=>['test_json'=>'ok','a'=>1,'b'=>true,'c'=>'teste'],
        'confirm'=>'Confirmar este post data?', //or 'true',
        'cb'=>'@ex2_callback',
    ]
])
<script>
function ex2_callback(r){console.log('ex2',r)}
</script>

@endsection