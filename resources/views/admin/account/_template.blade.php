@extends('templates.admin.index')

@section('title')
{{$account->account_name}}
@endsection


@section('content-view')
@php
/*Variáveis padrões esperadas:
    $account
    $dataAccount
*/


$def_vars = get_defined_vars();
$link_base = route('admin.app.index','account');

echo view('templates.ui.tab',[
    'id'=>'tab_main',
    'tab_active'=>  _GET('pag')??'edit',
    'data'=>[
        'edit'=>['title'=>'Dados','href'=>$link_base],
        'images'=>['title'=>'Logos','href'=>$link_base.'?pag=images'],
        //'config'=>['title'=>'Configurações','href'=>$link_base.'?pag=config'],
        //'pass'=>['title'=>'Usuários do Quiver','href'=>$link_base.'?pag=pass'],
    ],
    'content'=>false,
    'class'=>'no-margin',
]);

echo view('templates.ui.tab_content',['content'=>function() use($def_vars){
    echo View::getSection('content-tab');
}]);

@endphp

    

@endsection