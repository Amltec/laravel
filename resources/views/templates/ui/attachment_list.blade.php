@php

if(empty($area_name) || empty($area_id))exit('templates.ui.attachment_list: $area_name|id são requeridos');


$params=[
    'files'=>$files??null,
    'controller'=>$controller ?? 'files',
    'files_opt'=>[
        'uploadComplete'=>'reload',         //reload page
        'fileszone'=>['maximize'=>'.j_files_list_zone'],
        'bt_folder'=>false,
        'bt_access'=>false,
        'mode_view'=>false,
        
        'columns_show'=>'view,file_title,file_name,created_at,file_size,status',
        //'list_compact'=>true,
        
        'metabox'=>[
            'class'=>'j_files_list_zone',        //classe identificadora da zona de upload
            'title'=>'Anexos',
        ],
        
        'upload_opt'=>[
            'folder'=>'attachments',
        ],
        
        'edit_data'=>true,
        
    ],
    'auto_list'=>[
        'options'=>[
            'list_remove'=>false,
            'regs'=>false,
            'search'=>false,
            'allow_trash'=>false,
        ],
        //exemplo de customização de rota da lista (opcional)
        'routes'=>[
            'click'=>function($reg){
                $u='';
                if(method_exists($reg,'getUrl')){
                    $u=$reg->getUrl();
                }elseif(property_exists($reg,'getUrl')){
                    $u=$reg->getUrl;
                }
                return $u ? [$u, 'target'=>'_blank'] : '';
            },
            
            //para excluir um registro
            'remove'=>route('super-admin.file.remove','files'),                   //remove o arquivo
        ],
    ],
    
    'area_name'=>$area_name,
    'area_id'=>$area_id
];




if(!empty($routes))$params['auto_list']['routes']=array_merge($params['auto_list']['routes'],$routes);

//obs: abaixo o comando 'array_replace_recursive' é o mais indicado pois o 'array_merge_recursive', acaba duplicando propriedades com valores diferentes
if(!empty($files_opt))$params['files_opt']=array_replace_recursive($params['files_opt'],$files_opt); 
if(!empty($auto_list))$params['auto_list']=array_replace_recursive($params['auto_list'],$auto_list); 


echo view('templates.ui.files_list',$params);


@endphp