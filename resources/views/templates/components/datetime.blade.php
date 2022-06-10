@php
use  App\Utilities\FormatUtility;
if(!isset($attr))$attr='';
if(isset($picker) && $picker===true){
    Form::loadScript('datepicker');
    Form::loadScript('timepicker');
    $attr.=' data-picker="on" ';
}
$val = data_get($autodata,$name) ?? $value ?? Form::getValueAttribute($name) ?? '';//formato: dd/mm/aaaa hh:mm:ss

if(strpos($val,'-')!==false){//está no formato aaa-mm-dd
    $val = FormatUtility::dateFormat($val);//altera para dd/mm/aaaa
}
if($val!=''){
    //$val_d = FormatUtility::dateFormat(array_get(explode(' ',$val),0));
    $val_d = array_get(explode(' ',$val),0);
    $val_t = array_get(explode(' ',$val),1);
}else{
    $val_d = '';
    $val_t = '';
}


Form::loadScript('inputmask');

static $loadscript = false;

@endphp

@if(!$loadscript)
<script>
function componenteDateTimeUpd(field){
    var p=$(field).parent();
    p.find('input:eq(0)').val(p.find('[data-type=date]').val()+' '+p.find('[data-type=time]').val());
}
</script>
@endif
<div class="form-group form-group-type-datetime form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{{$label}}</label>
    @endif
    
    <div class="control-div {{$class_div ?? ''}}">
        <div class="form-control">
            <input type="hidden" name="{{$name}}" {!! !empty($id) ? 'id="'.$id.'"':'' !!} value="{{FormatUtility::dateFormat($val)}}">
            <input style="width:90px;" data-type="date" onchange="componenteDateTimeUpd(this);" onblur="componenteDateTimeUpd(this);"  type="text" class="inlineblock form-control-in {{$class_field ?? ''}}" {!! !empty($id) ? 'id="'.$id.'_date"':'' !!} name='{{$name}}__date' maxlength="10" value="{{$val_d}}" data-mask="99/99/99999" placeholder="dd/mm/aaaa" {!!$attr ?? ''!!} >
            <input style="width:75px;" data-type="time" onchange="componenteDateTimeUpd(this);" onblur="componenteDateTimeUpd(this);" type="text" class="inlineblock form-control-in {{$class_field ?? ''}}" {!! !empty($id) ? 'id="'.$id.'_time"':'' !!} name='{{$name}}__time' maxlength="8" value="{{$val_t}}" data-mask="99:99:99" placeholder="hh:mm:ss">
            <span class="help-block" style="margin-top:3px;"></span>
        </div>
    </div>
</div>

