@extends('templates.admin.index')

@section('title')
@php
    echo $account->account_name;
    echo '<small>#'. $account->id.'</small>';
    echo '<span style="font-size:0.8em;"> <span style="margin:0 10px;">|</span> ' . View::getSection('title-tab') .'</span>';
    
    if($account->trashed()){
        echo '<span class="label label-danger" style="font-size:10px;margin-left:10px;">conta excluída</span>';
    }else if($account->account_status=='c'){
        echo '<span class="label label-danger" style="font-size:10px;margin-left:10px;">conta cancelada</span>';
    }
@endphp
@endsection


@section('content-view')

@php
/*Variáveis padrões esperadas:
    $account
    $dataAccount
*/


$def_vars = get_defined_vars();
$link_base = route('super-admin.app.edit',['accounts',$account->id]);

echo view('templates.ui.tab',[
    'id'=>'tab_main',
    'tab_active'=>  _GET('pag')??'info',
    'data'=>[
        'info'=>['title'=>'Informações','href'=>$link_base],
        'edit'=>['title'=>'Dados','href'=>$link_base.'?pag=edit'],
        'images'=>['title'=>'Logos','href'=>$link_base.'?pag=images'],
        'users'=>['title'=>'Usuários','href'=>$link_base.'?pag=users'],
        'config'=>['title'=>'Configurações','href'=>$link_base.'?pag=config'],
        'pass'=>['title'=>'Usuários do Quiver','href'=>$link_base.'?pag=pass'],
        'actions'=>['title'=>'Ações','href'=>$link_base.'?pag=actions'],
    ],
    'content'=>false,
    'class'=>'no-margin',
]);

echo view('templates.ui.tab_content',['content'=>function() use($def_vars){
    echo View::getSection('content-tab');
}]);

@endphp

@if($account->trashed())
<style>
.content-header,.content{color:red;}
</style>    
@endif
    

@endsection