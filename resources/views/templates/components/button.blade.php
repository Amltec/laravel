@php
    $type = (isset($type) && $type=='upload'?'upload':(($type??'')=='submit'?'submit':''));
    if(!isset($attr)){$attr='';}else{$attr.=' ';}
   
    if($type=='submit'){
        if(empty($color))$color='primary';
        //if(empty($title))$title='Salvar';
        $attr.='type="submit" ';
    };
    
    $color=$color??'default';
    $class='btn'.($color?' btn-'.$color:'').' '.($class??'').' ';
    
    if(isset($size))$class.='btn-'.$size.' ';
    if(isset($alt))$attr.='title="'.$alt.'" ';
    if(isset($id))$attr.='id="'.$id.'" ';
    
    
    if(isset($href)){
        $type='link';//muda o tipo para link
        $attr.='href="'.$href.'" '.(isset($target)?'target="'. ($target===true?'_blank':$target) .'" ':'');
    }
    
    if(isset($post) && is_array($post)){
        $attr.='onclick=\'awBtnPostData('. json_encode($post) .',this)\'';
    }
    
    if(!isset($icon) && $type=='upload')$icon='fa-upload';
    $icon = (isset($icon)?'<span class="fa '.$icon.'" '. (empty($title)?'':'style="margin-{align}:7px;"')  .'></span>':'');
    
    $icon_pos = (isset($icon_pos)?$icon_pos:'left');
    if($icon_pos=='top')$class.='btn-app ';
    $icon=str_replace('{align}', ($icon_pos=='right'?'left':'right') ,$icon);
    
    if(isset($sub) || isset($sub_opt))echo '<div class="btn-group '. ($class_group??'') .'">';
    if(isset($sub)){
        $class.='dropdown-toggle ';
        $attr.='data-toggle="dropdown" ';
    }
    
    if(!empty($name) && $type!='upload')$attr.='name="'.$name.'" ';
    
    if($onclick??false)$attr.='onclick=\''. str_replace('\'','\\\'',$onclick) .'\'';
    
    echo '<'. ($type=='link'?'a':'button type="'. ($type=='submit'?'submit':'button') .'"') .' class="'. trim($class . ($type=='upload'?' btn-upload':'') ).'" '.$attr.' >'.
            ($type=='upload'?'<input type="file" name="'. ($name??'field-upload') .'"  '. (isset($id)?'id="'.$id.'"':'') .'  accept="'. ($accept??'') .'" '. (!empty($multiple)?'multiple="multiple"':'') .'>':'').
        
            ($icon_pos=='left' || $icon_pos=='top' ? '<span class="">'.$icon.'</span>' : '').
            (isset($title) && empty($title) ? '': '<span class="btn-title">'.($title ?? 'Botão').'</span>').
            ($icon_pos=='right' ? ' '.$icon : '').
            (isset($badge) ?' <span class="badge bg-'.($badge_color ?? 'red').'">'.$badge.'</span> ':'').
         '</'. ($type=='link'?'a':'button') .'>';
    
    if(isset($sub_opt))echo '<button type="button" class="btn btn-'. ($color ?? 'default') . (isset($size)?' btn-'.$size:'') .' dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>';
    if(isset($sub) || isset($sub_opt)){
        @endphp
        
        @include('templates.components.menu',['sub'=>(isset($sub) ? $sub : $sub_opt)])
        
        @php
        echo '</div>';
    }
@endphp