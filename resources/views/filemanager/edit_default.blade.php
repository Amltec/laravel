@php
/*
Variáveis esperadas:
    $file
    $fields             - (array) title (bool), status (bool), area_name (str), area_id(str|int)
    $controller
    $route_update       - (opcional)
*/


$prefix = Config::adminPrefix();

$params = [
    'action'=>['type'=>'hidden','value'=>'edit'],
    'file_id'=>['type'=>'hidden','value'=>$file->id],
    'area_name'=>['type'=>'hidden','value'=>$fields['area_name']],
    'area_id'=>['type'=>'hidden','value'=>$fields['area_id']],
    'title'=>['label'=>'Título','maxlength'=>255,'value'=>$file->file_title],
    'status'=>['label'=>'Status','type'=>'radio','value'=>($file->relation->status??'a'), 'default'=>'a',
        'list'=>['a'=>'Normal','0'=>'Oculto','c'=>'Cancelado']
    ],
];
//dd($file);
if(!$fields['title'])$params['title']=['label'=>'Arquivo','type'=>'info','text'=>'<strong>'.$file->file_title.'</strong>'];
if(!$fields['status'])unset($params['status']);

echo view('templates.ui.auto_fields',[
    'layout_type'=>'horizontal',
    'form'=>[
        'method'=>'POST',
        'url_action'=> $route_update ?? route($prefix.'.file.post',$controller),
        'data_opt'=>[
            'focus'=>true,
            'onSuccess'=>'@function(r){ awFilesListOnUpdate('. $file->id .',r); }',
            'fields_log'=>false,
        ],
        'bt_save'=>'Atualizar',
        'autodata'=>$file,
    ],
    'autocolumns'=>$params
]);



@endphp
