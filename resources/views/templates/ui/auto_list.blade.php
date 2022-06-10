@php
Form::loadScript('lists');
if($options['search']??true)Form::loadScript('forms');
Form::execFnc('awListDataInit');//seta o nome da função que deve ser inicializada

if(!isset($data))$data=null;

$is_ajax_load = (isset($is_ajax_load)?$is_ajax_load:Request::ajax());

$options=array_merge([
    'is_trash'=>($_GET['is_trash']??'')==='s',
    'checkbox'=>false,
    'collapse'=>false,
    'header'=>true,
    'footer'=>true,
    'pagin'=>true,
    'select_type'=>1,
    'confirm_remove'=>false,
    'total'=>false,
    'subtotal'=>false,
    'page'=>request()->input('page')??1, // parâmetro automático com o número da página atual
    'toolbar'=>false,
    'toolbar_menu'=>true,
    'remove'=>true,
    'reload'=>true,
    'regs'=>true,
    'columns_sel'=>false,
    'list_remove'=>true,
    'search'=>true,
    'perpage'=>$data && method_exists($data,'perpage')? $data->perpage() : 0,//automático com o número de páginas
    'allow_trash'=>true,
    'bt_add'=>'Adicionar',
],$options??[]);

if($options['allow_trash']==false){
    //desabilita as opções da lixeira
    $options['list_remove']=false;
    $options['is_trash']=false;
    $options['confirm_remove']=true;
}


$routes=array_merge([
    'load'=>false,
    'remove'=>false,
    'collapse'=>false,
    'click'=>false,
    'dblclick'=>false,
    'add'=>false,
],$routes??[]);
if($routes['remove'])$options['route_remove']=callstr($routes['remove'],null,true);
if($routes['load'])$options['route_load']=$routes['load'];

//taxonomias
$taxs=$taxs??false;
if($taxs){
    Form::loadScript('taxonomies');
    $options['taxs'] = [];
    foreach($taxs as $term_id=>&$tax){
        if(!isset($tax['term_model'])){
            $tax['term_model'] = \App\Models\Term::find($term_id);
        }

        $options['taxs'][$term_id]=[
            'tax_form'=>'#autolist_box_terms_'.$term_id, //registro o id do box da taxonomia
        ];
        if($tax===true || isset($tax['button'])==false || !empty($tax['button'])){//registro o id do botão que aciona a taxonomia
            $options['taxs'][$term_id]['button']='#autolist_bt_tax_'.$term_id;
        }
        if(isset($tax['tax_form_type']))$options['taxs'][$term_id]['tax_form_type'] = $tax['tax_form_type'];
        if(isset($tax['area_name']))$options['taxs'][$term_id]['area_name'] = $tax['area_name'];
    }
    $options['taxs_start'] = _GET('taxs_id')?_GET('taxs_id'):($taxs_start??null);
}


if($options['select_type']===0)$options['checkbox']=false;
$list_id = (isset($list_id)?$list_id:'listdata_'.uniqid());
$field_id = (isset($field_id)?$field_id:'id');
$field_click = (isset($field_click)?$field_click:''); if($field_click=='none')$field_click=false;
$class  = $class??'';
$list_class = (isset($list_class)?$list_class:'');
if($options['select_type']>0)$list_class.=' table-hover';
$options['field_click']=$field_click;
if($field_click && !is_array($field_click))$field_click=[$field_click];
$open_modal = $open_modal??false;

$field_group = (isset($field_group)?$field_group:'');
if($field_group){
    $options['field_group']=true;
}else{
   $options['total']=false;
   $options['subtotal']=false;
}


if(!isset($metabox) || empty($metabox))$metabox=false;

if($options['columns_sel']){
    if(empty($columns_show)){
        $columns_show=array_keys($columns);//captura o nomes das colunas
    }else if(gettype($columns_show)=='string'){
        $columns_show = explode(',',$columns_show);
    }
}else{
    if(empty($columns_show)){
        $columns_show=array_keys($columns);
    }else if(gettype($columns_show)=='string'){
        $columns_show = explode(',',$columns_show);
    }
}

//remove das colunas a serem exibidas o nome da coluna agrupada
if($field_group)unset($columns_show[ array_search($field_group,$columns_show) ]);


//rotas
if(!function_exists('awAutoListUI_route')){
    /* Parâmetros:
            $name - route name
            $attr0 - nome do atributo inicial. Ex: 'href', então retornará a href="..."
                       Aceita também o valor 'dbl-href' que altera para 'onclick=window.open(...)'
    */
    function awAutoListUI_route($reg,$routes,$name,$attr0){
        if(isset($routes[$name])){
            $r=callstr($routes[$name],['reg'=>$reg],true);
            if(is_array($r)){
                $n='';$i=0;
                foreach($r as $f=>$v){
                    if($f==='dbl-href'){
                        if($v)$n.='ondblclick="window.open(\''.htmlentities($v).'\');" ';
                    }else{
                        if($v)$n.=($i==0?$attr0:$f).'="'.htmlentities($v).'" ';
                    }
                    $i++;
                }
                return trim($n);
            }else{
                if($attr0==='dbl-href' && $r){
                    return 'onclick="window.open(\''.htmlentities($r).'\');"';
                }else{
                    return $attr0.'="'.htmlentities($r).'"';
                }
            }
        }else{
            return '';
        }
    }

    /* Retorna a string considerando que $route rota pode ser do tipo string ou função
       $routes_names - ex: 'click' ou ['click','edit']     //neste caso irá procurar a rota click e caso não encontre a rota edit
       Obs: até o momento usado no arquivo files_list.blade
    */
    function awAutoListUI_getRoute($reg,$routes,$routes_names){
        if(!is_array($routes_names))$routes_names=[$routes_names];
        foreach($routes_names as $rn){
            if(isset($routes[$rn])){
                $r=callstr($routes[$rn],['reg'=>$reg],true);
                return is_array($r) ? $r[0] : $r;
            }
        }
        return null;
    }
}



//subtotais
if($options['total'])$calc_total = [];
if($options['subtotal']){
    $calc_subtotal = [];

    if(!function_exists('awAutoListUI_subTotal_f1')){
        function awAutoListUI_subTotal_f1($field_group_count,$options,$columns,$calc_subtotal){
            //fim do bloco anterior
            echo '<tr class="row-group row-group-subtotal row-border-bottom" data-group="'.($field_group_count-1).'">';
                    if($options['collapse']){
                        echo '<th data-name="collapse" class="no-padding text-center">&nbsp;</th>';
                    }
                    if($options['checkbox']){
                        echo '<th data-name="checkbox">&nbsp;</th>';
                    }
                    foreach($columns as $col_name=>$col_label){
                        if(in_array($col_name,$columns_show)){
                            echo '<th>'.
                                    (isset($col_label['calc_total']) ? (isset($col_label['value'])?$col_label['value']($calc_subtotal[$col_name]):$calc_subtotal[$col_name]) : '&nbsp;').
                                '</th>';
                        }
                    }
            echo '</tr>';
        }
    }
}



//conteúdo / linhas da lista
//obs: deve retornar as linhas no formato html para inclusão dentro do TBODY
if(!function_exists('awAutoListUI_rows')){
    function awAutoListUI_rows($vars){//$name = route name
                extract($vars);

                if($field_group){
                    $field_group_val='';
                    $field_group_count=0;
                }

                $row_attribute_page = ['total'=>$data && method_exists($data,'total')?$data->total():0,'count'=>$data?$data->count():0,'perpage'=>$data && method_exists($data,'perpage')?$data->perpage():0,'currentpage'=>$data && method_exists($data,'currentpage')?$data->currentpage():0,'totalpage'=>$data && method_exists($data,'perpage')?$data->perpage()*$data->count():0];

                echo '<tbody data-row-page=\''. json_encode($row_attribute_page) .'\'>';

                if($data){
                foreach($data as $index => $reg){
                        callstr(data_get($row_opt??null,'actions'),[$reg]);


                        if($field_group){


                            if(isset($columns[$field_group])){
                                $col_label=$columns[$field_group];
                                if(is_array($col_label)){
                                    $value_field_group = isset($col_label['value'])?$col_label['value'](  data_get($reg,$col_name)  ,$reg):  data_get($reg,$col_name)  ;//é esperado uma função
                                }else{
                                    $value_field_group = $reg->$field_group;
                                }
                            }else{
                                $value_field_group = $reg->$field_group;
                            }
                            //dd($field_group,$reg->$field_group, $value_field_group,$columns[$field_group]);

                            if($field_group_val!==$value_field_group){
                                $field_group_count++;
                                $field_group_val=$value_field_group;


                                if($options['subtotal'] && $field_group_count>1){//pula o primeiro block
                                    //fim do bloco anterior
                                    awAutoListUI_subTotal_f1($field_group_count,$options,$columns,$calc_subtotal);
                                }

                                //início do próximo bloco
                                echo '<tr class="row-group row-border-bottom" data-group="'.$field_group_count.'">'.
                                        '<th data-name="group-collapse" class="col-collapse"><span title="Expandir / Colapsar" class="btn btn-link btn-xs j-group-collapse"><span class="fa fa-minus text-sm j-group-collapse"></span></span></th>'.
                                        '<th colspan="'. (count($columns) + ($options['checkbox']?1:0)) .'">'.$value_field_group.'</th>'.
                                    '</tr>';

                                //inicia / reseta o subtotal
                                if($options['subtotal']){
                                    foreach($columns as $col_name=>$col_label){
                                        if(in_array($col_name,$columns_show)){
                                            $calc_subtotal[$col_name]=0;
                                        }
                                    }
                                }
                            }
                        }


                        $data_id = data_get($reg,$field_id);

                        //classe da linha
                        $row_class = callstr(data_get($row_opt??null,'class'),[$reg],true);
                        if($options['is_trash'] || ($reg->deleted_at??false) )$row_class.=' row-deleted';

                        //atributos da linha
                        $row_attr = callstr(data_get($row_opt??null,'attr'),[$reg],true);

                        //registros que não podem serem excluídos
                        $lock_del = data_get($row_opt??null,'lock_del');

                        //registros que não podem serem clicados
                        $lock_click = data_get($row_opt??null,'lock_click');

                        if($lock_del){
                            if(is_array($lock_del)){
                                $lock_del = in_array($data_id,$lock_del);
                            }else{
                                $lock_del = callstr($lock_del,[$reg],true);//return boolean
                            }
                        }
                        if($lock_click){
                            if($lock_click=='deleted'){
                                $lock_click = $reg->deleted_at?true:false;
                            }else if(is_array($lock_click)){
                                $lock_click = in_array($data_id,$lock_click);
                            }else{
                                $lock_click = callstr($lock_click,[$reg],true);//return boolean
                            }
                        }


                        //atualiza a var $reg com os valores já processos
                        $reg->{'__lock_del'}=$lock_del;


                        echo '<tr data-id="'.$data_id.'" data-i="'.$index.'" class="row-item '.$row_class . ($lock_del?' row-lock-del':'') . ($lock_click?' row-lock-click':'') . (!$lock_click && !$field_click && $field_click!==false && ($routes['click'] || $routes['dblclick'])?' cursor-pointer':'') .'" '. ($field_group ?'data-group="'.$field_group_count.'"':'') .' '. (!$lock_click && !$field_click && $field_click!==false?''. awAutoListUI_route($reg,$routes,'click','data-url-click'):'') .' '. $row_attr .'>';

                        if($options['collapse']){
                            echo '<td data-name="collapse" class="col-collapse"><a title="Expandir / Colapsar" class="btn btn-link btn-xs j-collapse"><span class="fa fa-plus text-sm text-muted"></span></a></td>';
                        }

                        if($options['checkbox']){
                            echo '<td data-name="checkbox" class="col-check text-center"><input autocomplete="off" type="checkbox" '. ($lock_del?'disabled="disabled"':'') .'><span class="checkmark" style="margin-top:4px;"></span></td>';
                        }

                        foreach($columns as $col_name=>$col_label){
                            if(in_array($col_name,$columns_show)){
                                if($index==0){//executa somente 1x
                                    if(!isset($calc_total[$col_name]))$calc_total[$col_name]=0;
                                }

                                if(is_array($col_label)){
                                    $value = isset($col_label['value'])?$col_label['value']( data_get($reg,$col_name) ,$reg): data_get($reg,$col_name) ;//é esperado uma função
                                    if(isset($col_label['format']))$value = FormatUtility::formatDataSingle($value,$col_label['format'],'view');
                                }else{
                                    $value = str_replace(chr(10),'<br>',htmlentities(data_get($reg,$col_name)));
                                }

                                $alt_cell=null;
                                if(is_array($col_label) && isset($col_label['alt_cell'])){
                                    $alt_cell = callstr($col_label['alt_cell'],[$value,$reg],true);
                                }


                                echo '<td class="col-'.$col_name.'" title="'. $alt_cell .'"  data-value="'. htmlspecialchars(data_get($reg,$col_name)) .'">';

                                    $link=false;
                                    $dbllink=false;
                                    if(!$lock_click){
                                        if($field_click && in_array($col_name,$field_click)){
                                            $link=awAutoListUI_route($reg,$routes,'click','href');
                                            $dbllink=awAutoListUI_route($reg,$routes,'dblclick','dbl-href');
                                            if(!$link)$link='href="#"';
                                        }
                                    }

                                    if($link || $dbllink)echo '<a '. trim($link.' '. $dbllink) .' class="row-lnk-click" title="'.htmlentities($link).'">';
                                    echo $value;
                                    if($link || $dbllink)echo '</a>';

                                    if($taxs){
                                        foreach($taxs as $term_id=>$tax){
                                            if(isset($tax['show_list']) && $tax['show_list']==$col_name){
                                                echo view('templates.ui.tag_item_list',[
                                                    'taxRel'=>$reg->getTaxRelation($term_id,$tax['area_name']??''),
                                                    'term_id'=>$term_id,
                                                    'area_name'=>($tax['area_name']??'')
                                                ])->render();
                                            }
                                        }
                                    }

                                echo '</td>';

                                //calcula o total e subtotal
                                if($options['total'] && isset($col_label['calc_total']))$calc_total[$col_name] = callstr($col_label['calc_total'],['val'=>$calc_total[$col_name],'reg'=>$reg],true);
                                if($options['subtotal'] && isset($col_label['calc_total']))$calc_subtotal[$col_name] = callstr($col_label['calc_total'],['val'=>$calc_subtotal[$col_name],'reg'=>$reg],true);

                            }
                        }


                        echo '</tr>';


                        if($options['collapse']){
                            echo '<tr data-i="'.$index.'" class="row-collapse hiddenx" '. ($field_group ?'data-group="'.$field_group_count.'"':'') .' '. awAutoListUI_route($reg,$routes,'collapse','data-url-collapse') .'>'.
                                    //'<td class="col-collapse">&nbsp;</td>'.
                                    //($options['checkbox'] ? '<td class="col-check">&nbsp;</td>' : '').
                                    '<td colspan="'. (count($columns)+2) .'" class="col-collapse-content"><div>&nbsp;</div></td>'.
                                 '<tr>';

                        }


                        if($field_group){
                            $field_group_val=$value_field_group;
                        }

                }

                if($options['subtotal']){
                    //fim do último bloco
                    awAutoListUI_subTotal_f1($field_group_count+1,$options,$columns,$calc_subtotal);
                }

                }
                echo '</tbody>';
    }
}


//rodapé
if(!function_exists('awAutoListUI_footer')){
    function awAutoListUI_footer($options,$data,$footerCB=null){
        if(!$options['footer'])return;

         if($data && method_exists($data,'total') && $data->total()>$data->count()){
            $info = 'Exibindo '. (($data->currentpage()-1)*$data->perpage()+1).' - '. ((($data->currentpage()-1)*$data->perpage())+$data->count()). ' de '. $data->total() .' registros';
         }else if($data && method_exists($data,'total') && $data->count()>0){
            $info = 'Exibindo '.$data->total().' registro'.($data->total()>1?'s':'');
         }else{
            $info = '';
         }
         //dd($data);
         if($data && method_exists($data,'appends') && $options['pagin']){
            $pagin = '<div style="margin:-20px 0;">'. $data->appends(request()->except('page')) .'</div>';
         }else{
            $pagin = '';
         }

         echo '<div class="listdata-footer clearfix">';
         if($footerCB){
            echo callstr($footerCB,[
                'args1'=>[
                    'info'=>$info,
                    'pagin'=>$pagin,
                ]
            ],true);
         }else{
            echo '<div class="pull-left">', $info, '</div>';
            echo '<div class="pull-right">', $pagin, '</div>';
         }
         echo '</div>';
    }
}



//se a lista for ajax, carrega somente o conteúdo da lista (html TBOBY) ignorando o restante da tela
if($is_ajax_load){
    awAutoListUI_rows(get_defined_vars());
    echo chr(27).'--divider--'.chr(27);
    awAutoListUI_footer($options,$data,$footer_cb??null);
    return;
}



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


if(Request::ajax()){//caso todo o plugin tenha sido carregado via ajax
    //dd($_SERVER['QUERY_STRING'],json_encode(Request::query()));
    //$options['_qs']= $_SERVER['QUERY_STRING'];//querystring armazenado automaticamente devido a este recurso ser carregado via ajax
    $options['_qs']= json_encode(Request::query());//querystring armazenado automaticamente devido a este recurso ser carregado via ajax
};

if(isset($html_before))callstr($html_before);


echo '<div class="ui-listdata relative '.$class.'" ui-listdata="on" id="'.$list_id.'" '.($list_attr??'').' data-opt=\''. json_encode($options) .'\'>';



if($options['toolbar']){
    $toolbar_buttons=(isset($toolbar_buttons)?$toolbar_buttons:[]);
    $toolbar_buttons_right=(isset($toolbar_buttons_right)?$toolbar_buttons_right:false);
    $toolbar_menus=(isset($toolbar_menus)?$toolbar_menus:false);


    //botão padrão de 'adicionar'
    if($routes['add'] && $options['bt_add']){
        $toolbar_buttons_right['bt_add']=['title'=>'Adicionar','href'=>$routes['add'],'color'=>'primary','class'=>'primary','class'=>'j-bt-add'];
    }


    if($taxs && !$options['is_trash']){
        //adiciona o botão da taxonomia automaticamente
        if($toolbar_buttons===false)$toolbar_buttons=[];
        foreach($taxs as $term_id=>&$tax){
            if($tax===true || isset($tax['button'])==false || !empty($tax['button'])){
                $toolbar_buttons['bt_taxs_'.$term_id]=array_merge(
                    ['icon'=>'fa-tag','title'=>$tax['term_model']->term_title, 'color'=>'link','id'=>'autolist_bt_tax_'.$term_id, 'class'=>'j-show-on-select'],
                    isset($tax['button']) && is_array($tax['button'])?$tax['button']:[]
                );
            }
        }
    }




    echo '<div class="listdata-header clearfix" '. (array_get($metabox,'fit_table')===true?'style="margin:0 -10px;"':'') .'>
            <div class="pull-left">';
                if($options['collapse']==2)echo'<div data-name="collapse" class="inlineblock col-collapse">&nbsp;</div>';
                if($options['checkbox'])echo'<div class="inlineblock col-check text-center">'.  ( $options['select_type']==2 ? '<input autocomplete="off" type="checkbox" data-name="select-all" onclick="$(this).closest(\'.ui-listdata\').trigger(\'select\');"><span class="checkmark" style="top:6px;"></span>' : '').  '</div>';


            if($options['reload']){
                echo '<a href="#" title="Atualizar dados" class="btn btn-default margin-r-5" onclick="'. ($routes['load']?'$(this).closest(\'.ui-listdata\').trigger(\'load\');':'window.location.reload();')  .'return false;"><i class="fa fa-refresh"></i></a>';
            }
            if($options['remove']){
                echo'<span class="btns-group btns-normal'. ($options['is_trash']?' hiddenx':'') .'">'.
                        '<button type="button" class="btn btn-default margin-r-5 j-uilist-remove j-show-on-select" title="Remover"><i class="fa fa-trash"></i></button>'.
                    '</span>'.
                    '<span class="btns-group btns-trash'. ($options['is_trash']?'':' hiddenx') .'">'.
                        '<button type="button" class="btn btn-default margin-r-5 j-uilist-remove j-uilist-destroy j-show-on-select" title="Remover para sempre"><i class="fa fa-trash"></i> !</button>'.
                        '<button type="button" class="btn btn-default margin-r-5 j-uilist-restore j-show-on-select" title="Restaurar"><i class="fa fa-recycle"></i></button>'.
                    '</span>';
            }

            if($toolbar_buttons){
                foreach($toolbar_buttons as $b){
                    echo '<div class="margin-r-5 inlineblock">';
                    if(is_array($b)){
                        echo view('templates.components.button',$b);
                    }else{
                        echo callstr($b,null,true);
                    }
                    echo '</div>';
                }
            }

       echo'</div>
            <div class="pull-right">';
                echo '<div style="display: table;">';

                //busca
                if($options['search']){
                    $filter_q = _GET('q');
                    echo '<div style="display: table-cell;"><div class="field-sty1" style="width:220px;position:relative;top:2px;margin-right:20px;">
                              <span class="fa fa-search margin-r-5"></span>
                              <input autocomplete="off" type="search" placeholder="Pesquisar" class="j-uilist-search" value="'. htmlentities($filter_q) .'">
                              <span class="field-sty1-border"></span>
                            </div></div>';
                }

                if($toolbar_buttons_right){
                    foreach($toolbar_buttons_right as $b){
                        echo '<div style="display: table-cell;">';
                        if(is_array($b)){
                            echo view('templates.components.button',$b);
                        }else{
                            echo callstr($b,null,true);
                        }
                        echo '</div>';
                    }
                }


                //opções
                $tmp_menu = [];
                if($options['list_remove']){
                    $tmp_menu['list_remove']=array('title'=>'Listar da Lixeira','icon'=>'fa fa-trash','attr'=>($options['is_trash']?' style="display:none;"':'')   );
                    $tmp_menu['list_remove2']=array('title'=>'Sair da lixeira','icon'=>'fa fa-close','class'=>'text-red','attr'=>'data-deleted="s"'. ($options['is_trash']?'':' style="display:none;"') );
                };
                if($options['columns_sel']){
                    $tmp_submenus=[];
                    foreach($columns as $col_name=>$col_label){
                        $tmp_submenus[$col_name]=['title'=>(is_array($col_label) ? $col_label[0] : $col_label),'checkbox'=>true,'checked'=>in_array($col_name,$columns_show) ];
                    }
                    $tmp_submenus['__buttons']=['title'=>false,'html'=>'<a href="#" class="j-uilist-colsel btn btn-primary btn-sm">OK</a>'];
                    $tmp_menu['columns_sel']=array('title'=>'Exibir colunas','icon'=>'fa fa-columns','sub'=>$tmp_submenus);
                }
                if($options['regs'])$tmp_menu['regs']=array('title'=>'Registros por página','icon'=>'fa-edit', 'class'=>'j-uilist-regs');

                if($options['toolbar_menu'] && !empty($tmp_menu)){
                    if($toolbar_menus)$tmp_menu = array_merge($tmp_menu,$toolbar_menus);
                    echo '<div style="display: table-cell;">';
                    echo view('templates.components.button',[
                         'id_menu'=>$list_id.'_menu_opt',
                         'title'=>'',
                         'icon'=>'fa-gear', //fa-ellipsis-v
                         'color'=>'link',
                         'class_menu'=>'dropdown-menu-right',
                         'sub'=>$tmp_menu
                    ]);
                    echo '</div>';
                }

                echo '</div>';
            echo'
            </div>
    </div>'; //end .listdata-header
}//end: if($options['toolbar'])


echo '<div class="ui-listdata-wrap">';
echo'<div class="table-responsive ui-listdata-list '. (!$options['footer']?'margin-bottom-none':'') .'" '. (array_get($metabox,'fit_table')===true?'style="margin:0 -10px;"':'') .' >
        <table class="table '. $list_class .'"'. (!$options['footer']?' style="margin-bottom:0;"':'') .'>';

        if($options['header']){
            echo '<thead><tr class="thead-light">';

                if($options['collapse']){
                    echo '<th data-name="collapse" class="col-collapse no-padding text-center">&nbsp;</th>';
                }

                if($options['checkbox']){
                    echo '<th data-name="checkbox" class="col-check text-center">&nbsp;</th>';
                }
                foreach($columns as $col_name=>$col_label){
                    if(in_array($col_name,$columns_show)){
                        $label = (is_array($col_label) ? $col_label[0] : $col_label);
                        echo '<th title="'. strip_tags($col_label['alt']??$label) .'" class="col-'. $col_name .'" >'. $label .'</th>';
                    }
                }
            echo '</tr></thead>';
        }




    //dump($data->count(),$data,$field_id);


    if($data && $data->count()){
        awAutoListUI_rows(get_defined_vars());

        if($options['total']){
            echo '<tfoot><tr class="row-group row-group-total">';

                if($options['collapse']){
                    echo '<th data-name="collapse" class="col-collapse no-padding text-center">&nbsp;</th>';
                }
                if($options['checkbox']){
                    echo '<th data-name="checkbox" class="col-check">&nbsp;</th>';
                }
                foreach($columns as $col_name=>$col_label){
                    if(in_array($col_name,$columns_show)){
                        echo '<th>'.
                                (isset($col_label['calc_total']) ? (isset($col_label['value'])?$col_label['value']($calc_total[$col_name]):$calc_total[$col_name]) : '&nbsp;').
                            '</th>';
                        }
                }
            echo '</tr></tfoot>';
        }


    }else{
        echo '<tbody><tr class="row-notfound"><td colspan="'. (count($columns)+2) .'">';
            if(isset($html_not)){
                echo callstr($html_not);
            }else{
                echo '<p>Nenhum registro encontrado</p>';
            }
        echo '</td></tr></tbody>';
    }

    echo '</table>
</div>';//end .table-responsive
echo '</div>';//end .ui-listdata-wrap

awAutoListUI_footer($options,$data,$footer_cb??null);

echo '
</div>';


if(isset($html_after))echo callstr($html_after);





if($metabox){//com metabox
        echo '</div>';
        if(isset($metabox['footer'])){
            echo'<div class="box-footer">';
            echo callstr($metabox['footer']);
            echo '</div>';
        }
    echo'</div>';
}



//***** monta o box da taxonomia padrão ****
if($taxs){
    foreach($taxs as $term_id=>$tax){
        echo view('templates.ui.taxs_form',array_merge(
            [
                'id'=>'autolist_box_terms_'.$term_id,
                'term_id'=>$term_id,
                'is_collapse'=>true,
                'show_icon'=>'fa-tags',
                'is_popup'=>true,
                'start_collapse'=>true,
                'taxs_start'=>$options['taxs_start'],
                'class_select'=>true
            ],
            isset($tax['tax_form']) && is_array($tax['tax_form'])?$tax['tax_form']:[]
        ));
    }
}

static $load_write01=false;
@endphp




@if($load_write01===false && $open_modal!==false)
@php
    $load_write01=true;
@endphp
<script>
(function(){
    var modal_opts={!! json_encode($open_modal); !!};
    var oList=$('#{{$list_id}}').on('onOpen',function(e,opt){
        awAjax({
            type:'GET',url:opt.url,dataType:'html',
            success: function(r){
                var _fLoad=function(oHtml){
                    oHtml.html(r);
                }
                var oModal=awModal({title: (modal_opts.title_edit??'Edição') ,html:_fLoad,btClose:false,width: (modal_opts.width??'lg') });
            },
            error:function (xhr, ajaxOptions, thrownError){
                awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
            }
        });
        return false;//return false para anular o click
    });

    @if($routes['add'] && $options['bt_add'])
    oList.on('click','.j-bt-add',function(e){
        e.preventDefault();
        awAjax({
            type:'GET',url:"{{$routes['add']}}",dataType:'html',
            success: function(r){
                var _fLoad=function(oHtml){
                    oHtml.html(r);
                }
                var oModal=awModal({title:(modal_opts.title_edit??'Cadastro'),html:_fLoad,btClose:false,width:(modal_opts.width??'lg')});
            },
            error:function (xhr, ajaxOptions, thrownError){
                awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
            }
        });
    });
    @endif
})();
</script>
@endif
