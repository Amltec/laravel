@extends('templates.admin.index')

@php
/*
    Variáveis esperadas:
        $post_type
        $post
        $prefix
        $area_name
        $area_id
        $post_model
        $thisClass
        $config
        $postFolder
        $termsList
*/
use App\Services\PostsService;


$labels = $config['labels'];
$configView = $config['view'];
$postData = $post->getData();

if($termsList){
    $taxsList=[];
    foreach($termsList as $term){
        $taxsList[$term->id] = $post->getTaxsData($term->id,'ids');
    }
    
}else{
    $taxsList=null;
}
$taxs_select = last($taxsList[$term->id]??[]);

$content_type = $post->content_type;
$vars = get_defined_vars();


@endphp



@push('head')
    <link rel="stylesheet" href="{{asset('css/post-view.css?ver=1.0')}}">
@endpush


@section('title_bar')
{{$post->post_title .' | '. $thisClass->getLabel('name') }}
@endsection


@section('content-view')
@php
    
    
    echo '<div class="post-container"><div class="row">';
            //menu
            if($configView['tax_menu'])echo view('posts.posts.templates-view.menuleft',$vars);
            
            //content
            echo '<div class="postview-content'. ($configView['tax_menu']?' hash-menu col-xs-12 col-sm-10':' col-sm-12') .'">';
                echo '<div class="viewer ui-shadow">';
                    
                    if($configView['is_edit']) echo view('posts.posts.templates-view._editbar',$vars);
                    if($configView['title'])                        echo view('posts.posts.templates-view.title',$vars + ['is_resume'=>$configView['resume']]);
                    if($configView['taxs'])                         echo view('posts.posts.templates-view.taxs',$vars);
                    if($configView['content'])                      echo view('posts.posts.templates-view.content',$vars);
                    if(in_array('image',$configView['metaboxs']))   echo view('posts.posts.templates-view.image',$vars);
                    if(in_array('attach',$configView['metaboxs']))  echo view('posts.posts.templates-view.attach',$vars);

                echo '</div>';
            echo '</div>';
        
    echo '</div></div>';
    
@endphp


@endsection
