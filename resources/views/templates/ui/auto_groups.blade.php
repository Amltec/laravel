@php

if(!isset($autogroups))$autogroups=null;
if(!isset($autofields))$autofields=null;
if(!isset($form))$form=[];

$form['content']=function() use($autogroups,$autofields){
        if(!empty($autofields)){
            echo view ('templates.ui.auto_fields',$autofields);
        }
        if(!empty($autogroups)){
            foreach($autogroups as $autogroup_inc=>$autogroup_opt){
                if(is_callable($autogroup_opt)){
                    echo callstr($autogroup_opt,null,true);
                }else if(gettype($autogroup_inc)=='integer' && gettype($autogroup_opt)=='string'){//html normal
                    echo $autogroup_opt;
                }else{
                    echo view(gettype($autogroup_inc)=='string'?$autogroup_inc:'templates.ui.auto_fields', $autogroup_opt);
                }
            }
        }
    };


if(!isset($metabox) || empty($metabox))$metabox=false;
if($metabox){
    $_vars = get_defined_vars();
    if(!is_array($metabox))$metabox=[];
    $metabox['content']=function() use($_vars){
            extract($_vars);
            echo view('templates.ui.form',$form);
        };
    echo view('templates.components.metabox',$metabox);
}else{
    echo view('templates.ui.form',$form);
}

@endphp