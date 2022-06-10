@php

if(empty($attr))$attr='';
$editor_opt=[];
if(!isset($height))$height=300;
if(!isset($toolbar_fixed))$toolbar_fixed=false;

if($template??false){
    $editor_opt['template']=$template;
    if(strpos($template,'short')!==false)$height=100;
}
$editor_opt['height']=$height;
if(isset($filemanager)){
    if(is_array($filemanager)){
        $editor_opt['filemanager']=$filemanager;
    }else{
        //abre nas configurações padrões
        $editor_opt['filemanager']=[];
    }
}

if($auto_height??false){
    $editor_opt['auto_height'] = $auto_height;
}

//adiciona as demais configurações padrões
foreach(['toolbar_fixed'] as $n){
    $editor_opt[$n] = $$n;;
}



$editor_opt=empty($editor_opt)?'':'data-editor-opt=\''.json_encode($editor_opt)."'";



Form::loadScript('jodit');

@endphp

<div class="form-group form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{!!$label!!}</label>
    @endif

    <div class="control-div {{$class_div ?? ''}}" >
        <textarea data-type="editor" data-plugin-js="jodit" class="form-control {{$class_field ?? ''}}" id="{{$id ?? $name ?? "jodit1"}}" {!!$editor_opt!!} name="{{$name}}" data-label="{{$data_label??$label??''}}" {!!$attr!!} >{!! data_get($autodata??null,$name) ?? $value ?? Form::getValueAttribute($name) !!}</textarea>
        @if(!empty($info_html))'<div class="control-html">'. $info_html .'</div>';@endif
        <span class="help-block"></span>
    </div>
</div>