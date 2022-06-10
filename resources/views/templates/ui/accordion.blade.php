@php

if(!is_array($data??null)){echo 'Erro de parâmetro';return;}
$show_arrow = $show_arrow??false;

$tab_active='';
foreach($data as $tab_id => $tab_data){
    if(array_get($tab_data,'active')==true){
        $tab_active=$tab_id;
        break;
    }
}
if(!$tab_active)$tab_active=array_keys($data)[0];
if($default_hide??false)$tab_active='';


$id = $id??uniqid();


echo '<div class="aw-accordion panel-group '. ($class??'') .'" id="accordion_'.$id.'" role="tablist" aria-multiselectable="true" '. ($attr??'') .'>';
    foreach($data as $tab_id => $tab_data){
        $icon = !empty($tab_data['icon'])?'<i class="fa '.$tab_data['icon'].'"></i> ':'';
        $badge = (isset($tab_data['badge']) ?' <span style="font-size:11px;margin-left:3px;" class="badge bg-'.($tab_data['badge_color']??'red').'">'.$tab_data['badge'].'</span> ':'');

        echo'<div class="panel '.($tab_active==$tab_id?'panel-active ':'').'" role="tab" id="heading'.$tab_id.'">';
            
                if(isset($tab_data['right']))echo '<div class="panel-heading-right pull-right" style="padding:8px 15px 0 15px;">', callstr($tab_data['right']??null) ,'</div>';
                
            echo'<div class="panel-heading '. ($tab_active==$tab_id?'':'collapsed ') . ($tab_data['class_li']??'') .'" role="button" data-toggle="collapse" data-parent="#accordion_'.$id.'" href="#collapse'.$tab_id.'" aria-expanded="true" aria-controls="collapse'.$tab_id.'">'.
                    ($show_arrow ? '<div class="pull-right"><i class="aw-accordion-ic-a fa fa-caret-right hiddenx"></i><i class="aw-accordion-ic-b fa  fa-caret-down hiddenx"></i></div>' : '').
                    '<h4 class="panel-title strong">'. $icon . ($tab_data['title']??$tab_id) . $badge.'</h4>'.
                '</div>'.
                '<div id="collapse'.$tab_id.'" class="panel-collapse collapse '.($tab_active==$tab_id?'in ':'') . ($tab_data['class']??'') .'" role="tabpanel" aria-labelledby="heading'.$tab_id.'">'.
                    '<div class="panel-body">';
                        echo callstr($tab_data['content']??null);
                echo'</div>'.
                '</div>'.
            '</div>';
    }
echo '</div>';

@endphp