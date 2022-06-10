@extends('templates.admin.index')


@section('title')
Lista de Dados 02 - usando ajax
@endsection


@section('content-view')
<p>Tabela de dados usando o template auto_list.blade.</p>


@php
    //Obs: os dados da var $list_params estão no controller ExampleController@index?name=list02

@endphp
@include('templates.ui.auto_list',$list_params)


<br>
<strong>Eventos AJAX</strong><br>
<a href="#" onclick="$('#my_table_id1').trigger('load');return false;">Recarrega lista</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('load',{page:2});return false;">Carrega a lista na página 2</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('load',{page:'prev'});return false;">Carrega a lista na página na página anterior</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('load',{page:'next'});return false;">Carrega a lista na página na página seguinte</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('load',{pos:'before'});return false;">Carrega a lista e insere antes</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('load',{pos:'after'});return false;">Carrega a lista e insere depois</a> | 
<a href="#" onclick="$('#my_table_id1').trigger('load',{id:25});return false;">Atualiza a lista somente linha id 25</a> | 
<a href="#" onclick="console.log($('#my_table_id1').triggerHandler('get_select','obj'));return false;">Get Rows Objs</a> | 
<a href="#" onclick="console.log($('#my_table_id1').triggerHandler('get_select'));return false;">Get Rows IDs</a> | 


<script>
(function(){
    $('#my_table_id1').on('onOpen',function(e,opt){
        console.log('custom click row',opt);
        return false;//return false para anular o click
    })
}());
</script>

@endsection

