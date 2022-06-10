@inject('taxClass', 'App\Http\Controllers\TaxsController')

@php
    use App\Utilities\ColorsUtility;
    
    Form::loadScript('taxonomies');
    Form::loadScript('check');//carrega os arquivos js,css select2
    
    Form::execFnc('awTaxonomyInit');//seta o nome da função que deve ser inicializada
    $prefix = Config::adminPrefix();
    
    
    $id = (isset($id)?$id:'tax_form_'.uniqid());
    
    if(!isset($is_multiple)) $is_multiple=true;
    if(!isset($is_level))    $is_level=true;
    if(!isset($is_add))      $is_add=true;
    if(!isset($is_search))   $is_search=true;
    if(!isset($is_collapse)) $is_collapse=false;
    if(!isset($is_header))   $is_header=true;
    if(!isset($show_icon))   $show_icon=false;
    if(!isset($show_check) || $is_multiple)  $show_check=true;
    if(!isset($start_collapse)) $start_collapse=false;
    if(!isset($box_is_collapse))$box_is_collapse=true;
    if(!isset($box_is_close))   $box_is_close=false;
    if(!isset($is_popup))       $is_popup=false;
    if(!isset($taxs_start))     $taxs_start=false;
    if(!isset($class_select))   $class_select=false;
    if(!isset($metabox))    $metabox=true;
    
    if($is_popup)$box_is_collapse=false;
    
    if($taxs_start){
        if(is_numeric($taxs_start)){
            $taxs_start=[$taxs_start];
        }else if(!is_array($taxs_start)){
            $taxs_start=explode(',',$taxs_start);
        }
    }
   
    
    if(isset($icons_code)){
        if(!is_array($icons_code))$icons_code=[$icons_code,$icons_code];
    }else{
        $icons_code=['f07c','f07b'];
    }
    
    if(is_object($term_id)){
        $term = $term_id;
        $term_id = $term->id;
    }else{
        $term = $taxClass->getTerm($term_id);
        if(!$term){echo 'Código do termo inválido.'; return;}
    }
    
    $opt = array_merge([
        'is_add'=>$is_add,
        'is_multiple'=>$is_multiple,
        'is_search'=>$is_search,
        'is_collapse'=>$is_collapse,
        'show_check'=>$show_check,
        'is_popup'=>$is_popup,
        'is_add_checked'=>$is_add_checked??false,
        'class_select'=>$class_select,
    ]);
    //dump($opt);
    
    $taxs = $taxClass->getTaxList($term_id,[
        'levels'=>$is_level?null:0,
        'tax_id_parent'=>isset($tax_id_parent)?$tax_id_parent:null,
        'level_space'=>''
    ],'model');
    
    if($is_add)$opt['route_add']=route($prefix.'.taxonomy.store',$term_id);
    
    $unique_arr_tax=[];
    
    if($metabox){
    echo'<div class="box box-primary ui-taxform'. (($metabox['is_border']??true)?'':' box-widget') . ($is_popup?' ui-box-popup':'') . (($class??false)?' '.$class:'') .'" ui-taxform="on" id="'.$id.'" data-term_id="'.$term_id.'" data-opt=\''. json_encode($opt) .'\' '. ($attr??'') .'>'.
        '<input type="hidden" name="autofield_taxs_term_id[]" value="'. $term_id .'">'.
        '<input type="hidden" name="autofield_taxs_term_'. $term_id .'_uncheck" data-name="autofield_taxs_term_uncheck" value="">'.
        '<input type="hidden" name="autofield_taxs_term_'. $term_id .'_name" value="'. ($title ?? $term->term_title) .'">'.
        ($is_header ? 
            '<div class="box-header with-border">'.
                (($title??true)!==false?'<h3 class="box-title" data-title="'. ($title ?? $term->term_title) .'">'. ($title ?? $term->term_title) .'</h3>':'').
                '<div class="box-tools pull-right">'.
                    ($box_is_collapse ? '<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>' : '').
                    ($box_is_close ? '<button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-remove"></i></button>' : '').
                '</div>'.
            '</div>'
        : '').
        '<div class="box-body '. (isset($is_padding) && $is_padding==false?'no-padding':'') .'">'.
            ($is_search ? '<div class="ui-taxform-search"><input type="text" placeholder="Pesquisar"><span class="fa fa-search"></span></div>' : '');
    }else{
    echo'<div class="ui-taxform '. ($is_popup?' ui-box-popup':'') . (($class??false)?' '.$class:'') .'" ui-taxform="on" id="'.$id.'" data-term_id="'.$term_id.'" data-opt=\''. json_encode($opt) .'\' '. ($attr??'') .'>'.
        '<input type="hidden" name="autofield_taxs_term_id[]" value="'. $term_id .'">'.
        '<input type="hidden" name="autofield_taxs_term_'. $term_id .'_uncheck" data-name="autofield_taxs_term_uncheck" value="">'.
        '<input type="hidden" name="autofield_taxs_term_'. $term_id .'_name" value="'. ($title ?? $term->term_title) .'">'.
        
        (($title??true)!==false?'<h3 class="box-title" data-title="'. ($title ?? $term->term_title) .'">'. ($title ?? $term->term_title) .'</h3>':'').
        
        '<div class="margin">'.
            ($is_search ? '<div class="ui-taxform-search"><input type="text" placeholder="Pesquisar"><span class="fa fa-search"></span></div>' : '');
    }
    
    
        echo'<div class="form-group scrollmin">';
                
                if(!function_exists('taxs_form_wlistx1')){
                    
                    //modelo html de cada taxonomia
                    function taxs_form_wlistx0($tax,$term_id,$is_multiple,$is_collapse,$show_icon,$show_check,$icons_code,$start_collapse,&$unique_arr_tax,$taxs_start,$class_select){
                        if($tax){//registro existente
                            $is_sub = $tax->sub ? $tax->sub->count()>0 : false;
                            $tax_id = $tax->id;
                            $tax_id_parent = $tax->tax_id_parent;
                            $tax_title = $tax->tax_title;
                            $tax_parents = $tax->parents;
                            $tax_opt = $tax->tax_opt;
                            
                            if($taxs_start){
                                $selected = in_array($tax_id,$taxs_start);
                            }else{
                                $selected = false;
                            }
                            
                        }else{//registro em branco
                            $is_sub = false;
                            $tax_id = 'new';
                            $tax_id_parent = '';
                            $tax_title = '';
                            $tax_parents = [];
                            $selected = false;
                            $tax_opt = ['color'=>null,'icon'=>null];
                        }
                        
                        /*$n='&nbsp;';$a='';
                        if($tax_opt){
                            $a=$tax_opt['color']?' bg-'.$tax_opt['color']:'';
                            if($tax_opt['icon'])$n='<span class="fa '.$tax_opt['icon'].'" style="font-size:10px;position:relative;top:-1px;"></span>';
                        }*/
                        
                        $num_ctrl=uniqid();
                        //dump([$selected,$taxs_start]);
                        return '<div data-id="'.$tax_id.'" data-parent-id="'.$tax_id_parent.'" class="ui-taxform-gr '. ($is_multiple ? 'checkbox':'radio') . ($tax_id=="new"?" hiddenx":"") . '" title="'. join(' > ',$tax_parents) .'">'.
                                    ( $is_collapse ? '<span '. ($is_sub?'data-toggle="collapse" data-target="#ui-taxform-sub-'. $num_ctrl .'"':'') .' class="ui-taxform-collapse-icon pull-left '. ($start_collapse?'collapsed':'') .'">'. ($is_sub ?'<span class="fa fa-caret-down" style="position:relative;top:-1px;"></span> ':'') .'</span>' : '' ).
                                    '<label style="padding-left:0;" class="ui-taxform-item'. ($selected && $class_select?' selected':'') .'" data-term_id="'. $term_id .'" data-tax_id="'. $tax_id .'" data-tax_title="'. html_entity_decode($tax_title) .'" data-tax_color="'. ColorsUtility::getColor($tax_opt['color']) .'" data-tax_icon="'.$tax_opt['icon'].'">'.
                                        ($show_check ? '<input type="'. ($is_multiple ? 'checkbox':'radio') .'" name="autofield_taxs_term_'.$term_id.'[]" value="'. $tax_id .'"'. ($selected?' checked="cheked"':'') .' data-load-select="'. ($selected?'on':'off') .'"><span class="checkmark"></span> &nbsp; ' : '').
                                        ($show_icon===true && !$tax_opt['icon'] ? '<span class="ui-taxform-item-icon fa" data-iopen="&#x'. $icons_code[ $is_sub ? 0 : 1 ] .'" data-iclose="&#x'. $icons_code[1] .'"></span>' : '').
                                        (!$tax_id_parent && ($show_icon!==false && (gettype($show_icon)=='string' || $tax_opt['icon'])) ? '<span class="ui-taxform-item-icon"><span class="fa '. ($tax_opt['icon']?$tax_opt['icon']:$show_icon) .'" style="'. ColorsUtility::getTextColor($tax_opt['color']) .'"></span></span>' : '').
                                        '<span class="ui-taxform-itemtitle">'. 
                                            //$tax_title.
                                            /*'<span class="btn btn-sm '. ($tax_opt['color']?'bg-'.$tax_opt['color']:'') .'" style="'. ($tax_opt['color']?'':'color:#000;') .'font-size:14px;line-height:16px;background:#efefef;padding:0 2px;position:relative;top:-2px;">'.
                                                $tax_title.
                                                ($tax_opt['icon']?'<span class="fa '.$tax_opt['icon'].'" style="margin-left:5px;font-size:0.8em;"></span>':'').
                                            '</span>'.*/
                                            //'<span class="btn btn-xs bg-gray'.$a.'" style="margin-left:10px;width:16px;height:16px;line-height:16px;padding:0;">'.$n.'</span>'.
                                            
                                            '<span style="'. ColorsUtility::getTextColor($tax_opt['color']) .'">'.$tax_title.'</span>'.
                                        '</span></span>'.
                                    '</label>'.
                                    ($is_sub ? '<div class="ui-taxform-sub collapse '. ($start_collapse?"":"in") .'" id="ui-taxform-sub-'. $num_ctrl .'">'. taxs_form_wlistx1($tax->sub,$term_id,$is_multiple,$is_collapse,$show_icon,$show_check,$icons_code,$start_collapse,$unique_arr_tax,$taxs_start,$class_select) .'</div>' : '').
                                '</div>';
                    }
                    
                    //lista das taxonomias
                    function taxs_form_wlistx1($taxs,$term_id,$is_multiple,$is_collapse,$show_icon,$show_check,$icons_code,$start_collapse,&$unique_arr_tax,$taxs_start,$class_select){
                        $r='';
                        foreach($taxs as $tax){
                            $unique_arr_tax[$tax->id] = str_repeat(' &#151; ',$tax->level) . $tax->tax_title;
                            $r.=taxs_form_wlistx0($tax,$term_id,$is_multiple,$is_collapse,$show_icon,$show_check,$icons_code,$start_collapse,$unique_arr_tax,$taxs_start,$class_select);
                        }
                        return $r;
                    }
                    
                }
                
                echo taxs_form_wlistx1($taxs,$term_id,$is_multiple,$is_collapse,$show_icon,$show_check,$icons_code,$start_collapse,$unique_arr_tax,$taxs_start,$class_select);

                if($is_add){
                    //escreve a taxonomia em branco (modelo para novas)
                    echo taxs_form_wlistx0(null,$term_id,$is_multiple,$is_collapse,$show_icon,$show_check,$icons_code,$start_collapse,$unique_arr_tax,$taxs_start,$class_select);
                }
                
    echo    '</div>';
    
    
    if($is_add){
        $add_html=  '<a href="#" class="ui-taxform-addlnk"><span class="fa fa-plus"></span> &nbsp; Adicionar '.$term->term_singular_title.'</a>'.
                    '<div class="ui-taxform-addbox hiddenx">'.
                        '<input type="text" name="ui-taxform-newtext" class="form-control" style="margin-bottom:2px;">'.
                        Form::select('ui-taxform-new', [''=> '— '. $term->term_singular_title . ' Pai —'] + $unique_arr_tax, null, ['class'=>'form-control ui-taxform-new']).
                        '<button style="margin-top:2px;" class="btn btn-primary" data-loading-text="<i class=\'fa fa-circle-o-notch fa-spin\'></i> Processando">Adicionar</button>'.
                    '</div>';
    }else{
        $add_html='';
    }
    
    if($metabox){
    echo'</div>'.
               ($is_add ? '<div class="box-footer">'.$add_html.'</div>' : '').
        '</div>';
        
    }else{
    echo'</div>'.
               $add_html.
        '</div>';
    }
    
@endphp