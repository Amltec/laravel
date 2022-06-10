<div class="form-group form-group-type-email-mark form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{{$label}}</label>
    @endif

    <div class="control-div {{$class_div ?? ''}}">
      <span class="{{ isset($is_mark) && $is_mark==true?'form-group-fields-col-a1':''  }}">
          <input type="email" data-type="email" class="form-control {{$class_field ?? ''}}" {!! !empty($id) ? 'id="'.$id.'_email"':'' !!} name='{{$name}}_email' placeholder="E-mail" maxlength="{{$maxlength ?? '150'}}" value="{{ data_get($autodata,$name.'_email') ?? $value ?? Form::getValueAttribute($name.'_email') ?? '' }}" autocomplete="no" {!!$attr ?? ''!!} >
          <span class="help-block"></span>
      </span>
      
        
      @if($is_mark??false)
      <span class="form-group-fields-col-a2">
            <select data-type="email_mark" class="form-control select {{$class_field ?? ''}}" {!! !empty($id) ? 'id="'.$id.'_mark"':'' !!} name='{{$name}}_mark' {!!$attr ?? ''!!} onchange="if(this.value=='_other')$(this).hide().next().show().focus();">
                <option disabled="disabled" selected>Marcador</option>
                <option value="_blank_"></option>
                  @php
                    $arr=['Comercial','Financeiro','Suporte Técnico','Casa','Trabalho','Particular'];
                    $val=( data_get($autodata,$name.'_mark') ?? $value_mark ?? Form::getValueAttribute($name.'_mark') ?? '' );
                    if($val!='')if(!in_array($val,$arr))$arr[]=$val;
                    foreach($arr as $opt_name){
                        echo '<option value="'.$opt_name.'" '. ($val==$opt_name?'selected':'') .' >'.$opt_name.'</option>';
                    }
                  @endphp
                <option disabled="disabled"></option>
                <option value="_other">Outros</option>
            </select>
          <input type="text" placeholder="Marcador" class="form-control select {{$class_field ?? ''}}" style="display:none;" {!! !empty($id) ? 'id="'.$id.'_mark_other"':'' !!} name='{{$name}}_mark_other' {!!$attr ?? ''!!} onblur="if($.trim(this.value)=='')$(this).hide().prev().show().find('option:eq(0)').prop('selected',true);" >
           <span class="help-block"></span>
      </span>
      @endif
      
      
      @if(!empty($info_html))'<div class="control-html">'. $info_html .'</div>';@endif
      
    </div>
</div>

