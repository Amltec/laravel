@php

if(!is_array($data??null)){echo 'Erro de parâmetro';return;}


$tab_active=$tab_active??'';
foreach($data as $tab_id => $tab_data){
    if($tab_active=='' && array_get($tab_data,'active')==true){
        $tab_active=$tab_id;
        break;
    }
}
if(!$tab_active)$tab_active=array_keys($data)[0];


$nav_id =  isset($id) && $id ? $id : 'navtab_'.uniqid();

echo '<div class="nav-tabs-custom '. ($class??'') .'" '. ($attr??'') .' id="'.$nav_id.'" style="box-shadow:none;">'.
        '<ul class="nav nav-tabs">';
            
           if(isset($title) || isset($icon))echo '<li class="pull-left header">'. (isset($icon)?'<i class="fa '.$icon.'" style="font-size:0.9em;"></i> ':'') . $title.'</li>';
       
    $count=1;
    foreach($data as $tab_id => $tab_data){
        $is_content = ($content??true) && ($tab_data['content']??false) && !isset($tab_data['menu']);
        $is_disabled = $tab_data['disabled']??false;
        
        echo '<li class="tab-item-'.$count.' '. ($tab_id==$tab_active?'active ':'') . (isset($tab_data['menu'])?'dropdown ':'') . ($is_disabled?'disabled ':'') . ($tab_data['class_li']??'') .'" data-id="'.$tab_id.'" '. ($tab_data['attr']??'') .' '. (!$is_content?'data-content="no"':'') .'>';
                $icon = !empty($tab_data['icon'])?'<i class="fa '.$tab_data['icon'].'"></i> ':'';
                $badge = (isset($tab_data['badge']) ?' <span style="font-size:11px;margin-left:3px;" class="badge bg-'.($tab_data['badge_color']??'red').'">'.$tab_data['badge'].'</span> ':'');
                
               if(isset($tab_data['menu'])){
                    echo '<a href="#" data-toggle="dropdown"  class="dropdown-toggle" >'. $icon . ($tab_data['title']??$tab_id) . $badge .' <span class="caret"></span></a>';
                    echo view('templates.components.menu',[
                        'sub'=>$tab_data['menu']
                    ]);
                }else{
                    $link = $tab_data['href']??'';
                    if($link){
                        echo '<a '. ($is_disabled?'':'href="'.$link .'" style="cursor:pointer;"') .'>'. $icon . ($tab_data['title']??$tab_id) . $badge .'</a>';
                    }else{
                        echo '<a href="#'.$tab_id .'" '. ($is_disabled?'onclick="return false;"':'data-toggle="tab" aria-expanded="true"') .'>'. $icon . ($tab_data['title']??$tab_id) . $badge .'</a>';
                    }
                }
                
        echo '</li>';
        $count++;
    }
    echo '</ul>';
    
    if(($content??true)!==false){
        echo '<div class="tab-content '. (($is_content_clean ??false)?'':'ui-shadow') .'">';
              if(is_array($content??null)){
                echo callstr($content[0],[$content[1]]);
              }else{
                echo callstr($content??null);
              }
              foreach($data as $tab_id => $tab_data){
                if($tab_data['content']??false && !isset($tab_data['menu'])){
                    echo '<div class="tab-pane '. ($tab_id==$tab_active?'active ':'') . ($tab_data['class']??'') .'" id="'.$tab_id.'">';
                    if(is_array($tab_data['content']??null)){
                        echo callstr($tab_data['content'][0],[$tab_data['content'][1]]);
                    }else{
                        echo callstr($tab_data['content']??null);
                    }
                    echo '</div>';
                }
              }
        echo '</div>';
    }
echo '</div>';

@endphp

@if($tab_active_js??false)
<script>
(function(){
    var div=$('#{{$nav_id}}').on('click','.nav-tabs > li',function(e){
       if($(this).attr('data-content')=='no'){
           e.stopPropagation();
       }else{
           window.location='#tab:'+$(this).attr('data-id');
       }
    });
    var h=window.location.hash.replace('tab:','').replace('#','');
    if(h){
        var o=div.find('li[data-id="'+ h +'"]>a');
        if(o.length>0)o.click();
    }
}());
</script>
@endif