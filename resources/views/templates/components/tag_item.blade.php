@php
use App\Utilities\ColorsUtility;

$opt = array_merge([
    'id'=>'',
    'title'=>'Tag',
    'link'=>null,
    'class'=>'',
    'color'=>'',
    'icon'=>null,
    'attr'=>'',
    'type'=>'badge',
    'btClose'=>false,
    'confirmClose'=>false,
    'term_id'=>null,
    'tax_id'=>null,
],(array_filter($opt??[])));

$attr=$opt['attr']??'';unset($opt['attr']);

if(isset($model)){
    $opt['tax_id']=$model->id;
    $opt['term_id']=$model->term_id;
    $opt['title']=$model->tax_title;
    $attr='title="Id '.$model->id.' '. ($model->tax_description ? htmlspecialchars($model->tax_description) : '') .'" ';
    $opt['color']=$model->tax_options['color'];
    $opt['icon']=$model->tax_options['icon'];
}
$opt['color']=ColorsUtility::getColor($opt['color']);


$c=strtolower($opt['color']);
$cls='margin-r-5 ';
$style='';
if($opt['type']=='badge'){$cls.='badge ';}else{$cls.='btn btn-xs ';};

if(!$c)$c='gray';//default
if(in_array($c,['green','yellow','teal','aqua','red','purple','maroon','navy','olive','black','gray'])){//classe padrão de cor
    $cls.='bg-'.$c.' ';
}else if(substr($c,0,1)=='#' || substr($c,0,3)=='rgb'){//cor hex ou rgb
    $style.='background-color:'.$c;
};

if(!$opt['link'])$cls.='cursor-default '. (!$opt['btClose']?'no-events ':'') ;
$cls.=$opt['class'];

if($opt['term_id'])$attr.=' data-term_id="'.$opt['term_id'].'" ';
if($opt['tax_id'])$attr.=' data-tax_id="'.$opt['tax_id'].'" ';
$attr.='data-id="'. ($opt['id']?$opt['id']:$opt['tax_id']) .'" ';//será sempre igual a tax_id


if(isset($events) && $events){
    $attr.='ui-tagitem="on" data-opt=\''. json_encode($opt) .'\' ';
    Form::execFnc('awTagItem_events');//seta o nome da função que deve ser inicializada
}

echo '<a class="ui-tagitem nostrong '.$cls.'" '. ($opt['link']?'href="'.$opt['link'].'"':'') . ($style?'style="'.$style.'"':'') .' '.$attr.'>'.
        '<span class="'. ($opt['link']?'ui-tagitem-hover':'') .'">'.
            ($opt['icon']?'<span class="fa '.$opt['icon'].' margin-r-5" style="font-size:0.8em;"></span>':'').
            $opt['title'].
        '</span>'.
        ($opt['btClose']?'<span title="Fechar" class="ui-tagitem-hover j-close fa fa-close cursor-pointer" style="margin-left:5px;font-size:0.8em;"></span>':'').
    '</a>';

@endphp