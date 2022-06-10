<div class="form-group form-group-{{ rtrim($name,'[]') }} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
  @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{!!$label!!}</label>
  @endif


  @php
      if(!isset($type))$type='radio';
      $name2=rtrim($name,'[]');//tira o final os caracteres[] que indicam que os valores deste campo estão no formato array
      $this_value = $this_value ?? data_get($autodata??null,$name2) ?? Form::getValueAttribute($name2) ?? $value ?? null;
      $break_line = (isset($break_line) && $break_line===true?true:false);

      echo '<div class="control-div control-fields-checks '. ($class_div ?? '') .'" '. ($attr ?? '') .' data-type="'.$type.'" data-value-all="'. ($value_all??'') .'">';


      if(!is_array($this_value)){
        $v = (string)$this_value;
        if(empty($v))$v=$default??'';
      }else{
        $v = $this_value;
      }

      foreach($list as $val=>$text){
        $val=(string)$val;
        $class_item = $class_item??'';if(stripos($class_item,'strong')===false)$class_item.=' nostrong';
        echo '<label class="'. trim($class_item) .' margin-r-10"><input type="'.$type.'" ';
             if(is_array($this_value)){
                echo in_array($val,$v)?'checked="checked"':'';
             }else{
                echo $v==$val?'checked="checked"':'';
             }
        echo ' name="'. ($name??'campo') .'" value="'. $val .'" data-label="'. htmlspecialchars($data_label??$label??'') .'"><span class="checkmark '. ($class_field ?? '') .'"></span> '. $text .'</label> ';
        if($break_line)echo '<br>';
      }

      if(!empty($info_html))echo '<div class="control-html">'. $info_html .'</div>';

      echo '<span class="help-block"></span>'.
      '</div>';


  if($value_all??false){//foi setado o campo para selecionar todos
       static $write_fnc = true;//controle para não escrever a função mais de uma vez
       if($write_fnc){
            $write_fnc=false;
            echo "
<script>
setTimeout(function(){
    $('.control-fields-checks[data-value-all]').each(function(){
        var os=$(this).find(':input');
        $(this).find('[value=\'". $value_all ."\']').on('click',function(){
            var o=$(this);
            if(o.prop('checked'))os.prop('checked',true);
        });
    });
},100);
</script>";
       }

  }

  @endphp
</div>
