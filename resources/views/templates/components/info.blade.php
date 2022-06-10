<div class="form-group {{!empty($id)?'form-group-'.$id:''}} {{$class_group ?? ''}}" {{!empty($id)?'id="form-group-'.$id.'"':''}}>
    @php
    if(!empty($label)){
        echo '<label class="control-label '. ($class_label ?? '') .'">'.$label.'</label>';
    }
    @endphp
    
    <div class="control-info-text control-div padd-form-top {{$class_div ?? ''}}" style="{!! !empty($label)?'':'width:100%;' !!}" id="{{$id??''}}">
        {!! callstr($text)!!}
    </div>
</div>