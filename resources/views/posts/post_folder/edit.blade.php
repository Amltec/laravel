@extends('templates.admin.index')

@php
/*
    Variáveis esperadas:
        $folder
        $thisClass
*/

use App\Services\PostsService;
use App\Services\TaxService;
use App\Services\TermService;



@endphp

@section('title')
    {!! $folder ? 'Dados d'.$thisClass->getLabel('_p') .' '. $thisClass->getLabel('singular_name') .' <small>#'.$folder->id.'</small>' : 'Nov'.$thisClass->getLabel('_p'); !!}
@endsection


@section('content-view')
@php
    $tab_data = function() use($folder,$thisClass){
        $params=[
                'area_name'=>['type'=>'hidden'],
                'folder_title'=>['label'=>'Título','maxlength'=>500,'require'=>true],
                'folder_resume'=>['label'=>'Resume','type'=>'textarea','maxlength'=>1000,'rows'=>3,'auto_height'=>true],
                'folder_version'=>['label'=>'Versão','maxlength'=>10,'placeholder'=>'1.0'],
                'folder_status'=>['label'=>'Status','type'=>'select','list'=>[''=>'','a'=>'Normal','c'=>'Cancelado'],'require'=>true,'value'=>$folder?null:'a'],
                'folder_name'=>['label'=>'Slug','maxlength'=>50,'require'=>true],
                //'taxs'=>['label'=>'Marcadores','type'=>'html','html'=>$taxs_fnc],
            ];
        if(!$folder)unset($params['folder_name']);
        
        echo view('templates.ui.auto_fields',[
            'layout_type'=>'horizontal',
            'form'=>[
                'url_action'=> $folder ? route($thisClass->prefix.'.app.post',[$thisClass->post_type,'update',$folder->id]) : route($thisClass->prefix.'.app.store' ,$thisClass->post_type),
                'data_opt'=>[
                    'focus'=>true,
                    'onSuccess'=>$folder ? null : "@function(r){if(r.success)window.location=String('". route($thisClass->prefix.'.app.edit',[$thisClass->post_type,':id']) ."').replace(':id',r.id); }",
                ],
                'bt_save'=>$folder ? 'Atualizar' : 'Adicionar',
            ],
            'metabox'=>false,
            'autocolumns'=>$params,
            'autodata'=>$folder,
        ]);
    };

    if($folder){
            $tab_taxs = function() use($folder,$thisClass){
                if(!$folder)return '';
                $terms = TermService::find(['area_name'=>'post_folder','area_id'=>$folder->id]);
                if($terms){
                    foreach($terms as $term){
                            //echo '<h4>'. $term->term_title .'</h4>';
                            echo \App::call('\App\Http\Controllers\TaxsController@createlList',[
                                'term'=>$term,
                                'auto_list_opt'=>['metabox'=>false],
                            ]);
                    }
                }else{
                    echo view('templates.components.button',[
                        'title'=>'Criar marcadores',
                        'post'=>[
                            'url'=>route($thisClass->prefix.'.app.post',[$thisClass->post_type,'add_term']),
                            'data'=>['id'=>$folder->id],
                            'cb'=>'@function(r){if(r.success)window.location.reload();}',
                        ]
                    ]);
                }

            };


            /*$posts_taxs = function() use($folder,$thisClass){
                echo '***';
            };*/

            $urls = $thisClass->getPostUrls($folder);

            echo view('templates.ui.tab',[
                'data'=>[
                    'data'=>['title'=>'Dados','content'=>$tab_data],
                    'taxs'=>['title'=>'Caregorias','content'=>$tab_taxs],
                    //'posts'=>['title'=>'Publicações','content'=>$posts_taxs]
                    'posts'=>['title'=>'Publicações','attr'=>'onclick="window.open(\''. $urls['list'] .'\');return false;"','content'=>false]
                ],
                'tab_active_js'=>true,
            ]);
            
    }else{
        echo view('templates.ui.tab',[
            'data'=>[
                'data'=>['title'=>'Dados','content'=>$tab_data],
            ]
        ]);
    }
    
@endphp


@endsection