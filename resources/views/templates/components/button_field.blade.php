@if(isset($name))
<div class="form-group form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{{$label}}</label>
    @endif

    <div class="control-div {{$class_div ?? ''}}">
      @include('templates.components.button',['type'=>($type=='button_field'?'button':$type)])
      @php
        if(!empty($info_html))echo '<div class="control-html">'. $info_html .'</div>';
      @endphp
      <span class="help-block"></span>
    </div>
</div>
@else
Parâmetro $name não definido.
@endif