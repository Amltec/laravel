<div class="form-group form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{!! $label !!}</label>
    @endif
    <div class="control-div {{$class_div ?? ''}}">
      <textarea class="form-control {!!trim($class_field??'')!!}" {!! !empty($id) ? 'id="'.$id.'"':'' !!} {! (isset($resize) && $resize==false)?'style="resize:none;"':'') !} name='{{$name}}' placeholder="{{$placeholder ?? ''}}" rows="{{$rows ?? ''}}" maxlength="{{$maxlength ?? ''}}" {!! ($attr??'') . (isset($auto_height)?'auto-height="'.$auto_height.'" ':'') !!} >{{data_get($autodata,$name) ?? $value ?? Form::getValueAttribute($name) ?? ''}}</textarea>
      @if(!empty($info_html))<div class="control-html">{!! $info_html !!}</div>@endif
      <span class="help-block"></span>
    </div>
</div>

