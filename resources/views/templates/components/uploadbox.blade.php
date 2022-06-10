@php
if(isset($name)){
    Form::loadScript('forms');
    
    $uniq_id = uniqid();
    $prefix = Config::adminPrefix();
    $controller = is_string($controller) ? $controller : $controller->getConfig('basename');
    
    $defined_vars=get_defined_vars();
    $defined_vars['type']='';
    $defined_vars['name']='';
    $defined_vars['color']=empty($defined_vars['color'])?'primary':$defined_vars['color'];
    $defined_vars['id']=$uniq_id.'-btupl';
    $defined_vars['title']=empty($defined_vars['title'])?'Upload':$defined_vars['title'];
    
    $filemanager = $filemanager??false;
    
    if($filemanager){
        $upload_db=true;
    }else{
        $upload_db=isset($upload_db) && $upload_db==false?false:true;
    }
    
    $data_opt=array(
        'route'=>$route ?? route($prefix.'.file.'. ($upload_db?'post':'postdirect'),$controller ),
        'upload'=>$upload??null,
        'upload_db'=>$upload_db,
        'upload_form'=>$upload_form??null,
        'upload_view'=>(isset($upload_view) && $upload_view===false?false:  
                            array_merge([
                                'width'=>300,'height'=>300,'remove'=>'r2','thumbnail'=>'medium'
                            ],$upload_view??[])
                       )
    );
    //dump($data_opt);
    $data_opt['filename_show']=array_get($data_opt['upload_view'],'filename_show')!==false ? array_get($upload??null,'filename'):null; //nome do arquivo a ser exibido no box de visualização
    
    if($filemanager){
        unset($data_opt['route']);
        $data_opt['filemanager']=$filemanager;
    }
    
    $file=null;
    $file_url='';
    if(!isset($value))$value='';
    if($upload_db){//file registrado no db
        if(!empty($value)){
            $file = \App\Services\FilesService::getInfo($value,'json',$controller);
            if(!$file['success']){
                $file=null;//imagem não encontrada ou erro
                $value='';//limpa o campo
            }
        }
            
    }else{//file direto em diretórios (não registrado no db)
        if(!empty($value)){
            //param data php
            $value = [
                'filename'  =>array_get($data_opt,'upload.filename'),
                'folder'    =>array_get($data_opt,'upload.folder'),
                'private'   =>array_get($data_opt,'upload.private'),
                'account_off'=>array_get($data_opt,'upload.account_off'),
                'account_id'=>array_get($data_opt,'upload.account_id'),
            ];
            //xxx $account_id = array_get($data_opt,'upload.account_id');
            //xxx if($account_id)$value['account_id']=$account_id;
            //dd($value);
            
            //get info file
            $file = \App\Services\FilesDirectService::getInfo($value);
            //dd($value,$file);
            if(!$file['success']){
                $file=null;//imagem não encontrada ou erro
                $value='';//limpa o campo
            }else{
                //serialize do input hidden
                $value = serialize($value);
            }
        }
    }
    if($file)$file_url=$file['file_url'];
    
    
    echo '<div class="form-group form-group-'.$name.' '.($class_group ?? '').'" id="form-group-'.$uniq_id.'" data-opt=\''. json_encode($data_opt) .'\'>';
        if(!empty($label))echo '<label '. (!empty($id) ? 'for="'.$id.'"':'')  .' class="control-label '.($class_label ?? '').'">'.$label.'</label>';
        
    echo '<div class="control-div '.($class_div ?? '').'">';
            
          echo '<input type="hidden" name="'. $name .'--url" value="'. $file_url .'" data-name="url" data-is_image="'. ($file && $file['is_image']?'s':'n') .'" data-label="'. htmlspecialchars($data_label??$label??'') .'">'.
               '<input type="hidden" name="'. $name .'" value="'. htmlentities($value) .'" data-name="name" data-label="'. htmlspecialchars($data_label??$label??'') .'">';
            
          echo view('templates.components.button',$defined_vars);
          
          echo '<br><div class="uploadbox-view text-center inlineblock"></div>';
          
          
          if(!empty($info_html))echo '<div class="control-html">'. $info_html .'</div>';
          
          echo '<span class="help-block"></span>'.
        '</div>'.
    '</div>';
    echo '<script>awUploadFieldBox("'.$uniq_id.'");</script>';
}else{
    echo 'Campo UploadBox - parâmetro Name não definido.';
}
@endphp