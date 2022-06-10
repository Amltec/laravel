@php
use App\Utilities\FormatUtility;
use App\Services\FilesService;
use Illuminate\Support\Arr;

if(!isset($controller))dd('files_list.blade - $controller não informado');
if(is_string($controller)){
    $thisClass=(new \App\Http\Controllers\FilesController)->callController($controller);

}else{//object class
    $thisClass=$controller;
    $controller=$thisClass->getConfig('basename');
}


$area_name = $area_name??null;
$area_id = $area_id??null;

$is_reload_page=false;

$files_opt=array_merge([
    'area_name'=>null,
    'area_id'=>null,
    
    'toolbar'=>true,
    'bt_search'=>true,
    'bt_upload'=>true,
    'bt_folder'=>true,
    'bt_access'=>true,
    'bt_remove'=>true,
    'mode_view'=>true,
    'modeview_img'=>false,
    'metabox'=>true,
    'show_view'=>true,
    'list_compact'=>false,
    'columns_show'=>'',
    'title'=>'Gerenciador de Arquivos',
    'accept'=>null,
    'fileszone'=>false,
    'uploadComplete'=>null,
    'uploadSuccess'=>null,
    'class'=>'',
    'list_class'=>'',
    'mode_select'=>false,
    'file_view'=>false,
    'folders_list'=>false,
    'folders_show'=>true,
    'restrict_user'=>false,
    'edit_data'=>false,
],$files_opt??[]);
//dump($files_opt);

$prefix = Config::adminPrefix();



//ajusta $edit_data
if($files_opt['edit_data']){
    if($files_opt['edit_data']===true)$files_opt['edit_data']=['title'=>true,'status'=>true];
    
    if($area_name && $area_id){
        $files_opt['edit_data']['area_name']=$area_name;
        $files_opt['edit_data']['area_id']=$area_id;
    }
}



//ajustes adicionais por possíveis parâmetros do querystring
    foreach(['folders_list','folders_show','restrict_user'] as $f){
        $n=Request::get($f);
        if($n)$files_opt[$f]=FormatUtility::cBool($n);
    }
    //dump($files_opt);


//inicia a var
    $files_filter=$files_filter??[];

//verifica se a pasta informada está dentro das pastas permitidas
    $folders_list = $thisClass->getConfig('folders_list');
    $folder_default = $thisClass->getConfig('folder_default');
    $folder_init = $files_filter['folder']??$folder_default;
    foreach($folders_list as $f=>$v){
        if(substr($folder_init,0,strlen($f))==$f){//está dentro do diretório, ex: 'a/b/ existe em 'a/b/c'
            $folder_init=$f;break;
        }
    }
    if(!$folder_init)$folder_init=array_keys($folders_list)[0];
    if($files_opt['folders_list'])$folders_list = array_merge($folders_list,$files_opt['folders_list']);



//ajusta os parâmetros da lista
if(!isset($auto_list)){
    $auto_list=[];
}else{
    unset($auto_list['field_click']);
    if(isset($auto_list['routes']['load'])){
        $auto_list['routes']['load'] = \App\Utilities\HtmlUtility::addQs($auto_list['routes']['load'],['area_name'=>$area_name,'area_id'=>$area_id]);
    }
}
$auto_list['field_click']=false;//para desativar o click automático na lista


//força a visualização como imagem
    if(_GET('modeview')=='s')$files_opt['modeview_img']=true;


//modo de visualização compacta
    if($files_opt['list_compact']){
        if(!$files_opt['columns_show'])$files_opt['columns_show']='file_title,created_at,file_size,folder,status';
    }else{
        $files_opt['list_class'].=' table-large';
    }
    if(!$files_opt['columns_show'])$files_opt['columns_show']='view,file_title,file_name,created_at,file_size,folder,status'; //valor padrão


//captura os dados informados pelo parâmetro / querystring
    $n=_GETNumber('regs');if(empty($n) && $files_opt['modeview_img'])$n=30;
    $files_filter=array_merge([
        'regs'=> $n,
        'search'=> Request::get('q'),
        'folder'=> Request::get('folder'),
        'private'=> in_array(Request::get('private'),['s','true','1']),
        'is_trash'=> Request::get('is_trash')=='s',
        'taxs_id'=> Request::get('taxs_id'),
        'area_name'=> Request::get('area_name'),
        'area_id'=> Request::get('area_id'),
        'area_status'=> Request::get('area_status'),
        'filetype'=> Request::get('filetype'),
        'metadata'=> Request::get('metadata'),
        'meta_name'=> Request::get('meta_name'),
        'meta_value'=> Request::get('meta_value'),
        'taxs'=> Request::get('taxs'),
        'id'=> Request::get('filter_id')??Request::get('id'),
    ],$files_filter);
    if(empty($files_filter['folder']))$files_filter['folder']=$folder_init;
    if($files_opt['restrict_user'])$files_filter['user_id']=true;
    
    
    if($area_name && $area_id){
        $files_filter['area_name']=$area_name;
        $files_filter['area_id']=$area_id;
        
        $files_opt['area_name']=$area_name;
        $files_opt['area_id']=$area_id;
        
        if(!isset($files_opt['upload_opt']))$files_opt['upload_opt']=[];
        $files_opt['upload_opt']['area_name']=$area_name;
        $files_opt['upload_opt']['area_id']=$area_id;
    }
    
    if(isset($files)){
        //termina de atualizar automaticamente o $file_filter a partir dos dados em $files já informados
        $files_filter=array_merge($files['opt'],$files_filter);

    }else{
        $files = FilesService::getList($files_filter,$thisClass);
    }
    //dump($files_filter);
    //dd($files,$files_filter);




//atribui a classe na tabela que converte o visual para visualização como imagem
if($files_opt['modeview_img'])$files_opt['list_class'].=' table-mode-view';


//Formata os parâmetros para evitar

$list_id = 'filemanager_'.uniqid();
$params=[
    'list_id'=>$list_id,
    'class'=>$files_opt['class'],
    'list_class'=>'filemanager_wrap '.$files_opt['list_class'],
    'data'=>$files['files'],

    /*'options'=>[
        'checkbox'=>true,
        'select_type'=>2,
        'header'=>false,
        'confirm_remove'=>true,
        'toolbar'=>true,
    ],*/
    'routes'=>[
        'remove'=>route($prefix.'.file.post',$controller),
    ],
    'metabox'=>callstr(function() use ($files_opt,$files_filter,$thisClass,$folder_init){
                    if($files_opt['metabox']){
                        return array_merge(
                                [   'title'=>
                                        $files_opt['title'] .
                                        ' - <span style="font-size:0.8em;" title="Pasta '. $files_filter['folder'] .'">'.
                                        ($files_filter['is_trash']?' Lixeira: ':'').
                                        ($files_filter['private']?'Privada':'Pública'). ' / '.
                                        Arr::get($thisClass->getConfig('folders_list'),$folder_init).
                                        '</span>',
                                ],
                                is_array($files_opt['metabox'])?$files_opt['metabox']:[]
                                );
                    }else{
                        return false;
                    }
                },true),
    'options'=>[
        'toolbar'=>$files_opt['toolbar'],
        'checkbox'=>$files_opt['bt_remove'],
        'remove'=>$files_opt['bt_remove'],
        'search'=>$files_opt['bt_search'],
        'select_type'=>2,
        'header'=>false,
        'confirm_remove'=>true,
    ]
];
//dump($params);

if($files_opt['mode_select']??false){
    $params['footer_cb']=function($optx){
        echo '<div style="margin-top:15px;">'.
            '<div class="pull-left">', $optx['pagin'], '</div>'.
            '<div class="pull-right"><button class="btn btn-primary j-btn-select">Selecionar</button></div>'.
        '</div>';
    };
}

if($files_opt['modeview_img']){//modo de visualização imagens
    $params['columns_show']='view,file_title';
    $params['columns']=[
        'view'=>[
            'Imagem',
            'value'=>function($val,$reg=null) use($auto_list,$files_opt){
                if(method_exists($reg,'getUrlThumbnail')){
                    $img = $reg->getUrlThumbnail('small');//0 img, 1 width, 2 height
                }elseif(property_exists($reg,'getUrlThumbnail')){//$img - string url
                    $img = [$reg->getUrlThumbnail,0,0];
                }else{
                    $img = ['',0,0];
                }
                $link = awAutoListUI_route($reg,Arr::get($auto_list,'routes'),'click','href');
                $dbllink = awAutoListUI_route($reg,Arr::get($auto_list,'routes'),'dblclick','dbl-href');

                return '<a '. trim($link.' '.$dbllink) .' class="row-lnk-click row-item-img" title="'.
                            'Nome: '. $reg->file_name.'.'.$reg->file_ext .chr(10).
                            'Tamanho: '. FormatUtility::bytesFormat($reg->file_size) .chr(10).
                            'Data: '. FormatUtility::dateFormat($reg->file_title,'d/m/Y H:i') .chr(10).
                            ''. ($reg->private?'Pasta privada':'Pasta') .': '. $reg->folder .chr(10).
                            'ID: '. $reg->id .chr(10).
                        '"><img src="'.$img[0].'" data-size="'.$img[1].'x'.$img[2].'"></a>';
            }
        ],
        'file_title'=>[
            'Título',
            'value'=>function($val,$reg=null) use($auto_list,$files_opt,$prefix,$controller){
                if(method_exists($reg,'getPath')){
                    $exists  = $reg?file_exists($reg->getPath()):true;
                }else{
                    $exists = true;
                }
                $link = awAutoListUI_route($reg,Arr::get($auto_list,'routes'),'click','href');
                $dbllink = awAutoListUI_route($reg,Arr::get($auto_list,'routes'),'dblclick','dbl-href');
                
                $link_edit = awAutoListUI_getRoute($reg,Arr::get($auto_list, 'routes'), 'edit');
                
                $str_title= '<a '. trim($link.' '.$dbllink) .' class="row-lnk-click row-item-title '. ($exists?'':'text-red') .'" title="'.
                                    'Nome: '. $reg->file_name.'.'.$reg->file_ext .chr(10).
                                    'Tamanho: '. FormatUtility::bytesFormat($reg->file_size) .chr(10).
                                    'Data: '. FormatUtility::dateFormat($reg->file_title,'d/m/Y H:i') .chr(10).
                                    ''. ($reg->private?'Pasta privada':'Pasta') .': '. $reg->folder .chr(10).
                                    'ID: '. $reg->id .chr(10).
                               '">'. $reg->file_title .'</a>';
                
                if($files_opt['edit_data']){
                    $link_edit = awAutoListUI_getRoute($reg,Arr::get($auto_list,'routes'),'edit');
                    if(!$link_edit)$link_edit = route($prefix.'.app.get',[$controller,'edit-file',$reg->id]);
                    $link_view = awAutoListUI_getRoute($reg,Arr::get($auto_list,'routes'), ['view','click']);
                    
                    $str_title.= '<div class="row-item-links-actions">'.
                                    '<span class="margin-r-10"></span>'.
                                    '<a href="'.$link_edit.'" onclick="awFilesListEdit(this);return false;" class="margin-r-10 row-item-link-edit strong">Editar</a>'.
                                    ($link_view  ? '<a href="'.$link_view.'" target="_blank" class="margin-r-10 row-item-link-view strong">Visualizar</a>' : '').
                                 '</div>';
                }
                return $str_title;
            },
        ]
    ];

}else{//modo de visualização lista
    $params['columns_show']=$files_opt['columns_show'];
    $params['columns']=[
        'id'=>'ID',
        'file_title'=>[
            'Título',
            'value'=>function($val,$reg=null) use($files_opt,$auto_list,$controller,$prefix){
                if($reg){
                    if(method_exists($reg,'getPath')){
                        $exists  = file_exists($reg->getPath());
                        $icon_class = $reg->getIcon()['class'];
                    }elseif(property_exists($reg,'getIcon')){
                        $exists = true;
                        $icon_class = $reg->getIcon;
                    }else{
                        $exists = true;
                        $icon_class = '';
                    }
                    $val    = $reg->file_title;
                    $link   = awAutoListUI_route($reg,Arr::get($auto_list,'routes'),'click','href');
                    $dbllink = awAutoListUI_route($reg,Arr::get($auto_list,'routes'),'dblclick','dbl-href');
                    
                    if($files_opt['show_view'] && strpos($files_opt['columns_show'],'view')!==false){
                        if(method_exists($reg,'getUrlThumbnail')){
                            $img = $reg->getUrlThumbnail('small');//0 img, 1 width, 2 height
                            $view = '<a '. trim($link.' '.$dbllink) .' class="ui-img-thumbnail row-item-img ui-img-box row-lnk-click" style="float:left;margin-right:10px;"><img src="'. $img[0] .'" data-size="'.$img[1].'x'.$img[2].'"></a>';
                        }else{
                            $view = '<a '. trim($link.' '.$dbllink) .' class="ui-img-thumbnail row-item-img row-lnk-click nostrong text-muted '. $icon_class .'" style="opacity:0.5;font-size:1.7em;position:relative;top:5px;float:left;margin-right:10px;"></a>';
                        }

                    }else if($files_opt['show_view'] && strpos($files_opt['columns_show'],'icon')!==false){
                        $view = '<a '. trim($link.' '.$dbllink) .' class="ui-img-thumbnail row-lnk-click nostrong text-muted '. $icon_class .'" style="opacity:0.5;font-size:1.7em;position:relative;top:5px;float:left;margin-right:10px;"></a>';

                    }else{
                        $view = '';
                    }
                    
                    $bts_edit_left='';
                    $bts_edit_bottom='';
                    if($files_opt['edit_data']){
                        $link_edit = awAutoListUI_getRoute($reg,Arr::get($auto_list,'routes'),'edit');
                        if(!$link_edit)$link_edit = route($prefix.'.app.get',[$controller,'edit-file',$reg->id]);
                        $link_view = awAutoListUI_getRoute($reg,Arr::get($auto_list,'routes'), ['view','click']);
                        
                        if($view==''){
                            $bts_edit_left=
                                '<a href="'. $link_edit .'" onclick="awFilesListEdit(this);return false;" class="margin-r-10 row-item-link-edit fa fa-pencil" title="Editar"></a>'.
                                //($link_view  ? '<a href="'.$link_view.'" target="_blank" class="margin-r-10 row-item-link-view fa fa-file-o" title="Visualizar"></a>' : '').
                                '';
                        }else{
                            $bts_edit_bottom=
                                '<div class="row-item-links-actions" style="'. ($view?'margin-left:58px;':'') .'">'.
                                    '<a href="'. $link_edit .'" onclick="awFilesListEdit(this);return false;" class="margin-r-10 row-item-link-edit strong"><i class="fa fa-pencil"></i> Editar</a>'.
                                    //($link_view  ? '<a href="'.$link_view.'" target="_blank" class="margin-r-10 row-item-link-view strong">Visualizar</a>' : '').
                                '</div>';
                        }
                    }
                    
                    if(strpos($files_opt['columns_show'],'file_name')===false){
                        $ext =' <span class="text-color-disable nostrong text-uppercase small">'.$reg->file_ext.'</span>';
                    }else{
                        $ext = '';
                    }
                    $text   = '';
                    
                    $text  .= '<span title="'.
                                        'Nome: '. $reg->file_name.'.'.$reg->file_ext .chr(10).
                                        'Tamanho: '. FormatUtility::bytesFormat($reg ? $reg->file_size : $val) .chr(10).
                                        'Data: '. FormatUtility::dateFormat($val,'d/m/Y H:i') .chr(10).
                                        ''. ($reg->private?'Pasta privada':'Pasta') .': '. $reg->folder .chr(10).
                                        'ID: '. $reg->id .chr(10).
                                        ($reg->private?'Privado':'Público').': '. str_replace('\\','/',$reg->file_path) .chr(10).
                                   '" class="row-item-title '. ($exists?'':'text-red') .'">'.$reg->file_title.'</span>' . $ext;
                    
                    $text = $bts_edit_left . $view. '<a '. trim($link.' '.$dbllink) .' class="text-color-default strong row-lnk-click" title="Arquivo: '.$reg->file_name.'.'.$reg->file_ext.'">'.$text.'</a>';

                    if(strpos($files_opt['columns_show'],'file_name')!==false){
                        $text.= '<br><span class="small inlineblock text-color-disable row-item-filename">'. $reg->file_name.'.'.$reg->file_ext .'</span>';
                    }else{
                        if($view!='')$text.= '<br><br>';
                    }
                    
                    $text.=$bts_edit_bottom;

                    return $text;

                }else{
                    return $val;    //or echo $val;
                };
            },
        ],
        'created_at'=>[
                'Data',
                'value'=>function($val){ return FormatUtility::dateFormat($val,'d/m/Y H:i'); },
        ],
        'file_size'=>[
                'Tamanho',
                'value'=>function($val,$reg=null){ return FormatUtility::bytesFormat($reg ? $reg->file_size : $val); },
            ],
        'folder'=>[
            'Pasta',
            'value'=>function($val,$reg=null){
                $is_private = $reg && $reg->private;
                return '<span class="fa fa-'. ($is_private?'lock':'folder') .' margin-r-5" title="'. ($is_private?'Pasta privada':'Pasta') .': '.$val.'" style="'. ($is_private?'':'opacity:0.2;') .'"></span> '.
                    '<span style="opacity:0.7;font-size:0.9em;">'.$val.'</span>';
            }
        ],
    ];
    
    
    //existe filtro por area_name|area_id e portanto existe o campo status para exibir
    if($files_filter['area_name'] && $files_filter['area_id']){
        $params['columns']['status']=[
            'Status',
            'value'=>function($v,$reg) use($files_filter){
                if(method_exists($reg,'relationByArea')){
                    $m=$reg->relationByArea($files_filter['area_name'],$files_filter['area_id']);
                    return '<span title="Arquivo '. $m->status_label .'" style="font-size:0.8em;" class="fa fa-circle '.  ($m->status=='c'?'text-red': ($m->status=='0'?'text-gray':'text-blue') )  .'"></span>';
                    
                }elseif(property_exists($reg,'relation')){
                    $m=$reg->relation;
                    $s=$m['status']??'a';
                    $l=$m['status_label']??'Visível';
                    return '<span title="Arquivo '. $l .'" style="font-size:0.8em;" class="fa fa-circle '.  ($s=='c'?'text-red': ($s=='0'?'text-gray':'text-blue') )  .'"></span>';
                }
            }
        ];
    }
}

if($files_opt['bt_access']){
    if($thisClass->getConfig('access')=='all'){
            $params['toolbar_buttons']['bt_access']=['icon'=>'fa-user','title'=>($files_filter['private']?'Privado':'Público'), 'color'=>'link',
                    'sub'=>function() use ($files_filter,$auto_list){
                        $r=[];
                        foreach(['public'=>'Público','private'=>'Privado'] as $folder_name=>$folder_title){
                            $folder_name=str_replace('\\','\\\\',$folder_name);
                            if(!empty(Arr::get($auto_list,'routes.load'))){//esta lista é por ajax
                                $lnk = '#';
                                $attr= 'onclick="awFilesListBtnFilterChange(this,{type:\'access\',value:\''.$folder_name.'\',label:\''.$folder_title.'\'});return false;"';
                            }else{
                                $lnk = \Request::fullUrlWithQuery(['private'=>$folder_name=='private','access'=>null]);
                                $attr= '';
                            }
                            $r[$folder_name]=['title'=>$folder_title,'icon'=>'fa-'.($folder_name=='private'?'lock':'unlock'), 'link'=>$lnk, 'class'=>(($files_filter['private'] && $folder_name=='private') || (!$files_filter['private'] && $folder_name=='public')?'dropdown-selected':''), 'attr'=>$attr ];
                        }
                        return $r;
                    },
                ];
    }
}


if($files_opt['folders_show'] && $files_opt['bt_folder'] && count($folders_list)>1){
    $params['toolbar_buttons']['bt_folder']=['icon'=>'fa-folder','title'=>Arr::get($folders_list,$files_filter['folder'],false), 'color'=>'link',
            'sub'=>function() use ($files_filter,$auto_list,$folders_list){
                $r=[];
                foreach($folders_list as $folder_name=>$folder_title){
                    $folder_name=str_replace('\\','\\\\',$folder_name);
                    if(!empty(Arr::get($auto_list,'routes.load'))){//esta lista é por ajax
                        $lnk = '';
                        //$attr= 'onclick="$(this).closest(\'.ui-listdata\').trigger(\'load\',{folder:\''.$folder_name.'\'});$(this).closest(\'.btn-group\').find(\'>button>.btn-title\').html(\''.$folder_title.'\');return false;"';
                        $attr= 'onclick="awFilesListBtnFilterChange(this,{type:\'folder\',value:\''.$folder_name.'\',label:\''.$folder_title.'\'});return false;"';
                    }else{
                        $lnk = \Request::fullUrlWithQuery(['folder'=>$folder_name]);
                        $attr= '';
                    }
                    $r[$folder_name]=['title'=>$folder_title,'icon'=>'fa-folder','link'=>$lnk, 'class'=>($files_filter['folder']==$folder_name?'dropdown-selected':''), 'attr'=>$attr ];
                }
                return $r;
            },
        ];


    /*
    $params['toolbar_buttons']['bt_folder']=['icon'=>'fa-folder','title'=>Arr::get($folders_list,$files_filter['folder'],false), 'color'=>'link',
        'sub'=>function() use ($files_filter,$auto_list,$folders_list){
                $r=[];
                return $r;
            },
    ];*/
}



//*** parâmetro row_opt ***
    $old_row_opt_class=$auto_list['row_opt']['class']??null;//captura o valor atual para não sobrescrever abaixo
    $auto_list['row_opt']['class']=function($reg) use($files_opt,$old_row_opt_class){
        $s0 = $old_row_opt_class ? callstr($old_row_opt_class,[$reg], true) : '';
        $s1 = $files_opt['edit_data']?'has-edit ':'';
        if(method_exists($reg,'getPath')){
            $cls = $s0 .' '. $s1 . (file_exists($reg->getPath())?'exists':'not-exists text-red');
        }else{
            $cls = $s0 .' '. $s1 . 'exists';
        }
        
        if($files_opt['area_name'] && $files_opt['area_id']!=''){
            if(method_exists($reg,'relationByArea')){
                $mRel=$reg->relationByArea($files_opt['area_name'],$files_opt['area_id']);
                if($mRel->status=='c')$cls.=' text-canceled';
            }elseif(property_exists($reg,'relation')){
                if(($reg->relation['status']??'')=='c')$cls.=' text-canceled';
            }
        }
        return trim('v-align-m '.$cls);
    };
    
    $old_row_opt_attr=$auto_list['row_opt']['attr']??null;//captura o valor atual para não sobrescrever abaixo
    $auto_list['row_opt']['attr']=function($reg) use($files_opt,$old_row_opt_attr){
        if($old_row_opt_attr)callstr($old_row_opt_class,[$reg]);
        
        if(method_exists($reg,'toArray')){
            $arr = $reg->toArray();
        }else{
            $arr = (array)$reg;
        }
        $attr_data = $reg ? Arr::only($arr,['id','file_name','file_title','file_ext','file_size']) : [];
        return 'data-data=\''. json_encode($attr_data) .'\'';
    };

    
    

//dd($auto_list);


if(!empty($params['toolbar_buttons'])){
    $params['toolbar_buttons'] =  ['<span class="btn no-events">Filtros:<span>'] + $params['toolbar_buttons'];
}else{
    unset($params['toolbar_buttons']);
}

if($files_opt['bt_upload']){

    //retorno do upload
    if($files_opt['uploadComplete']=='reload'){
        $files_opt['uploadComplete']='@function(v){ if(v.status=="R")window.location.reload(); }';
        $is_reload_page=true;
    }
    if($files_opt['uploadSuccess']=='route_load'){
        $files_opt['uploadSuccess']='@function(v){ $("#'.$params['list_id'].'").trigger("load",{id:v.id,pos:"before"}); }';
    }

    if(!$files_filter['is_trash']){
        if(($files_opt['upload_mode']??false)=='filemanager'){//seleção
                $optsFileMg=array_merge([
                    'controller'=>$controller,
                    'multiple'=>true,
                    'private'=>$files_filter['private'],
                    'folder'=>$files_filter['folder'],
                    'filetype'=>$files_opt['filetype']??null,
                    'accept'=>$files_opt['accept'],
                    'thumbnails'=>$files_opt['thumbnails']??null,
                    'allow_trash'=>$auto_list['options']['allow_trash']??null,
                    'restrict_user'=>$files_opt['restrict_user']??false,
                    //'area_name'=>$files_filter['area_name'],
                    //'area_id'=>$files_filter['area_id'],
                    'onSelectFile'=>$files_opt['onSelectFile']??null,    //'@function(opt){ console.log("select file",opt) }',
                ],($files_opt['filemanager_opt']??[]));
                //dd($optsFileMg);
                $params['toolbar_buttons_right']=[
                    'filemanager'=>['icon'=>'fa-mouse-pointer','title'=>'Escolher', 'color'=>'info','onclick'=>'awFilemanager('. json_encode($optsFileMg) .');'],
                ];

        }else{//upload
                //dump($files_opt,_GET('accept'));
                $params['toolbar_buttons_right']=[
                        //'file'=>['icon'=>'fa-upload','title'=>'Upload', 'color'=>'info', 'type'=>'upload', 'multiple'=>true],
                        //botão de upload com formulário
                        view('templates.ui.auto_fields',[
                            'form'=>[
                                'class'=>'inlineblock',
                                'alert'=>false,
                                'url_action'=> Arr::get($auto_list,'routes.upload') ?? route($prefix.'.file.post',$controller),
                                'files'=>true,
                                'data_opt'=>array_merge(
                                    [
                                        'btAutoUpload'=>'[name=file]',
                                        'onSuccess'=>$files_opt['uploadSuccess'],
                                        'onComplete'=>$files_opt['uploadComplete'],
                                        'fileszone'=>($files_filter['is_trash']?false:$files_opt['fileszone']),
                                        'fields_log'=>false,
                                    ],
                                    ($files_opt['form_opt']??[])
                                ),
                            ],
                            'attr'=>'style="padding-right:0;"',//retira o padding a direita
                            'autocolumns'=>[
                                'file'=>['type'=>'upload','value'=>'Upload','class_button'=>'info','icon'=>'fa-upload','multiple'=>true,'accept'=>($files_opt['accept']??_GET('accept'))],
                                //parâmetros de upload
                                'controller'=>['type'=>'hidden','value'=>$controller],
                                'action'=>['type'=>'hidden','value'=>'upload'],
                                'data-opt'=>['type'=>'hidden','class_field'=>'j-field-opt-upload','value'=>json_encode(
                                    array_merge(
                                        [
                                            'private'=>$files_filter['private'],
                                            'folder'=>$files_filter['folder'],
                                            'accept'=>$files_opt['accept']??_GET('accept'),
                                            'thumbnails'=>$files_opt['thumbnails']??_GET('thumbnails'),
                                        ],
                                        Arr::get($files_opt,'upload_opt',[])
                                    )
                                )],
                            ]
                        ])
                ];
        }
    }
}

if($files_opt['mode_view']){
    $params['toolbar_menus']=[
        'mode_view'=>['title'=>'Alterar visualização','icon'=>'fa-image', 'link'=> \Request::fullUrlWithQuery(['modeview'=>($files_opt['modeview_img']?'':'s')]) ],
    ];
    if(Request::ajax()){
        $params['toolbar_menus']['mode_view']['link']='#';
        $params['toolbar_menus']['mode_view']['attr']='onclick="awFilesListChangeModeView(this);"';
    }
}

$n=($params['list_class']??'').' '.($auto_list['list_class']??'');//soma as classes para não substituir no array_merge abaixo
if(!empty($auto_list))$params=array_replace_recursive($params,$auto_list); //obs: este comando 'array_replace_recursive' é o mais indicado pois o 'array_merge_recursive', acaba duplicando propriedades com valores diferentes
$params['list_class']=trim($n);
//dd($params,$auto_list);


//parâmetros para a função js filesListInit()
$params['list_attr']='ui-fileslist="on" data-fileslist-opt=\''. json_encode([
    'bt_upload'=>$files_opt['bt_upload'],
    'bt_folder'=>$files_opt['bt_folder'],
    'bt_access'=>$files_opt['bt_access'],
]) .'\'';

$params['options']['post_data']=['area_name'=>$area_name,'area_id'=>$area_id];  //dados adicionais para cada post
echo view('templates.ui.auto_list',$params)->render();//obs: o render() faz mostrar o erro dentro da view caso ocorra

if(false){
    echo '
    <div class="filemanager-fileview">
        ..........
    </div>
    <style>
    .filemanager-fileview{position:absolute;max-width:400px;width:100%;height:calc(100% - 110px);top:0;right:0;z-index:99;outline:1px solid red}
    </style>
    ';
}


//controle para não repetir a função js
static $js_write_01=false;


if($js_write_01===false):
@endphp

<style>
.filemanager_wrap .col-status{width:30px;padding-left:10px;padding-right:10px;}    
.filemanager_wrap .text-canceled,.filemanager_wrap .text-canceled .row-item-title{color:#cc0000;}
</style>
<script>
//Função para os botões de filtro: access e folder
function awFilesListBtnFilterChange(thisBt,opt){//opt:{type:(access|folder),value,label}
    var o=$(thisBt);
    o.closest('ul').find('>li>a').removeClass('dropdown-selected');
    o.addClass('dropdown-selected');
    o.closest('.ui-listdata').trigger('load',{[opt.type]:opt.value});
    o.closest('.btn-group').find('>button>.btn-title').html(opt.label);
    var base=o.closest('[ui-fileslist]');
    var opt0=$.parseJSON(base.attr('data-fileslist-opt'));
    if(opt0.bt_upload){
        if(opt0.bt_folder || opt0.bt_access){
            var oInpDataOpt=base.find('.j-field-opt-upload:eq(0)');
            var val=oInpDataOpt.val();val=$.trim(val)!=''?$.parseJSON(val):{};
            var newopt={};
            if(opt0.bt_folder && opt.type=='folder')newopt.folder=opt.value;
            if(opt0.bt_access && opt.type=='access')newopt.private=opt.value=='private';
            val=$.extend(true,val,newopt);//mescla os valores da configuração atual para o novo do upload
            oInpDataOpt.val(JSON.stringify(val));
        };
    };
};

//Função que altera o modeview dinamicamente
function awFilesListChangeModeView(thisObj){
    var o=$(thisObj);
    var base=o.closest('[ui-fileslist]');
    var table=base.find('table:eq(0)');
    var modeview='';
    if(table.hasClass('table-mode-view')){
        table.removeClass('table-mode-view');
        modeview='n';
    }else{
        table.addClass('table-mode-view');
        modeview='s';
    };
    base.trigger('load',{modeview:modeview});//adiciona 'modeview' na querystring do ajax
};

//Função que aplica a selação de imagens pelo botão de 'Selecionar'
function awFilesListOnSelect(list_id){
    var oList=$(list_id);

    //Aplica a selação de imagens pelo botão de 'Selecionar'
    oList.on('click','.j-btn-select',function(){
        var ids = oList.triggerHandler('get_select');
        if(ids.length==0){
            alert('Nenhum arquivo selecionado');
            return false;
        };
        var oBt=$(this).prop('disabled',true);
        //chama a rota que irá carregar os dados de cada um dos arquivo
        awAjax({
            url:'{{route($prefix.".file.getdata",$controller)}}',
            data:{id:ids},
            processData:true,
            success: function(r){console.log('**',r)
                var oModal = oList.closest('.modal');//verifica se está dentroda uma janela modal
                if(oModal.length>0)oModal.modal('hide');
                //if(fnc)callfnc(fnc,r);
                oList.trigger('onSelectFile',r);
                oBt.prop('disabled',false);
            },
            error:function (xhr, ajaxOptions, thrownError){
                oBt.prop('disabled',false);
                awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
            }
        });
    });

    oList.on('onOpen',function(e,opt){return false;});//return false para anular o click em cada item da linha
};

//Função de painel de visualização de arquivos
function awFilesListView(list_id,file_view){
    var oPanel,oList=$(list_id);

    if(file_view=='panel'){
        //adiciona a estrutura html do painel da lista
        var o=oList.find('.ui-listdata-wrap');
        if(!o.hasClass('ui-listfiles')){
            o.addClass('ui-listfiles');
            oPanel=$('<div class="ui-listdata-view"><div class="ui-listdata-view-scroll"></div></div>').appendTo(o);
        };
        //eventos da lista
        oList.on('load',function(){oPanel.find('>.ui-listdata-view-scroll').html('');});//limpa o painel
    }
    if(file_view=='modal' || file_view=='panel'){
        oList.on('onOpen',function(e,file){
            if(file_view=='panel'){
                var o=oPanel.find('>.ui-listdata-view-scroll');
                awLoading(o);
            };
            var oItem = file.oTr;//objeto item da lista
            awAjax({
               type:'GET',url: file.url,dataType:'html',
               success: function(r){
                   if(file_view=='panel'){
                       o.html(r).find('.j-btn-remove').on('onRemove',function(e,r){
                           o.html('');//limpa o painel
                           oItem.fadeOut();//oculta o item da lista
                       });
                   }else{//modal
                       var oModal=awModal({title:false,btClose:false,padding:false,html:function(o){
                                o.html(r).find('.j-btn-remove').on('onRemove',function(e,r){
                                    oModal.modal('hide');//oculta a janela
                                    oItem.fadeOut();//oculta o item da lista
                                });
                       },width:'lg'});
                   }
               },
               error:function (xhr, ajaxOptions, thrownError){
                   awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
               }
            });
            return false;//anula o click
       });
   }
}

@if($files_opt['edit_data'])
var oModalEdit=null;
function awFilesListEdit(item){
    item=$(item);
    var data=$.parseJSON(item.closest('tr').attr('data-data'));
    awAjax({
        url: item.attr('href'),dataType:'html',type:'GET',processData:true,
        data:{
            controller:'{{$controller}}',
            fields:{
                title: {{ ($files_opt['edit_data']['title']??true)?'true':'false' }},
                status: {{ ($files_opt['edit_data']['status']??true)?'true':'false' }},
                area_name: '{{ $files_opt['edit_data']['area_name']??null }}',
                area_id: '{{ $files_opt['edit_data']['area_id']??null }}',
            },
        },
        success: function(r){
            var _fLoad=function(oHtml){oHtml.html(r);}
            oModalEdit=awModal({title:'Editando Aquivo <span style="margin-left:10px;font-size:12px;" class="nostrong text-muted">'+ data.file_name+'.'+data.file_ext +'</span>',html:_fLoad,btClose:false});
        },
        error:function (xhr, ajaxOptions, thrownError){
            awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
        }
     });
}
//Ao editar os dados da lista (utilizado dentro da janela modal)
function awFilesListOnUpdate(id,r){
    setTimeout(function(){ 
        oModalEdit.modal('hide'); 
        @if($is_reload_page)
            window.location.reload();
        @else
            $('#{{$list_id}}').trigger('load',{id:id});
        @endif
    },600);
}
@endif

</script>
@php
$js_write_01=true;

if($files_opt['mode_select']??false)echo '<script>awFilesListOnSelect("#'. $params['list_id'] .'");</script>';
if($files_opt['file_view'])echo '<script>awFilesListView("#'. $params['list_id'] .'","'. $files_opt['file_view'] .'");</script>';

endif;


@endphp
