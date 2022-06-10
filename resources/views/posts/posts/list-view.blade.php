@extends('templates.admin.index')

@php
/*
    Lista de posts apenas para visualização e acesso ao conteúdo
    Não são exibidos as opções de edição na lista
    
    Variáveis esperadas:
        post_type
        postFolder
        posts
        thisClass
        config
        prefix
        title_folder
        termsList
        taxs_select
*/


$configList = $config['view_list'];
$vars = get_defined_vars();

@endphp

@push('head')
    <link rel="stylesheet" href="{{asset('css/post-view.css?ver=1.0')}}">
@endpush


@section('content-view')
@php
    echo '<div class="post-container"><div class="row">';
            //menu
            if($configList['tax_menu'])echo view('posts.posts.templates-view.menuleft',$vars);
            
            //list
            echo '<div class="postview-content'. ($configList['tax_menu']?' hash-menu col-xs-12 col-sm-10':' col-sm-12') .'">';
                if($posts->count()>0){
                    foreach($posts as $post){
                        echo '
                            <div class="list-viewer ui-shadow cursor-pointer effect-left-move" onclick="goToUrl(\''. $post->getUrl() .'\')">
                                <h3 class="no-margin">'. $post->post_title .'</h3>
                                <p>'. $post->post_resume .'</p>
                                <a href="#">Saiba mais</a>
                            </div>
                        ';
                    }
                }else{
                    echo '<br><p>Nenhum registro encontrado</p>';
                }
                
                echo '<div class="pull-right">'. $posts->appends(request()->except('page')) .'</div>';
                
            echo '</div>';
            
        
    echo '</div></div>';
@endphp


@endsection