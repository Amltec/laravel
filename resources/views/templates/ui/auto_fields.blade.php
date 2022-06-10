@php

Form::loadScript('forms');


//obs: código do metabox duplicado de templates.components.metabox.blade
if(!isset($metabox) || empty($metabox))$metabox=false;

//formulário
if(!isset($form))$form=[];
if($form){
    Form::execFnc('awFormAjax');//seta o nome da função que deve ser inicializada

    //obs: neste bloco, utiliza a var $autodata apenas a que vem do formulário $form[autodata]
    if(!isset($form['autodata']))$form['autodata']=false;

    $form_action_param=[
        'id'=>$form['id']??'form_auto_'.uniqid(),
        'class'=>$form['class']??null,
        'alert'=>$form['alert']??true,
        'form-auto-init'=>array_get($form,'autoinit')===false?'':'on',
        'autocomplete'=>'off',
    ];
    
    
    if($form['autodata'])$form_action_param['method']='put';
    if(isset($form['method']))$form_action_param['method']=$form['method'];
    
    
    $form_action_param['url']=isset($form['url_action']) ? $form['url_action'] : '';
    
    if(!empty($form['files']))$form_action_param['files']=true;
    
    
    //opções do formulário
    $form_action_param['data-opt']=(isset($form['data_opt']) ? $form['data_opt'] : []);
    if(isset($form['url_success']))$form_action_param['data-opt']['urlSuccess']=$form['url_success'];
    //if(!empty($form['fileszone']))$form_action_param['data-opt']['fileszone']=true;
    if($form_action_param['data-opt']){$form_action_param['data-opt']=json_encode($form_action_param['data-opt']);}else{unset($form_action_param['data-opt']);}
    
    //dd($form_action_param);
    if($form['autodata']){
        echo Form::model($form['autodata'],$form_action_param);
    }else{
        echo Form::open($form_action_param);
    }
}

//atualizar a var $autodata com a do formulário caso não exista a var $autodata
if($form && $form['autodata'])$autodata=$form['autodata'];//dados já carregados


//converte a var para um formato de array para funcionar corretamente no loop mais abaixo (válido somente para blocos não dinâmicos, pois os dinâmicos já são informados como array)
if(empty($block_dinamic) && isset($autodata))$autodata=[$autodata];

//cria a var que indica se é cadastro ou atualização de dados
$this_action=isset($autodata)===false?'add':'edit';

//caso não definido, precisa inicializar com uma variável vazia
if(empty($autodata))$autodata=[new StdClass];

if($metabox){
    if($metabox===true){//configuração padrão
        $metabox=['_1'=>true];//para na verificação if($metabox) for true
    }else if(is_array($metabox)==false){
        $metabox=false;
    }
    
    if(isset($metabox['title']) && !isset($metabox['header'])){
        $metabox['header']='';
    }else if(!isset($metabox['title']) && !isset($metabox['header'])){
        $metabox['header']=false;
    }
    echo'<div class="box box-'. ($metabox['color']??'primary') .' '.  ((isset($metabox['is_border']) && $metabox['is_border']===true) || !isset($metabox['is_border'])?'':'box-widget') .  (isset($metabox['is_bg']) && $metabox['is_bg']===true?'box-solid ':'') . ($metabox['class']??'') .'" >';
         if(isset($metabox['header']) && $metabox['header']!==false){ 
            echo'<div class="box-header with-border">'.
                    '<h3 class="box-title">'. ($metabox['title'] ?? 'Título') .'</h3>'.
                    '<div class="box-tools pull-right">';
                        callstr($metabox['header']);
                        echo(isset($metabox['is_collapse']) && $metabox['is_collapse'] ? '<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>' : '').
                            (isset($metabox['$is_close']) && $metabox['$is_close'] ? '<button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-remove"></i></button>' : '').
                    '</div>'.
                '</div>';
        }
        
        echo '<div class="box-body '. (isset($metabox['is_padding']) && $metabox['is_padding']==false?'no-padding':'') .'">';
}



$isBtSave=($form && isset($form['bt_save']) && $form['bt_save']!==false);
$isBtBack=($form && isset($form['bt_back']) && $form['bt_back']!==false);



if(isset($autocolumns)){
    if(!isset($attr))$attr='';
    
    if(isset($layout_type) && $layout_type=='horizontal'){
        $def_class_label='col-sm-2';
        $def_class_div='col-sm-10';
    }else{//vertical
        $def_class_label='';
        $def_class_div='';
    }
    
    if(isset($layout_type) && $layout_type=='row' && $isBtSave){//adiciona o botão na última coluna
        $autocolumns['bt_save']=['type'=>'submit', 'title'=>($form['bt_save']===true? ($this_action=='edit'?'Atualizar':'Salvar') :$form['bt_save'])  ];
        $autocolumns['bt_save']=function() use($form,$this_action){
            $v=($form['bt_save']===true? ($this_action=='edit'?'Atualizar':'Salvar') :$form['bt_save']);
            return '<div class="form-group form-group-bt_save form-group-button" id="form-group-bt_save"><button style="width:100px;" type="submit" class="btn btn-primary" data-loading-text="<i class=\'fa fa-circle-o-notch fa-spin\'></i> '. $v .'">'. $v .'</button></div>';
        };
        $isBtSave=false;
    }
    
    
    if(empty($block_dinamic)){
        $block_dinamic = false;
    }
    if($block_dinamic)$attr.=trim(' form-block-dinamic=\''. json_encode($block_dinamic) .'\' ');
    
    if(!isset($__block_dinamic_re))
    echo '<div class="form-block-wrap'
                    . (($block_dinamic['mode']??false)?' form-block-dinamic-mode-'.$block_dinamic['mode']:'') 
                    . (($block_dinamic['remove_last']??false)?' form-block-dinamic-rlast' : '')
                    .' clearfix form-'. ($layout_type??'vertical') 
                    .' '. ($class??'') 
               .'" '. $attr .'>';
            
            if($block_dinamic){
                echo'<input type="hidden" name="'. ($prefix??'') .'_autofield_count" data-type="autofield_count" value="" >'.
                    '<input type="hidden" name="'. ($prefix??'') .'_autofield_remove_ids" data-type="autofield_remove_ids" value="" >';
            }
            
            if($block_dinamic){
                //cria um bloco padrão para o modo dinâmico
                $__tmpVars=get_defined_vars();
                $__tmpVars['__block_dinamic_re']=true;//este parâmetro serve apenas para que no loop deste template não processe corretamente não executando alguns comandos
                if($block_dinamic['numeral']??false)$__tmpVars['__block_dinamic_numeral']=true;//este parâmetro serve apenas para que no loop deste template não processe corretamente não executando alguns comandos
                unset($__tmpVars['metabox'],$__tmpVars['block_dinamic'],$__tmpVars['form'],$__tmpVars['form_action_param'],$__tmpVars['metabox'],$__tmpVars['autodata']);
                //dd($__tmpVars);
                echo view('templates.ui.auto_fields',$__tmpVars);
            }
    
        
        $autodata_index=0;
        $autodata_tmp = (object)(array)$autodata;
        
        foreach($autodata_tmp as $autodata_i=>$autodata_arr){
            if($block_dinamic){
                //substitui todos os campos {N}
                foreach((array)$autodata_arr as $k1=>$v1){
                    if(strpos($k1,'{N}')!==false){
                        $k2=str_replace('{N}','{'. ($autodata_i) .'}',$k1);
                        $autodata_arr->{$k2}=$v1;
                        unset($autodata_arr->{$k1});
                    }
                }
            }
            $autodata = $autodata_arr;
            //dump($autodata);
            
            echo '<div class="form-block-group clearfix'. (isset($__block_dinamic_re)?' form-block-group-def hidden':'') . ($autodata_index>0 && $block_dinamic && $block_dinamic['mode']=='block'?' form-block-group-sep':'') .'" data-i="'. $autodata_i .'"  style="position:relative;" data-id="'. data_get($autodata,'id') .'" data-area_name="'. data_get($autodata,'area_name') .'">';
                if($block_dinamic || isset($__block_dinamic_re)){
                    if(($block_dinamic['numeral']??false) || isset($__block_dinamic_numeral))echo '<div class="btn btn-link j-numeral" style="text-decoration:none;cursor:default;color:#999;position:absolute;z-index:9;left:-20px;"><span>'. $autodata_i .'</span></div>';
                }
                foreach($autocolumns as $name => $col){
                        if(is_callable($col)){
                            echo call_user_func($col);
                            continue;
                        }
                
                        //deve trocar a string {N} por 1, para funcionar corretamente com a função /js/admin.js->awFncBlocDinamic()
                        if($block_dinamic && !isset($__block_dinamic_re)){
                            $name = str_replace('{N}','{'. ($autodata_i) .'}',$name);
                        }

                        $autopage_col_include='';
                        $autopage_col_params=array_merge($col,[
                            'label'=>array_get($col,'label'),
                            'name'=>$name,
                            'class_label'=>$def_class_label,
                            'class_div'=>$def_class_div,
                        ]);
                        if(isset($col['require']) && $col['require']===true){
                            $autopage_col_params['class_group']=trim((isset($autopage_col_params['class_group'])?$autopage_col_params['class_group']:'').' require');
                        }
                        if(!empty($col['class_label'])) $autopage_col_params['class_label']=$col['class_label'];
                        if(!empty($col['class_div'])) $autopage_col_params['class_div']=$col['class_div'];


                        if(!isset($col['type']))$col['type']='text';
                        if(!isset($autopage_col_params['attr']))$autopage_col_params['attr']='';
                        if(isset($col['mask'])){
                            $autopage_col_params['mask'].=' data-mask="'.$col['mask'].'"';
                            Form::loadScript('inputmask');
                        }
                        

                        if($col['type']=='hidden'){
                            $autopage_col_include='hidden';
                        
                        }else if(in_array($col['type'],['phone','email']) && array_get($col,'is_mark')===true){
                            $autopage_col_include=$col['type'].'_mark';
                        
                        }else if(in_array($col['type'], ['text','email','password','search','number','date','time','daterange','cpf','cnpj','cep','phone','phone_only','decimal','currency','color']) ){
                            $autopage_col_include='text';
                            $autopage_col_params['type']=$col['type'];               

                        }else if(in_array($col['type'],['select','select2','colorbox'])){
                            $autopage_col_include=$col['type'];
                            if(isset($col['list']))$autopage_col_params['list']=$col['list'];

                        }else if(in_array($col['type'],['select_icon','button','button_field','button_group','editor','editorcode','upload','uploadzone','uploadbox','textarea','phone_mark','email_mark','cidade_uf','endereco_num','uf','pais','datetime','radio','checkbox','sim_nao','text_filemanager'])){
                            $autopage_col_include=$col['type'];
                            
                        }else if(in_array($col['type'],['link'])){
                            $autopage_col_include='button';
                            $autopage_col_params['type']='button';
                            $autopage_col_params['color']='link';
                            
                        }else if(in_array($col['type'],['submit','submit_field'])){
                            $autopage_col_include=$col['type']=='submit_field'?'button_field':'button';
                            $autopage_col_params['type']='submit';
							
                        }else if($col['type']=='info'){
                           $autopage_col_include='info';
                           if(isset($col['text']))$autopage_col_params['text']=$col['text'];
                           
                        }else if($col['type']=='html'){
                            $autopage_col_include='html';
                        }

                        if($this_action=='edit' && isset($col['_format']) && is_callable($col['_format'])){
                            //formata os dados para o formulário
                            $autodata->$name = call_user_func($col['_format'],$autodata->$name);
                        }

                        //dd($autodata,$autopage_col_params);

                        if($autopage_col_include!=''){
                            @endphp
                            @include('templates.components.'.$autopage_col_include,$autopage_col_params)
                            @php
                            
                            //echo view('templates.components.'.$autopage_col_include,$autopage_col_params);
                        }

                $autodata_index++;
                }
                
                if($block_dinamic || isset($__block_dinamic_re)){
                    echo '<button type="button" class="btn btn-link j-remove" title="Remover" style="position:absolute;z-index:9;right:-40px;"><span class="fa fa-trash"></span></button>';
                }
                
            echo '</div>';
    }
    
    
    
    if(!isset($__block_dinamic_re))
    echo'</div>';
    
    
}else{
    echo '<p>Nenhum campo para exibir</p>';
}


if($metabox){//com metabox
        echo '</div>';
        if(isset($metabox['footer']) || $isBtSave || $isBtBack){ 
            echo'<div class="box-footer">';
                if($isBtSave){
                    echo '<button type="submit" class="btn btn-primary">'. ($form['bt_save']===true? ($this_action=='edit'?'Atualizar':'Salvar') :$form['bt_save']) .'</button>';
                }
                if($isBtBack){
                    echo '<a href="'. ($form['url_back']??'') .'" class="btn btn-default pull-right">'. ($form['bt_back']===true?'Voltar':$form['bt_back']) .'</a>';
                }
                
                if(isset($metabox['footer']))callstr($metabox['footer']);
                if($form && $form_action_param['alert']===true)echo view('templates.components.alert-structure');
            echo '</div>';
        }
    echo'</div>';
}else{//sem metabox
    if($isBtSave){
        echo '<button type="submit" class="btn btn-primary">'. ($form['bt_save']===true? ($this_action=='edit'?'Atualizar':'Salvar') :$form['bt_save']) .'</button>';
    }
    if($isBtBack){
        echo '<a href="'. $form['url_back'] .'" class="btn btn-default pull-right">'. ($form['bt_back']===true?'Voltar':$form['bt_back']) .'</a>';
    }
    if($form && $form_action_param['alert']===true && ($isBtSave) )echo view('templates.components.alert-structure');
}


if($form){
    //dd($metabox));
    if($form_action_param['alert']===true)if(!(isset($metabox['footer']) || $isBtSave || $isBtBack))echo view('templates.components.alert-structure');
    //end form
    echo Form::close();
}


@endphp