@php
$name=uniqid();
if(!isset($buttons))return;
@endphp
<div class="form-group form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{{$label}}</label>
    @endif
    <div class="control-div {{$class_div ?? ''}}">
        @php
            echo '<div class="btn-group">';
            foreach($buttons as $button){
                if(!isset($button['type']))$button['type']='button';
                echo view('templates.components.button',$button);
            }
            echo '</div>';
            if(!empty($info_html))echo '<div class="control-html">'. $info_html .'</div>';
        @endphp
    </div>
</div>