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
*/
use App\Services\PostsService;


$labels = $config['labels'];
$configView = $config['view'];

$vars = get_defined_vars();
@endphp


@section('title_bar')
{{$post->post_title .' | '. $thisClass->getLabel('name') }}
@endsection


@section('content-view')
@php
    echo '<div class="viewer ui-shadow">';
        echo view('posts.templates-view.title',$vars + ['is_resume'=>true]);
        
        echo '<hr>';
        
        echo view('templates.ui.auto_fields',[
            'layout_type'=>'row',
            'autocolumns'=>[
                'post_pass'=>['type'=>'password','label'=>'Digite a senha para visualizar'],
            ],
            'form'=>[
                'url_action'=> route($prefix.'.app.post',[$post_type,'post_login']),
                'data_opt'=>[
                    'dataFields'=>['id'=>$post->id],
                    'focus'=>true,
                    'fields_log'=>false,
                    'onSuccess'=>'@function(r){if(r.success)window.location.reload();}'
                ],
                'bt_save'=>'Acessar',
                'alert'=>false,
            ],
        ]);
        
    echo '</div>';
    
@endphp

<style>
    .alert-success{display:none !important;}
</style>

@endsection