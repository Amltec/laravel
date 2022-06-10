@php
$autodata=$autodata ?? [];
if(is_object($autodata))$autodata=(array)$autodata;
$autocolumns = $autocolumns??[];
$form_id = $form_id??'filterbar-'.uniqid();
$is_filter = $is_filter??true;
$is_form = $is_form??true;
$class = $class??'';

if($is_filter)$autocolumns['bt'] = ['title'=>false,'alt'=>'Filtrar','class_group'=>'','type'=>'submit','color'=>'info','icon'=>'fa-filter','class'=>'j-btn-submit'];


$css='';
//atualizar a var $autodata caso encontre algum campo de querystring com o mesmo nome (somente se o campo do filtro não existir)
foreach($autocolumns as $col_n => $col_v){
    if(!isset($autodata[$col_n])){
        $v = request()->input($col_n);
        if($v)$autodata[$col_n]=$v;
    }
    
    $w=$autocolumns[$col_n]['width']??null;
    if($w)$css.='#'.$form_id.' .form-group-'.$col_n.'{width:'.$w.'px;}';
}

$params=[
    'metabox'=>$metabox??true,
    'form'=>[
        'id'=>$form_id,
        'class'=>'ui-toolbar '.$class,
        'url_action'=>'#',
        'alert'=>false,
        'data_opt'=>[
            'fields_log'=>false,//desativa os campos de log
        ],
    ],
    'autodata'=>(object)$autodata,
    'autocolumns'=>$autocolumns
];
if(!$is_form)unset($params['form']);
echo view('templates.ui.auto_fields',$params);

if($css)echo '<style>'.$css.'</style>';

if($is_filter)echo '<script>awFilterBar("#'. $form_id .'");</script>';
@endphp 
