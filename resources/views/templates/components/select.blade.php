<div class="form-group form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{!!$label!!}</label>
    @endif

    <div class="control-div {{$class_div ?? ''}}">
      
      @php
      if(!isset($attr))$attr="";
      
      echo '<select name="'.($name??'campo').'" class="form-control '. (isset($class_field)?$class_field:'') .'" data-type="'. ($type??'select').'" '. (isset($id)?'id="'.$id.'"':'') .' data-label="'. ($data_label??$label??'') .'" '.$attr.'>';
      if(isset($list)){
        foreach($list as $val=>$text){
            if(is_array($text)){//grupo
                echo '<optgroup label="'. $val .'">';
                foreach($text as $val2=>$text2){
                    if(isset($autodata) || isset($value)){
                        $sel=(string)$val2===(string)( data_get($autodata??null,$name) ?? $value ?? Form::getValueAttribute($name) ?? null )?'selected':'';
                    }else{
                        $sel='';
                    }
                    echo '<option value="'.$val2.'" '. $sel .'>'. (is_array($text2)?join(' ',$text2):$text2) .'</option>';
                }
                echo '</optgroup>';
                
            }else{//lista normal
                if(isset($autodata) || isset($value)){
                    $sel=(string)$val===(string)( data_get($autodata??null,$name) ?? $value ?? Form::getValueAttribute($name) ?? null )?'selected':'';
                }else{
                    $sel='';
                }
                echo '<option value="'.$val.'" '. $sel .'>'. (is_array($text)?join(' ',$text):$text) .'</option>';
            }
        }
      }
      echo '</select>';
      
      if(!empty($info_html))echo '<div class="control-html">'. $info_html .'</div>';
      
      @endphp
      
      <span class="help-block"></span>
    </div>
</div>

