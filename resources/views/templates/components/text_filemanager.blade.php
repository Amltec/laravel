@php

$arr = get_defined_vars();

if(!isset($filemanager_param))$filemanager_param=[];
if(!isset($filemanager_return))$filemanager_return='urls';

$filemanager_param['param_cb']='#!!$(this).parent().prev()!!#';

$n='function(opt){'.
    'var o=opt.param_cb;';
    if($filemanager_return=='ids'){
        $n.='o.val(opt.ids);';
    }else if($filemanager_return=='urls'){
        $n.='o.val(opt.urls);';
    }else{//function
        $n.='o.val(callfnc(#!QTS'.$filemanager_return.'QTS!#,opt));';
    }
$n.='}';
$filemanager_param['onSelectFile']='#!!'.$n.'!!#';

$json=json_encode($filemanager_param);
$json = str_replace(['"#!!','!!#"'],['',''],$json);
$json = str_replace(['#!QTS','QTS!#'],['"','"'],$json);


$arr['button']=array_replace_recursive(
    ['title'=>false,'alt'=>'Selecionar arquivos','color'=>'primary','icon'=>'fa-files-o'],
    ($button??[])
);

$arr['button']['onclick']='awFilemanager('. $json .');';
if(!isset($arr['attr']))$arr['attr']='';
$arr['attr'].='onclick="$(this).next().find(\'button:eq(0)\').click()" style="cursor:default;" onkeydown="if($(this).prop(\'readonly\'))$(this).val(\'\');" ';

if(($type2??null)=='hidden')$arr['type']='hidden';

echo view('templates.components.text',$arr);

@endphp