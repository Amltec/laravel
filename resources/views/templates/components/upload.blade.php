<div class="form-group form-group-{{$name}} {{$class_group ?? ''}} margin-bottom-none" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{{$label}}</label>
    @endif

    <div class="control-div {{$class_div ?? ''}}">
      <div class="btn btn-{{$class_button??'default'}} btn-upload">
         {!! isset($icon) && $icon!==false? '<i class="fa '.$icon.'" style="margin-right:5px;"></i>' : (!isset($icon)?'<i class="fa fa-upload" style="margin-right:5px;"></i>':'') !!} 
         <span>{{$value ?? 'Selecionar arquivo'}}</span>
         @php
             echo '<input type="file" class="form-control '. ($class_field ?? '') .'" '. (!empty($id) ? 'id="'.$id.'"':'') .' name="'.$name.'" accept="'.(isset($accept) && $accept?$accept:'image/*').'" '. (!empty($multiple)?'multiple="multiple"':'') .' '. ($attr ?? '') .'>';
         @endphp
      </div>
      @php
        if(!empty($info_html))echo '<div class="control-html">'. $info_html .'</div>';
      @endphp
      <span class="help-block"></span>
    </div>
</div>