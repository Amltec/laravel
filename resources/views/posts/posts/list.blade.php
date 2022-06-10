@extends('templates.admin.index')

@php
/*
    Variáveis esperadas:
        $post_type
        $posts
        $prefix
        $area_name
        $area_id
        $post_model
        $thisClass
        $config
        $post_folder_id
*/
use App\Services\PostsService;


$labels = $config['labels'];
$configList = $config['list'];
$termsList = null;
$postFolder = $thisClass->postFolder($post_folder_id);

//inicialização da taxonomia
if($configList['taxs']){
    $termsList = $thisClass->getTermsList('list',$post_folder_id);
}


@endphp



@section('title')
@php
    echo $postFolder ? $postFolder->folder_title . '<small class="strong" style="margin-left:10px;"> - '. $labels['name'] .'</small>': $labels['name'] ;
@endphp
@endsection


@section('toolbar-header')
@php
    $r='';
    if($configList['search']){
            //inicia a configuração
            $params=['metabox'=>false,'autocolumns'=>[],'class'=>'ui-toolbar-line','form_id'=>'form-filter-bar'];
            
            //campo geral para armazenar os ids taxs selecionados
            $params['autocolumns']['taxs_id']=['type'=>'hidden','value'=>_GET('taxs_id')];
            
            //campo de filtros taxonomia
            if($termsList){
                //botão que abre a janela taxs
                foreach($termsList as $term){
                    $params['autocolumns']['term_'.$term->id.'tx']=['title'=>$term->term_title,'type'=>'button_field','icon'=>'fa-tags','class'=>'bg-navy','onclick'=>'fTaxOpen(this,'.$term->id.')','width'=>(strlen($term->term_title)*6)+80];
                    
                    //***** monta o box da taxonomia padrão **** //obs: este comando pode estar escrito em qualquer parte do código
                    echo view('templates.ui.taxs_form',[
                                'id'=>'autofilter_box_terms_'.$term->id,
                                'term_id'=>$term->id,
                                'is_collapse'=>true,
                                'is_popup'=>true,
                                'start_collapse'=>true,
                                'taxs_start'=>_GET('taxs_id'),
                                'is_add'=>false,
                                'is_header'=>false,
                                'class'=>'j-autofilter_box_terms',
                            ]);
                }
            }
            
            //campo de busca
            $params['autocolumns']['search']=['type'=>'search','width'=>200];
            
            $r.= '<td>'. view('templates.ui.toolbar',$params) .'</td>';
    }
    if($configList['bt_add']){
            $r.='<td>'. 
                    view('templates.components.button',['title'=> PostsService::labels($labels,'add_post') ,'color'=>'primary','href'=>FormatUtility::route($prefix.'.app.get',[$post_type,$post_folder_id,'add'])]).
                '</td>';
    }
    if($r)echo '<table><tr>'.$r.'</tr></table>';
@endphp
@endsection


@section('content-view')
@php

//dd($posts);


//*** monta as colunas ***
$columns = [
    'id'                => ['ID','value'=>function($v){ return '<small class="text-muted margin-r-10">'.$v.'</small>'; }],
    'post_visibility'   => ['','alt'=>'Visibilidade',
                                'value'=>function($v) use($thisClass){ return '<span class="fa '. $thisClass->post_visibility_opt[$v]['icon'] .'"></span>'; },
                                'alt_cell'=>function($v,$reg) use($thisClass){ return $thisClass->post_visibility[$reg->post_visibility]; }
                            ],
    'post_title'        => ['Título','value'=>function($v,$reg) use($prefix,$configList,$post_type,$post_folder_id){
                                if($reg->deleted_at)return $v;
                                $v=$reg->post_title;
                                $c=$configList['row_links'];
                                if($c===false){
                                    $r=$v;
                                }else{
                                    $link=FormatUtility::route($prefix.'.app.gets',[$post_type,$post_folder_id,'edit',$reg->id]);
                                    $r= '<a href="'. $link .'" class="strong">'.$v.'</a>';

                                    if($c===true){//padrão
                                        $link_view = $reg->getUrl();
                                        
                                        $c='<div class="j-row-hide1">'.
                                                //'<a href="'. $link .'" class="margin-r-10">ID '.$reg->id .'</a> '.
                                                '<a href="'. $link .'" class="margin-r-10">Editar</a> '.
                                                '<a href="'. $link_view .'" target="_blank">Ver</a> '.
                                         '</div>';
                                    }
                                    if($c)$r .= '<br>'.callstr($c,[$reg],true);
                                }
                                return $r;
                            }],
    'post_resume'       => 'Resumo',
    //'post_content'      => ['Conteúdo','value'=>function($v){return str_limit($v,200);}],
    'post_status'       => ['Status','value'=>function($v) use($thisClass){return $thisClass->post_status[$v]??$v;}],
    'user_id'           => ['Autor','value'=>function($v,$reg){return $reg->user->user_alias; }],
    'created_at'        => ['Cadastro','value'=>function($v){return FormatUtility::dateFormat($v,'datetime2'); }],
    'published_at'      => ['Publicação','value'=>function($v){return FormatUtility::dateFormat($v,'datetime2'); }],
    'account_id'        => ['Conta','value'=>function($v,$reg){return $reg->account_id ? $reg->account->account_name : '-'; }],
    'area_name'         => ['Ref.','value'=>function($v,$reg){return $v ? $reg->area_name . ' #'. $reg->area_id : '-'; }],
    //'taxs_1'            => [],
    //'mb_image'          => 'Imagens',
    //'mb_attach'         => 'Anexos',
];


//*** seleção de colunas ***
if($configList['columns_show']){
    $columns_show = $configList['columns_show'];
}else{//padrão
    $columns_show = array_keys($columns);
    /*foreach(['created_at', 'account_id','area_name','post_content'] as $v){
        if(($key = array_search($v, $columns_show)) !== false)unset($columns_show[$key]);
    }*/
}
//colunas negadas
if($configList['columns_hide']){
    foreach($configList['columns_hide'] as $v){
        if(($key = array_search($v, $columns_show)) !== false)unset($columns_show[$key]);
    }
}

//*** configurações da lista ***
$params=[
    'list_id'=>'post-listdata',
    'list_class'=>'post-listdata',
    'data'=>$posts,
    'columns'=>$columns,
    'options'=>[
        'checkbox'=>true,
        'select_type'=>2,
        'pagin'=>true,
        'confirm_remove'=>true,
        'toolbar'=>true,
        'search'=>false,
        'regs'=>false,
        'columns_sel'=>true,
    ],
    'routes'=>[
        'remove'=>FormatUtility::route($prefix.'.app.remove',$post_type) . ($post_folder_id?'?post_folder_id='.$post_folder_id:'') ,
        'click'=>function($reg) use($prefix,$post_type,$post_folder_id){return FormatUtility::route($prefix.'.app.get',[$post_type,$post_folder_id,'edit',$reg->id,'rd='. urlencode(Request::fullUrl()) ]);},
    ],
    'row_opt'=>[
        'class'=>function($reg){return $reg->post_status=='c'?'row-deleted':'';},
        'lock_click'=>'deleted',
    ],
    'field_click'=>'post_title',
    'metabox'=>true,
];



//*** monta a colunas das taxonomias ***
if($configList['taxs']){
    $r=[];
    if($termsList && $termsList->count()>0){
        $c=$configList['taxs_filter'];
        foreach($termsList as $term){
            $r[ $term->id ]=[
                'show_list'=>'term_'.$term->id.'tx',
                'area_name'=>'posts',
                'tax_form_type'=>'set',
                'term_model'=>$term
            ];
            if($c===true || (is_array($c) && in_array($term->id,$c)) ){//exibe o botão na barra da lista
                $r[ $term->id ]['button']=['icon'=>'fa-tags','color'=>'default','title'=>false,'alt'=>$term->term_title];
            }else{
                $r[ $term->id ]['button']=false;
            }
            
            //cabeçalho das colunas
            $n='term_'.$term->id.'tx';
            $params['columns'][$n]=$term->term_title;
            if(!in_array($n,$columns_show))$columns_show[]=$n;
        }
    }
    //dump($r);
    if($r)$params['taxs']=$r;
}



//*** filtra as colunas de acordo com a configuração ***
    if($configList['columns'])$params['columns'] = array_intersect_key($params['columns'], array_flip($configList['columns']));




//*** filtro de colunas pelo parâmetro get ****
    $columns_show_get = _GET('columns_show');
    if($columns_show_get){//get
        Session::put('posts.'. $post_type .'.columns_show',$columns_show_get);
    }else{//session
        $columns_show_get=Session::get('posts.'. $post_type .'.columns_show');
    }
    if($columns_show_get){
        $columns_show_get=explode(',',$columns_show_get);
        $columns_all=array_keys($params['columns']);
        //ajusta a var $columns_show para conter as colunas de $columns_show_get
        foreach($columns_show_get as $v){
            if(!in_array($v,$columns_all)){
                unset($columns_show_get[$v]);
                if (($key = array_search($v, $columns_show_get)) !== false) { unset($columns_show_get[$key]); }//remove o item
            }
        }
        $columns_show = $columns_show_get;
    }
    $params['columns_show'] = $columns_show;
    //dd('final',$columns_all,$columns_show,$columns_show_get);

    

//*** mescla os parâmetros já montados em $params com as configurações adicionais setadas pelo $auto_list ***
    if($configList['auto_list']){
        $params = FormatUtility::array_merge_recursive_distinct($params,$configList['auto_list']);
    }


//*** escreve a lista na tela ***
echo view('templates.ui.auto_list',$params);

static $jscss_load=true;

@endphp
@if($jscss_load)
@php
$jscss_load=false;
@endphp
<style>
.col-post_title{width:200px;}
.col-id{width:30px;}
.post-listdata .j-row-hide1{visibility:hidden;}
.post-listdata tr:hover .j-row-hide1{visibility:visible;}
.post-listdata .row-item td{}
</style>
<script>
var oForm=$('#form-filter-bar');

//aplica a função de barra de filtos
awFilterBar(oForm);

//botões de taxonomia do filtro
var oBxTerms=$('.j-autofilter_box_terms').on('onClickItem',function(e,opt){
    var txs=[];
    oBxTerms.each(function(){
        var v=$(this).triggerHandler('get_select');
        if(v.length>0)txs.push(v.join(','));
    });
    oForm.find('[name=taxs_id]').val(txs.join(','));
});
function fTaxOpen(bt,term_id){
    var oTaxBox=$('#autofilter_box_terms_'+term_id);
    oTaxBox.trigger('show',{position:$(bt)});
};
</script>
@endif


@endsection