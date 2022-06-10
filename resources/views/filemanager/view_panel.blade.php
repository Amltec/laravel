@include('templates.ui.files_view',[
    'controller'=>$controller,
    'file'=>$file,
    //'onRemove'=>'@function(r){ console.log(r);alert(r.action);window.location.reload(); }',
    //'fields'=>'title,size,link' 
    'view_params'=>[
        'class'=>'view-fields-line',
        'class_field'=>'text-muted',
        'class_value'=>'no-padd-top',
        'arrange'=>'',
    ],
    'bt_remove'=>false
])
