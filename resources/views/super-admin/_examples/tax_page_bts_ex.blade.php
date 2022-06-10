{{-- 
    **** página de exemplo / teste de taxonomia acionado por botões popup ****
--}}

@extends('templates.admin.index')

@php
    //variáveis de teste
    $term_id=1;
@endphp

@section('title')
Taxonomia - Modelo como popup - acionado por botões
@endsection


@section('content-view')
    
    <button type=button" class="btn btn-primary" id="bt1">Exibe apenas &nbsp; <span class="btn-xs btn-info">considerando a última posição</span></button>
    <button type=button" class="btn btn-primary" id="bt2">Exibe com x,y</button>
    <button type=button" class="btn btn-primary" id="bt3" style="position:absolute;left:600px;top:200px;">Exibe com this!</button>
    
    

    @include('templates.ui.taxs_form',[
        'id'=>'my_box_term_'.$term_id,
        'term_id'=>$term_id,
        //'is_multiple'=>false,
        'is_collapse'=>true,
        'show_icon'=>true,
        //'show_check'=>false,
        //'start_collapse'=>true,
        'is_popup'=>true
    ])
    
    
    
<script>
$().ready(function(){
    var term_id='{{$term_id}}';
    var oTaxBox=$('#my_box_term_'+term_id);
    
    $('#bt1').on('click',function(){
       oTaxBox.trigger('show');
    });
    $('#bt2').on('click',function(){
       oTaxBox.trigger('show',{position:[400,150]});
    });
    $('#bt3').on('click',function(){
       oTaxBox.trigger('show',{position:$(this)});
    });
});
</script>
@endsection