{{-- 
    **** página de exemplo / teste de taxonomia ****
--}}

@extends('templates.admin.index')


@php
    //variáveis de teste
    $term_id=1;
@endphp



@section('title')
Taxonomia View Page
@endsection


@section('content-view')
    @include('templates.ui.taxs_form',[
        'id'=>'my_term_id1',
        'term_id'=>$term_id,
        //'is_multiple'=>false,
        'is_collapse'=>true,
        'show_icon'=>true,
        //'show_check'=>false,
        //'start_collapse'=>true
        'is_add_checked'=>true,
        //'title'=>'Título da Taxonomia',
        'class_select'=>true,
        
        //teste de valor já carregado
        'taxs_start'=>[13,1],
    ])
    
<br>
<strong>Eventos</strong><br>
<a href="#" onclick="$('#my_term_id1').off('onClickItem').on('onClickItem',function(e,opt){ console.log(opt) });alert('evento adicionado, clique no item da lista');return false;">Adiciona evento ao clicar em um item</a> | 
<a href="#" onclick="$('#my_term_id1').off('onBeforeAdd').on('onBeforeAdd',function(e,opt){ console.log('before',opt) });alert('evento adicionado, adicione novo item na lista');return false;">Adiciona evento Antes de adicionar um item</a> | 
<a href="#" onclick="$('#my_term_id1').off('onAfterAdd').on('onAfterAdd',function(e,opt){ console.log('after',opt) });alert('evento adicionado, adicione novo item na lista');return false;">Adiciona evento Depois de adicionar um item</a> | 

<a href="#" onclick="$('#my_term_id1').trigger('select',{id:18,select:true});return false;">Selecionar linha id 18: true</a> | 
<a href="#" onclick="$('#my_term_id1').trigger('select',{id:18,select:false});return false;">Deselecionar linha id 18: false</a> | 
<a href="#" onclick="$('#my_term_id1').trigger('select',{select:true});return false;">Selecionar todos</a> | 
<a href="#" onclick="$('#my_term_id1').trigger('select',{select:false});return false;">Deselecionar todos</a> | 

<a href="#" onclick="console.log($('#my_term_id1').triggerHandler('get_select','obj'));return false;">Get Selected Rows Objs</a> | 
<a href="#" onclick="console.log($('#my_term_id1').triggerHandler('get_select'));return false;">Get Selected Rows IDs</a> | 

<br><br><br><br><br>



@include('templates.ui.taxs_form',[
    'id'=>'my_term_id2',
    'term_id'=>$term_id,
    'is_multiple'=>false,
    'is_collapse'=>true,
    'show_icon'=>true,
    'is_add_checked'=>true,
    'class_select'=>true
])

<br><br><br><br><br>



@include('templates.ui.taxs_form',[
    'id'=>'my_term_id2',
    'term_id'=>$term_id,
    'is_multiple'=>false,
    'is_collapse'=>true,
    'show_icon'=>true,
    'show_check'=>false,
    'is_add_checked'=>true,
    'class_select'=>true,
    
    //teste de valor já carregado
    //'taxs_start'=>[13,1],
])

<br><br><br><br><br>


@endsection

