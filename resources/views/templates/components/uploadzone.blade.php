@php
Form::execFnc('awUploadZone');//seta o nome da função que deve ser inicializada
@endphp
<div class="form-group form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{{$label}}</label>
    @endif
    
    <div class="control-div {{$class_div ?? ''}}">
      <div ui-uploadzone="on" data-opt="{{json_encode(array_filter(array(
                        'title'=>$title??null,
                        'name'=>$name??null,
                        'id'=>$id??null,
                        'class'=>$class??null,
                        'multiple'=>$multiple??null,
                        'accept'=>$accept??null,
                        'height'=>$height??null,
                  )))}}"></div>
      @if(!empty($info_html))'<div class="control-html">'. $info_html .'</div>';@endif
      <span class="help-block"></span>
    </div>
</div>