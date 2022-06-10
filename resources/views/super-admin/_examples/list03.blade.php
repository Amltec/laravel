@extends('templates.admin.index')


@section('title')
Lista de Dados 03 - Taxonomias - código manual
@endsection


@section('content-view')
<p><strong>Explorando todos os recursos</strong> - Tabela de dados usando o template auto_list.blade.<br>
Obs: a janela das taxonomias são aplicadas manualmente via código neste arquivo como exemplo</p>

@php
    use App\Services\FilesService;
    $files = \App\Services\FilesService::getList([
        //'regs'=>_GETNumber('regs')??3,
        //'is_trash'=>$_GET['is_trash']??false,
    ]);
    
    $term_id = 1;
@endphp



@include('templates.ui.auto_list',[
    'list_id'=>'my_table_id1',
    'list_class'=>'table-striped',// table-hover
    
    'data'=>$files['files'],
    'columns'=>[
        //'id'=>'ID',
        'file_title'=>['Título','value'=>function($v){ return '<span class="margin-r-5">'.$v.'</span><span class="custom-class-tag-row"></span>'; }],
        'created_at'=>'Data',
    ],
    'options'=>[
        'collapse'=>true,
        'checkbox'=>true,
        'select_type'=>2,
        'pagin'=>true,
        'regs'=>false,
        'confirm_remove'=>true,
        'toolbar'=>true,
    ],
    'routes'=>[
        'click'=>function($reg){return ($reg->__lock_del?'#':'my-page-test-tmp/'.$reg->id.'/');},
        'collapse'=>route('admin.app.index','example').'/?name=html',
        'remove'=>route('admin.app.post',['example','testDelAuto']),
    ],
    'field_click'=>'file_title',
    'metabox'=>[
        'title'=>'Minha lista de dados',
        'fit_table'=>true //para a tabela encaixar no metabox considerando o padding do metabox
    ],
    'toolbar_buttons'=>[
        ['title'=>'Taxonomia 1','icon'=>'fa-tags','id'=>'bt_tax_1'],
        ['title'=>'Taxonomia 2','icon'=>'fa-tags','id'=>'bt_tax_2'],
    ],
])


@include('templates.ui.taxs_form',[
    'id'=>'box_terms_01',
    'term_id'=>$term_id,
    'is_collapse'=>true,
    'show_icon'=>true,
    'is_popup'=>true
])

<br>

<script>
    
    var oList=$('#my_table_id1');
    var oTaxBox=$('#box_terms_01');
    
    //ao selecionar um item no checkbox
    oTaxBox.on('onClickItem',function(e,opt){
            var oTrsSels=oList.triggerHandler('get_select','obj');
            var item=opt.item;
            if(opt.sel){//add tags
                awTagItem({
                    title:item.attr('data-tax_title'),
                    term_id:item.attr('data-term_id'),
                    tax_id:item.attr('data-tax_id'),
                    color:'green',
                }).appendTo( oTrsSels.find('.custom-class-tag-row') );
              
            }else{//remove tags
                oTrsSels.find('.ui-tagitem').filter(function(){ return $(this).attr('data-tax_id')==item.attr('data-tax_id'); }).remove();
            }
            
            oTaxBox.trigger('close');
        });
        
        
    //ao exibir o checkbox
    $('#bt_tax_1').on('click',function(){
        
        //carrega as tags já adicionadas na lista //return array
        var idsTagSel = oList.triggerHandler('get_select','obj')
                .find('.ui-tagitem').map(function(){ return $(this).attr('data-tax_id'); }).get();
        idsTagSel=uniqueArr(idsTagSel);//elimite a duplicação dos ids
        console.log(idsTagSel)
        
        //deseleciona todos
        oTaxBox.trigger('select',{select:false});
            
        if(idsTagSel.length>0){//existem tags
            //marca os selecioandos
            var tag_id;
            for(var i in idsTagSel){
                tag_id=idsTagSel[i];
                oTaxBox.trigger('select',{id:tag_id,select:true});
            };
            
        }
        
        //exibe o box da taxonomia
        oTaxBox.trigger('show',{position:$(this)});
    });
    
    $('#bt_tax_2').on('click',function(){
        //oTaxBox.trigger('show',{position:$(this)});
        alert('Não configurado');
    });
</script>

<style>
.col-file_title{width:calc(80% - 38px - 30px);}
.col-created_at{width:20%;}
</style>

@endsection

