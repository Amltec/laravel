@php
echo '<div class="form-group form-group-'.$name.' '. ($class_group ?? '') .'" id="form-group-'. $name .'" '. ($attr??'') .'>';
    if(!empty($label))echo '<label '. (!empty($id??null) ? 'for="'.$id.'"':'') .' class="control-label '. ($class_label ?? '') .'">'. $label .'</label>';
    echo'<div class="control-div '.($class_div ?? '')  .  (empty($label)?'col-sm-10':'') .'">';
        echo callstr($html);
        echo'<span class="help-block"></span>
    </div>';
    
echo '</div>';
@endphp
