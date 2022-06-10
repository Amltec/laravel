@php
    //carrega os arquivos js,css select2
    Form::loadScript('select2');
    
    //adiciona a classe .select2 no campo select
    if(!isset($class_field))$class_field='';
    if(!isset($class_div))$class_div='';
    $class_field.=' select2';
    $class_div.=' form-group-field-select2';
    
    
    //ajax
    if(isset($ajax_url)){
        if(!isset($attr))$attr="";
        $attr.='data-ajax-url="'.$ajax_url.'" ';
    }
    
    $type='select2';
@endphp
@include('templates.components.select')