@extends('templates.admin.index')

@php
    //variáveis de teste
    $term_id=1;
@endphp

@section('title')
Taxonomia - Adicionando tags
@endsection


@section('content-view')
        @include('templates.ui.taxs_form',[
        'id'=>'my_box_term_'.$term_id,
        'term_id'=>$term_id,
        'is_collapse'=>true,
        //'show_check'=>false,
        'start_collapse'=>true,
        'is_popup'=>true
    ])
    
    <br><br>
    Exemplos de tags (carregadas junto com a página)
    <div id="div-tags" class="clearfix">
        @include('templates.components.tag_item',['opt'=>['color'=>'red','title'=>'Tag sem evento','icon'=>'fa-refresh']])
        @include('templates.components.tag_item',['opt'=>['color'=>'aqua','title'=>'Tag com evento','btClose'=>true,'confirmClose'=>true],'events'=>true,])
        
    </div>
    
    <br><br>
    <button type=button" class="btn btn-primary" id="bt1">Exibir</button>
    <br><br>
    Tags adicionadas a partir da seleção da taxonomia
    <div id="div-tags2" class="clearfix"></div>
    
    <br><br>
    <strong>Eventos</strong> <small>(não programados neste exemplo)</small><br>
    <a>close</a>, <a>onBeforeClose</a>, <a>onClose</a>, <a>onClick</a>
    
    
    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
    
    
<script>
$().ready(function(){
    var term_id='{{$term_id}}';
    var oTaxBox=$('#my_box_term_'+term_id);
    
    $('#bt1').on('click',function(){
       oTaxBox.trigger('show',{position:$(this)});
    });
    
    oTaxBox.on('onClickItem',function(e,opt){
        console.log('click item select',opt)
        
        if(opt.sel){
            var item=opt.item;
            awTagItem({
                title:item.attr('data-tax_title'),
                term_id:item.attr('data-term_id'),
                tax_id:item.attr('data-tax_id'),
                color:'green',
                //icon:'fa-check',
                btClose:true,
                confirmClose:true,
                events:true,
            }).appendTo('#div-tags2');
        
            oTaxBox.trigger('close');
        }
    });
    
});
</script>
@endsection