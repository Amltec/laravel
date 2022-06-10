@extends('templates.admin.index')


@section('title')
Gerenciador de Arquivos - Dados a partir de um Array
@endsection


@section('content-view')
Os dados vem de um array com a função \App\Utilities\CollectionUtility. <br>
Obs1: todo o array precisa conter os mesmos campos da tabela 'files'.
Obs2: neste caso os métodos da model 'files' não existem e precisam ser criados os respectivos parâmetros no array: <br>
- <strong>getPath</strong> - caminho do arquivo <br>
- <strong>getUrl</strong> - url da imagem <br>
- <strong>getUrlThumbnail</strong> - url imagem em mininarura <br>
- <strong>getIcon</strong> - url imagem do ícone (opcional) <br>
<br>


@php
    $data_files=[
        [   
            "id" => 123456,
            "file_title" => "download",
            "file_name" => "download",
            "file_size" => "6168",
            "file_mimetype" => "image/jpeg",
            "file_path" => "app\\attachments\\2021\\08",
            "file_ext" => "jpg",
            "file_thumbnails" => "small-150x99",
            "private" => 1,
            "folder" => "attachments",
            "created_at" => "2021-08-16T11:35:04.000000Z",
            "updated_at" => "2021-08-16T11:35:04.000000Z",
            "deleted_at" => null,
            //novos campos
            "getPath" => "C:\Users\Aurl\Desktop\arquivos-tmp\img001.jpg",
            "getUrl" => "https://www.aurlweb.com.br/wp-content/uploads/theme_aurl/logo.png",
            "getUrlThumbnail" => "https://www.aurlweb.com.br/wp-content/uploads/theme_aurl/logo.png",
            "getIcon" => \App\Services\FilesService::getIconExt('jpg')['class'],
            "relation" =>[
                "status" => "a",
                "status_label" => "Visível"
            ],
        ]
    ];
    $data_files = (new \App\Utilities\CollectionUtility($data_files))->paginate(15);
    //dd($data_files);

@endphp


@include('templates.ui.files_list',[
    'controller'=>'files',
    'files'=>[
        'opt'=>[],
        'files'=>$data_files,
     ],
    'files_filter'=>[
        'regs'=>_GETNumber('regs')??5,
    ],
    'files_opt'=>[
        //'uploadComplete'=>'@function(v){console.log("***",v);}',    //custom js function
        //'uploadComplete'=>'reload',                                 //reload na página
        //'uploadSuccess'=>'@function(v){console.log("***",v);}',     //custom js function
        'uploadSuccess'=>'route_load',                                //dispara a rota 'load' ajax
        'fileszone'=>['maximize'=>'.j_files_list_zone'],
        
        //'class'=>'relative',
        //'list_class'=>'',
        
        'metabox'=>[
            'class'=>'j_files_list_zone'        //classe identificadora da zona de upload
        ],
        
        //informações adicionais para o upload
        'upload_mode'=>'filemanager',
        'upload_opt'=>[
            //adiciona informações de relacionamento com outras tabelas...
            'area_name'=>'test',
            'area_id'=>'111'
        ],
        
        'edit_data'=>[
            'area_name'=>'test','area_id'=>222,   //neste exemplo não é necessário, pois estes mesmos parâmetros foram informados no final desta configuração
            //'status'=>false,
        ],
    ],
    'auto_list'=>[
        
        //exemplo de customização de rota da lista (opcional)
        'routes'=>[
            'click'=>function($reg){
                return [$reg->getUrl, 'target'=>'_blank'];
            },
            //para excluir um registro
            'remove'=>route('super-admin.file.remove','files'),
            
            //rota para edição
            'edit'=>function($reg){ return route('super-admin.app.get',['example','edit-file',$reg->id]); },
            
        ],
    ],
    
    'area_name'=>'test', 'area_id'=>222, //area_name e area_id padrões
])



@endsection

