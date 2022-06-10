@extends('templates.admin.index')

@section('title')
Tabela Padrão - Posts
@endsection


@section('content-view')
@php
    //dados de exemplo para preenchimento do formulário
    $dataform = (object)[
        'id'=>1,
        'post_title'=>'Título do post',
        'post_excerpt'=>'Resumo do post',
        'post_content'=>'Contéudo <strong>html</strong> do post',
        'post_name'=>'titulo-do-post',
        'created_at'=>'2019-04-12 15:14:18',
        'post_status'=>'c',
    ];
    //adiciona o prefixo na var $dataform
    $dataform = \App\Services\TablePostsService::adjusteData($dataform,'noticias*');
    //dd($dataform);

@endphp

    <p>Cadastro de Telefones (utilizando o autofied) - Valor 'area_name'='noticias'.</p>
    @include('templates.ui.auto_fields',array_merge(
        \App\Services\TablePostsService::configAutoFields([
            'prefix'=>'noticias*',
            'area_name'=>'noticias',
            'area_id'=>'1',
            'title'=>'Cadastro Posts',
            //'autodata'=>$dataform,
            
            
            //estes campos são de exemplos apenas para executar o teste pelo ExempleControler@testSaveAuto
            'add_columns'=>[
                '_tmp_controller_test'=>['type'=>'hidden','value'=>'TablePostsService@save'],
                '_tmp_table_id'=>['type'=>'hidden','value'=>'2'],
                '_tmp_table_param'=>['type'=>'hidden','value'=>json_encode([
                        'prefix'=>'noticias*',
                        //'required'=>['contact_cpf_cnpj','contact_sexo'],
                        //'duplicate'=>false
                    ])
                ]
            ]
        ],'all'),
        [
            'form'=>[
                'url_action'=>route('admin.app.post',['example','testSaveAuto']),
                'bt_save'=>true,
            ],
            'autodata'=>$dataform
        ]
    ))
    
@endsection