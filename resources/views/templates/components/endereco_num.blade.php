@php
    $name_end = explode('|',$name)[0];
    $name_num = explode('|',$name)[1];
@endphp

<div class="form-group form-group-type-end-num form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{{$label}}</label>
    @endif
    
    <div class="control-div {{$class_div ?? ''}}">
      <span class="form-group-fields-col-a1">
          <input type="text" class="form-control" {!! !empty($id) ? 'id="'.explode('|',$id)[0].'"':'' !!} name='{{$name_end}}' placeholder="{{ isset($placeholder) && $placeholder===true ? 'Endereço' : '' }}" maxlength="100" value="{{ data_get($autodata,$name_end) ?? Form::getValueAttribute($name_end) ?? $value ?? ''   }}" data-type="end" autocomplete="no" data-name="end" >
          <span class="help-block"></span>
      </span>
      <span class="form-group-fields-col-a2">
          <input type="text" class="form-control" {!! !empty($id) ? 'id="'.explode('|',$id)[1].'"':'' !!} name='{{$name_num}}' placeholder="{{ isset($placeholder) && $placeholder===true ? 'Número' : '' }}" maxlength="50" value="{{ data_get($autodata,$name_num) ?? Form::getValueAttribute($name_num) ?? $value ?? ''   }}" data-type="num" autocomplete="no" data-name="num" >
          <span class="help-block"></span>
      </span>
    </div>
</div>

