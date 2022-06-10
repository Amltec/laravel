@extends('templates.public.index')

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
*/
use App\Services\PostsService;

$labels = $config['labels'];
$configView = $config['view'];
$postData = $post->getData();

$taxs = is_array($configView['taxs'])?$configView['taxs']:$config['taxs'];
$termsList = $taxs ? \App\Models\Term::whereIn('id',$taxs)->orderByRaw("FIELD(id,". join(',',$taxs) .")")->get() : null;
if($termsList && $termsList->count()==0)$termsList=null;
if($termsList){
    $taxsList=[];
    foreach($termsList as $term){
        $taxsList[$term->id] = $post->getTaxsData($term->id,'ids');
    }
}else{
    $taxsList=null;
}

$content_type = $post->content_type;
$vars = get_defined_vars();

@endphp

@section('title')
{{$post->post_title .' | '. $thisClass->getLabel('name') }}
@endsection


@section('content')
@php
    echo view('posts.templates-view.title',$vars + ['is_resume'=>true]);
    //echo view('posts.templates-view.taxs',$vars);
    echo view('posts.templates-view.content',$vars);
    echo view('posts.templates-view.image',$vars);
    echo view('posts.templates-view.attach',$vars);
    
@endphp


@endsection