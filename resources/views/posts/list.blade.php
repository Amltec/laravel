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
*/
use App\Services\PostsService;


$labels = $config['labels'];
$configList = $config['list'];


@endphp



@section('title')
@php
    echo $labels['name'];
@endphp
@endsection


@section('toolbar-header')
@php
    echo view('templates.components.button',['title'=> PostsService::labels($labels,'add_post') ,'color'=>'primary','href'=>route($prefix.'.app.get',['posts','add'])]);
@endphp
@endsection


@section('content-view')
@php

//dd($posts);

//*** seleção de colunas ***
$columns_show = _GET('columns_show');/*
if($columns_show){//get
    Session::put('process.cad_apolice.columns_show',$columns_show);
}else{//session
    $columns_show=Session::get('process.cad_apolice.columns_show');
}
if(!$columns_show)$columns_show='id,account,process_name_prod,data_type,broker,insurer,nome,term_1tx,created_at,status_msg';
*/

//*** monta as colunas ***
$columns = [
    'id'                => ['ID','value'=>function($v){ return '<small class="text-muted margin-r-10">'.$v.'</small>'; }],
    'post_title'        => ['Título','value'=>function($v,$reg) use($prefix){
                                $link=route($prefix.'.app.get',['posts','edit',$reg->id]);
                                $r= '<a href="'. $link .'" class="strong">'.$v.'</a> <br>'.
                                    '<div class="hiddenx j-row-hide1">'.
                                        //'<a href="'. $link .'" class="margin-r-10">ID '.$reg->id .'</a> '.
                                        '<a href="'. $link .'" class="margin-r-10">Editar</a> '.
                                        '<a href="'. $link .'">Ver</a> '.
                                    '</div>';
                                return $r;
                            }],
    'post_resume'       => 'Resumo',
    'post_content'      => ['Conteúdo','value'=>function($v){return str_limit($v);}],
    'post_status'       => ['Status','value'=>function($v) use($thisClass){return $thisClass::$post_status[$v]??$v;}],
    'post_visibility'   => ['Visibilidade','value'=>function($v) use($thisClass){return $thisClass::$post_visibility[$v]??$v;}],
    'user_id'           => ['Autor','value'=>function($v,$reg){return $reg->user->user_alias; }],
    'created_at'        => ['Cadastro','value'=>function($v){return FormatUtility::dateFormat($v,'datetime2'); }],
    'published_at'      => ['Publicação','value'=>function($v){return FormatUtility::dateFormat($v,'datetime2'); }],
    'account_id'        => ['Conta','value'=>function($v,$reg){return $reg->account_id ? $reg->account->account_name : '-'; }],
    'area_name'         => ['Ref.','value'=>function($v,$reg){return $v ? $reg->area_name . ' #'. $reg->area_id : '-'; }],
    //'taxs_1'            => [],
    //'mb_image'          => 'Imagens',
    //'mb_attach'         => 'Anexos',
];



//*** configurações da lista ***
$params=[
    'list_id'=>'post-listdata',
    //'list_class'=>'table-striped',// table-hover
    'data'=>$posts,
    'columns'=>$columns,
    'columns_show'=>$columns_show,
    'options'=>[
        'checkbox'=>true,
        'select_type'=>2,
        'pagin'=>true,
        'confirm_remove'=>true,
        'toolbar'=>true,
        'search'=>false,
        'regs'=>false,
        
    ],
    'routes'=>[
        'remove'=>route($prefix.'.app.remove','posts'),
    ],
    'row_opt'=>[
        'class'=>function($reg){return $reg->post_status=='c'?'row-deleted':'';}
    ],
    'metabox'=>true,
];



//*** monta a colunas das taxonomias ***
if($configList['taxs']){
    $taxs = is_array($configList['taxs'])?$configList['taxs']:$config['taxs'];
    if($taxs)$termsList = \App\Models\Term::whereIn('id',$taxs)->orderByRaw("FIELD(id,". join(',',$taxs) .")")->get();
    /*
    if($taxs && $termsList->count()>0){
        foreach($termsList as $term){
            $columns['taxs_'.$term->id] = [$term->term_title,'value'=>function($v,$post) use($term){
                $tax_itens=$post->getTaxsData($term->id);
                $r = join(', ',array_pluck($tax_itens,'tax_title'));
                echo $r?$r:'-';
            }];
        }
    }*/
    
    $r=[];
    if($taxs && $termsList->count()>0){
        foreach($termsList as $term){
            $r[ $term->id ]=[
                'show_list'=>'term_'.$term->id.'tx',
                'button'=>['icon'=>'fa-tags','color'=>'default','title'=>false,'alt'=>$term->term_title],
                'area_name'=>'posts',
                'tax_form_type'=>'set',
                'term_model'=>$term
            ];
            //cabeçalho das colunas
            $params['columns']['term_'.$term->id.'tx']=$term->term_title;
        }
    }
    //dump($r);
    if($r)$params['taxs']=$r;
}





//*** escreve a lista na tela ***
echo view('templates.ui.auto_list',$params);


@endphp

<style>
.col-post_title{width:200px;}
#post-listdata tbody tr:hover .j-row-hide1{display:block !important;}
</style>


@endsection