@php
use App\Utilities\HtmlUtility;

    if(!function_exists('awMenuComponent_createItem')){
        function awMenuComponent_createItem($arr_menu,$is_right){
            if(is_callable($arr_menu))$arr_menu=callstr($arr_menu,true);
            
            if(isset($arr_menu) && is_array($arr_menu)){
                foreach($arr_menu as $menu_id=>$menu_item){
                    if(is_array($menu_item)){
                        if(array_get($menu_item,'header')===true){
                            echo'<li class="dropdown-header">'.array_get($menu_item,'title','Cabeçalho').'</li>';

                        }else{
                            $title = array_get($menu_item,'title','Menu');
                            $icon = array_get($menu_item,'icon');
                            $link = array_get($menu_item,'link','#');
                            $attr = array_get($menu_item,'attr','');
                            $html = array_get($menu_item,'html','');
                            $onclick = array_get($menu_item,'onclick','');
                            
                            $sub = array_get($menu_item,'sub',null);
                            if(is_callable($sub))$sub=callstr($sub,true);
                            if(!is_array($sub))$sub=null;
                            
                            
                            $is_checkbox = array_get($menu_item,'checkbox')===true;
                            
                            echo'<li class="'. array_get($menu_item,'class_li') . ($sub?' dropdown-submenu '. ($is_right?'pull-left ':'') :'') .'">';
                                    
                                    if($title!==false){
                                        $onclick = ($is_checkbox?'var c=$(this).find(\'>:checkbox\');c.prop(\'checked\',!c.prop(\'checked\'));window.event.stopPropagation();window.event.preventDefault();':'') . $onclick;
                                        
                                        echo '<a href="'. $link .'" '. HtmlUtility::targetHref($link)  .' data-id="'.$menu_id.'" title="'.array_get($menu_item,'alt').'"  class="dropdown-menu-item '. array_get($menu_item,'class','') .'" '.  ($onclick?'onclick="'. $onclick .'"':'')  .' '. $attr .'>';

                                            if($is_checkbox){
                                                echo '<input type="checkbox" value="'.$menu_id.'" id="'.$menu_id.'" class="no-events" '. (array_get($menu_item,'checked',false)===true?'checked="checked"':'') .' /><span class="checkmark"></span>'.
                                                     $title;
                                            }else{
                                                if(!empty($icon))echo '<span class="fa '.$icon.'"></span>';
                                                echo $title;
                                            }

                                        echo ($sub?'<span class="caret caret-right"></span>':'').
                                        '</a>';
                                    }
                                    
                                    if($html)echo '<div class="dropdown-html">'.$html.'</a>';

                                    if($sub){
                                        echo '<ul class="dropdown-menu">';
                                        awMenuComponent_createItem($sub,$is_right);
                                        echo '</ul>';
                                    }

                            echo'</li>';
                        }
                    }else if($menu_item=='sep'){
                        echo'<li class="divider"></li>';

                    }else{
                        echo '<li><a href="#" data-id="'.$menu_id.'" class="'. array_get($menu_item,'class','#') .'" title="'.array_get($menu_item,'alt').'">'.$menu_item.'</a></li>';
                    }
                }
            }  
        }
    }

    $is_right = (isset($class_menu) ? strpos($class_menu ,'dropdown-menu-right')!==false : false);
    
    echo '<ul class="dropdown-menu '.($class_menu??'').'" '. (isset($id_menu)?'id="'.$id_menu.'"':'') .'>';
    awMenuComponent_createItem($sub,$is_right);
    echo '</ul>';
@endphp