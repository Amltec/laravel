@php
use \App\Services\FilesService;
use \App\Services\FilesDirectService;


$route_action = $route_action??false;
$onRemove = $onRemove??null;
$fields = $fields??false;if(gettype($fields)=='string')$fields=array_map('trim',explode(',',$fields));
$prefix = Config::adminPrefix();

$is_file_db=true;
if(isset($file) && is_numeric($file)){
    if(empty($controller))exit('files_view.blade - $controller não informado');
    $file=FilesService::getInfo($file,'object',$controller);
    if(!$file){echo 'Erro ao localizar arquivo';return;}
    
}else if(isset($file) && is_object($file)){
    //object model
    if(!$file){echo 'Erro ao localizar arquivo';return;}
    
}else{
    $private=$private??false;
    $file = FilesDirectService::getInfo([
        'filename'=>$filename??null,
        'folder'=>$folder??null,
        'private'=>$private,
        'account_off'=>$account_off??null,
        'account_id'=>$account_id??null
    ]);
    $is_file_db=false;
    if(!$file['success']){echo 'Erro ao localizar arquivo';return;}
}
if($is_file_db){//pela tabela db
    $link_full = $file->getUrl();
    $link = $link_full;
    if(strlen($link)>40){
        $n2=substr($link,strlen($link)-15,strlen($link));
        $link=str_limit($link,22,'...').$n2;
    }
    
    $data = [
        'view'=>['class_value'=>'no-padding','value'=>function() use($file){
            if($file->is_image){
                $img = $file->getUrlThumbnail('full');//0 img, 1 width, 2 height
            }else{
                $img = [$file->getIcon()['image'], null, null];
            }
            return '<div class="ui-view-cover text-center" title="Duplo clique para abrir" onclick="if(window.event.ctrlKey || window.event.shiftKey)$(this).trigger(\'dblclick\');" ondblclick="window.open(\''. $img[0] .'\');"><img class="transition1" src="'. $img[0] .'" data-size="'. $img[1] .'x'. $img[2] .'" /></div>';
        }],
        'title'=> ['title'=>'Título','value'=>$file->file_title,'alt'=>'ID #'.$file->id],
        'name'=> ['title'=>'Arquivo','value'=>$file->file_name_full,'alt'=>'ID #'.$file->id],
        'size'=> ['title'=>'Peso','value'=>$file->file_size,'type'=>'bytes'],
        'type'=> ['title'=>'Tipo','value'=>$file->file_mimetype],
        'created_at'=> ['title'=>'Criado em','value'=>$file->created_at,'type'=>'datetime','class_row'=>'hidden'],
        'updated_at'=> ['title'=>'Modificado em','value'=>$file->updated_at,'type'=>'datetime','class_row'=>'hidden'],
        'deleted_at'=> ['title'=>'Excluído em','value'=>$file->deleted_at,'type'=>'datetime','class_row'=>'hidden'],
        'user'=> ['title'=>'Usuário','value'=>function() use($file){ return $file->users->user_name;},'class_row'=>'hidden' ],
        'folder'=> ['title'=>'Pasta','value'=>($file->private?'Privado':'Pública').': '.$file->folder,'class_row'=>'hidden'],
        'storage'=> ['title'=>'Armazenamento','value'=>DIRECTORY_SEPARATOR.($file->private?'private:'.DIRECTORY_SEPARATOR:'') .  $file->file_path . DIRECTORY_SEPARATOR . $file->file_name .'.'. $file->file_ext, 'class_row'=>'hidden'],
        'link'=> ['title'=>'Link','value'=>'<a href="'.$link_full.'" title="'.$link_full.'" target="_blank">'.$link.'</a>'],
    ];
    if($file->deleted_at){
        $data=['deleted'=>['title'=>false,'value'=>'<strong class="label label-danger">Arquivo excluído</strong>']] + $data;
    }else{
        unset($data['deleted_at']);
    }
    if(!file_exists($file->getPath())){
        $data = array_merge(['not_exists'=>'<div class="text-center"><span class="label bg-red">Arquivo não existe</span></div>' ],$data);
    }
    
    if($file['is_image']){
        $data['thumbnail']=['title'=>'Miniaturas','value'=>'<a href="'. route($prefix.'.app.get',['files','thumbnails',$file->id]) .'" target="_blank">Visualizar</a>','class_row'=>'hidden'];
    }
    
    
    //relations
    $relations=$file->relations;
    if($relations->count()>0){
        $r='';
        foreach($relations as $reg){
            $r.='<div>'. $reg->area_name .' #'. $reg->area_id .'</div>';
        }
        $data['relations']=['title'=>'Relacionados','value'=>$r,'class_row'=>'hidden'];
    }
    
    
    $param=[
        'data'=>$data,
        'class_field'=>'text-right text-muted',
        'class_value'=>($file->deleted_at?'text-red':''),
    ];
    
        
}else{//por diretórios
    $link_full = $file['file_url'];
    $xstorage = str_replace('\\','/',$private ? storage_path() : public_path());
    $xpath    = str_replace('\\','/',$file['file_path']);
    $xstorage = str_replace($xstorage,'', $xpath);
    
    $data = [
            'view'=>['class_value'=>'no-padding','value'=>function() use($file){
                return '<div class="ui-view-cover text-center" title="Duplo clique para abrir" onclick="if(window.event.ctrlKey || window.event.shiftKey)$(this).trigger(\'dblclick\');" ondblclick="window.open(\''. $file['file_url'] .'\');"><img class="transition1" src="'. $file['file_url'] .'" data-size="'. $file['width'] .'x'. $file['height'] .'" /></div>';
            }],
            /*'name'=> ['title'=>'Arquivo','value'=>$file['file_name']],
            'size'=> ['title'=>'Peso','value'=>$file['file_size'],'type'=>'bytes'],
            'type'=> ['title'=>'Tipo','value'=>$file['file_mimetype']],
            'updated_at'=> ['title'=>'Modificado em','value'=>$file['file_lastmodified'],'type'=>'datetime', 'class_row'=>'hidden'],
            'folder'=> ['title'=>'Pasta','value'=>($private?'Privado':'Pública').': '.$file['file_relative_dir'], 'class_row'=>'hidden'],
            'storage'=> ['title'=>'Armazenamento','value'=> $xstorage, 'class_row'=>'hidden'],*/
            'link'=> ['title'=>'Link','value'=>$file['file_url'],'type'=>'link'],
        ];
    
        
    $param=[
        'data'=>$data,
        'class_field'=>'text-right text-muted',
    ];
    
}

//Filtro de campos para serem exibidos
if($fields){
    array_push($fields,'view');
    $param['filter']=$fields;
}


if(isset($view_params)){
    $param = array_merge($param,$view_params);
}

echo view('templates.ui.view',$param);



//conta se tem campos ocultos
$count_hides=0;
foreach($data as $f=>$d){
    if($fields===false || (is_array($fields) && in_array($f,$fields))){
        $n=$d['class_row']??'';
        if(strpos($n,'hidden')!==false)$count_hides++;
    }
}


$btn_seemore = $count_hides>0 ? '<p><a href="#" class="text-small" onclick="var o=$(this);var as=o.data(\'data-fields\');if(!as){as=o.closest(\'.ui-view-toolbar\').prev().find(\'.ui-view-row.hidden\');o.data(\'data-fields\',as);}if(o.text()==o.attr(\'data-title2\')){as.addClass(\'hidden\');o.text(o.attr(\'data-title1\'));}else{as.removeClass(\'hidden\');o.text(o.attr(\'data-title2\'));};return false;" data-title1="Ver mais" data-title2="Ver menos">Ver mais</a></p>' : '';

if($is_file_db){//pela tabela db
    echo '<div class="ui-view-toolbar pad padding-left-lg no-pad-top">'.
        $btn_seemore;
        
        if(($bt_link??true) || ($bt_remove??true)){
        echo '<div style="border-top:1px solid #e2e2e2;padding-top:15px;">';
            
            if($bt_link??true)echo '<button class="btn btn-primary strong margin-r-5 j-btn-link" onclick="window.open(\''. $link_full .'\');"></span> Abrir</button>';
            
            if($bt_remove??true){
                echo view('templates.components.button',[
                    'alt'=>'Remover','icon'=>'fa-trash','title'=>($file->deleted_at?'!':''), 'class'=>'margin-r-5 j-btn-remove j-action-remove',
                    'post'=>[
                        'url'=>$route_action ? $route_action : route($prefix.'.file.post',$controller),
                        'data'=>['action'=> ($file->deleted_at?'remove':'trash') ,'id'=>$file->id],
                        'confirm'=> ($file->deleted_at ? 'Deseja remover este arquivo?' : 'Deseja enviar o arquivo para a lixeira?'),
                        'cb'=>['@function(opt){opt.oBt.trigger("onRemove",opt);}',$onRemove],
                    ]
                ]);


                if($file->deleted_at){
                    echo view('templates.components.button',[
                        'alt'=>'Restaurar','icon'=>'fa-recycle','title'=>false,'class'=>'j-btn-remove j-action-restore',
                        'post'=>[
                            'url'=>route($prefix.'.file.post',$controller),
                            'data'=>['action'=>'restore','id'=>$file->id],
                            'confirm'=> 'Restaurar arquivo da lixeira?',
                            'cb'=>['@function(opt){opt.oBt.trigger("onRemove",opt);}',$onRemove],
                        ]
                    ]);
                }
            }
        
        echo '</div>';
        }
        
    echo '</div>';
    
    
}else{//por diretórios
    echo '<div class="ui-view-toolbar pad padding-left-lg no-pad-top">'.
        $btn_seemore.
        '<div style="border-top:1px solid #e2e2e2;padding-top:15px;">'.
            '<button class="btn btn-primary strong margin-r-5" onclick="window.open(\''.$data['link']['value'] .'\');"></span> Abrir</button>';

            echo view('templates.components.button',[
                'alt'=>'Remover','icon'=>'fa-trash','title'=>false,
                'post'=>[
                    'url'=>$route_action ? $route_action : route($prefix.'.file.postdirect','files'),
                    'data'=>['action'=>'remove','file'=>$file['file_name_full'],'folder'=>$folder??'','private'=>$file['private']?'s':'n','account_off'=>($account_off??false)?'s':'n'],
                    'confirm'=>'Deseja remover este arquivo?',
                    'cb'=>['@function(opt){opt.oBt.trigger("onRemove",opt);}',$onRemove],
                ]
            ]);
    
   echo '</div>'.
        '</div>';
    
}
@endphp