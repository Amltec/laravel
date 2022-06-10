@php
use \App\Utilities\HtmlUtility;


if(!isset($sub))return;

if(!function_exists('awTreeComponent_createItem')){
    function awTreeComponent_createItem($arr_menu,$options,$level){
        if(isset($arr_menu) && is_array($arr_menu)){
            
            foreach($arr_menu as $menu_id=>$menu_item){
                $n=$options['routes']['click']??null;
                $route_click = $n ? callstr($n, ['id'=>$menu_id,'item'=>$menu_item], true) : '#';
                
                if(is_array($menu_item)){
                    if(array_get($menu_item,'header')===true){
                        echo '<span class="ui-tree-tbline"></span>';
                        echo'<li data-level="'. $level .'" class="ui-tree-header text-muted">'.array_get($menu_item,'title','Cabeçalho').'</li>';

                    }else{
                        $title = array_get($menu_item,'title','Menu');
                        $icon = array_get($menu_item,'icon');
                        $icon_color = array_get($menu_item,'icon_color');
                        
                        //configuração pai
                        if(!$icon)$icon = $level==0 ? ($options['icon_def']??null) : ($options['sub_icon_def']??$options['icon_def']??null);
                        if(empty($icon_color) && !empty($options['sub_icon_color_def']))$icon_color = $options['sub_icon_color_def'];
                        
                        $icon_def = array_get($menu_item,'icon_def');
                        $icon_color_def = array_get($menu_item,'icon_color_def');if(!$icon_color_def)$icon_color_def=$icon_color;
                        
                        
                        $link = array_get($menu_item,'link',$route_click);
                        $attr = array_get($menu_item,'attr','');
                        $html = array_get($menu_item,'html','');
                        $collapse = array_get($menu_item,'collapse',$options['collapse_def']);
                        $sub = array_get($menu_item,'sub',null);if(!is_array($sub)){$sub=null;}else{$sub_idx='tree-menu-'.uniqid();}
                        
                        if($sub && !$options['link_force'])$link='#'; //altera o link para '#' pois existem subdiretórios
                        
                        echo'<li data-level="'. $level .'" '. ($options['select']==$menu_id?' data-select-ref="load"':'') . ' class="'. ($options['select']==$menu_id?'ui-tree-li-selected ':'') . array_get($menu_item,'class_li') .'">';
                                echo '<span class="ui-tree-tbline"></span>';
                                if($title!==false){
                                    echo '<div class="ui-tree-item'. ($collapse?' collapsed':'') .'" '. (isset($sub_idx)?' data-toggle="collapse" data-target="#'.$sub_idx.'"':'') .'>';
                                        echo    '<a href="'. $link .'" '. HtmlUtility::targetHref($link)  .' data-id="'.$menu_id.'" title="'.array_get($menu_item,'alt').'"  class="'. ($options['select']==$menu_id?'ui-tree-a-selected ':'') . array_get($menu_item,'class','') .'" '. ($link!='#'?'onclick="window.event.stopPropagation();" ':'onclick="window.event.preventDefault();"') .$attr .'>'.  //effect-left-move
                                                    ($options['show_caret']?'<span class="ui-tree-caret">'. ($sub?'<span class="caret"></span>':'') .'</span>':'').
                                                    ($options['show_icon'] ? '<span '. ($icon_color?'style="color:'.$icon_color.';" ':'') .'class="ui-tree-icon fa '. ($icon??'fa-folder') .' margin-r-5"></span>' : '').
                                                    '<span class="ui-tree-title">'.$title.'</span>'.
                                                '</a>';
                                    echo '</div>';
                                }

                                if($html){
                                    echo '<div class="ui-tree-html">'.$html.'</a>';
                                    
                                }else if($sub){
                                    echo '<ul class="list-unstyled ui-tree-menu collapse'. (!$collapse?' in':'') .'" id="'.$sub_idx.'">';
                                    
                                    /* XXX descartar
                                    $tmp = array_merge($options,[
                                                'Xsub_icon_def'=>$icon_def,
                                                'sub_icon_color_def'=>$icon_color_def,
                                            ]);*/
                                    $tmp = $options;
                                    if($icon_def)$tmp['sub_icon_def']=$icon_def;
                                    if($icon_color_def)$tmp['sub_icon_color_def']=$icon_color_def;
                                    awTreeComponent_createItem($sub,$tmp,$level+1);
                                    echo '</ul>';
                                }

                        echo'</li>';
                    }
                }else if($menu_item=='sep'){
                    echo '<span class="ui-tree-tbline"></span><li data-level="'. $level .'" class="ui-tree-divider"></li>';

                }else{
                
                    //configuração pai
                    $icon = $options['icon_def'];
                    $icon_color = null;
                    if(!empty($options['sub_icon_def']))$icon = $options['sub_icon_def'];
                    if(!empty($options['sub_icon_color_def']))$icon_color = $options['sub_icon_color_def'];
                    
                    
                    echo '<li data-level="'. $level .'" '. ($options['select']==$menu_id?' data-select-ref="load" class="ui-tree-li-selected"':'') .'>'.
                            '<span class="ui-tree-tbline"></span>'.
                            '<div class="ui-tree-item">'.
                                '<a href="#" data-id="'.$menu_id.'" class="'. ($options['select']==$menu_id?'ui-tree-a-selected ':'') . array_get($menu_item,'class','') .'" title="'.array_get($menu_item,'alt').'">'. //effect-left-move
                                    ($options['show_caret']?'<span class="ui-tree-caret">&nbsp;</span>':'').
                                    ($options['show_icon'] ? '<span '. ($icon_color?'style="color:'.$icon_color.';" ':'') .'class="ui-tree-icon fa '. $icon .' margin-r-5"></span>' : '').
                                    '<span class="ui-tree-title">'.$menu_item.'</span>'.
                                '</a>'.
                            '</div>'.
                        '</li>';
                }
            }
        }
    }
}


$class_menu = $class_menu??'';
if(($pos_caret??null)=='right')$class_menu.=' ui-tree-caret-right';
if(($pos_caret_def??null)=='right')$class_menu.=' ui-tree-caret-right-def';

echo '<ul class="ui-tree list-unstyled '.trim($class_menu).'" ui-tree="on" '. (isset($id_menu)?'id="'.$id_menu.'"':'') .'>';
awTreeComponent_createItem($sub,[
    'icon_def'=>$icon_def??'fa-folder',
    'sub_icon_def'=>$sub_icon_def??null,
    'collapse_def'=>$collapse_def??true,
    'show_caret'=>$show_caret??true,
    'show_icon'=>$show_icon??true,
    'select'=>$select??null,
    'link_force'=>$link_force??true,
    'routes'=>$routes??[],
],0);
echo '</ul>';

if(!empty($select)){
    static $aw_tree_var_js_exec=true;
    if($aw_tree_var_js_exec){//executa apenas 1x
        //código js para expandir recursivamente os diretórios
        echo '<script>(function(){
            function awTreeExpandRecursiveX1(){
                var _xf=function(obj){
                    if(!obj)obj=$("li[data-select-ref]");
                    if(obj.length==0)return;
                    obj.each(function(){
                        var o=$(this).closest("ul").prev();//=div.collapsed;
                        if(o.length>0){
                            o.click();
                            _xf(o.parent());//=li
                            //console.log(o[0])
                        }
                    });
                };
                _xf();
            };
            awTreeExpandRecursiveX1();
        }())</script>';
        echo '<script>
        
        </script>';
        $aw_tree_var_js_exec=false;
    }
}
@endphp