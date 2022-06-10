@php
$type='colorbox';

//adiciona a classe .select2 no campo select
if(!isset($class_field))$class_field='';
if(!isset($class_div))$class_div='';
$class_div.=' form-group-colorbox aw-colorbox';

$this_value = data_get($autodata,$name) ?? Form::getValueAttribute($name) ?? $value ?? '';

$list = [
    '#880E4F'=>'#880E4F',
    '#A52714'=>'#A52714',
    '#E65100'=>'#E65100',
    '#F9A825'=>'#F9A825',
    '#817717'=>'#817717',
    '#558B2F'=>'#558B2F',
    '#097138'=>'#097138',
    '#006064'=>'#006064',
    '#01579B'=>'#01579B',
    '#1A237E'=>'#1A237E',
    '#673AB7'=>'#673AB7',
    '#4E342E'=>'#4E342E',
    '#C2185B'=>'#C2185B',
    '#FF5252'=>'#FF5252',
    '#F57C00'=>'#F57C00',
    '#FFEA00'=>'#FFEA00',
    '#AFB42B'=>'#AFB42B',
    '#7CB342'=>'#7CB342',
    '#0F9D58'=>'#0F9D58',
    '#0097A7'=>'#0097A7',
    '#0288D1'=>'#0288D1',
    '#3949AB'=>'#3949AB',
    '#9C27B0'=>'#9C27B0',
    '#795548'=>'#795548',
    '#BDBDBD'=>'#BDBDBD',
    '#757575'=>'#757575',
    '#424242'=>'#424242',
    '#000000'=>'#000000',
];



echo'<div class="form-group form-group-'.$name.' '. ($class_group ?? '') .'" id="form-group-'. $name .'">';
    if(!empty($label))echo '<label '. (!empty($id??null) ? 'for="'.$id.'"':'') .' class="control-label '. ($class_label ?? '') .'">'. $label .'</label>';
    echo'
    <div class="control-div '.($class_div ?? '').'" style="padding-top:7px;" >
        <input type="hidden" name="'. $name .'" value="'. $this_value .'" data-type="colorbox" >';
   
    if($none??true){//opção nenhum/vazio
        echo '<div class="aw-colorbox-item'. (empty($this_value??null)?' select':'') .'" data-value="" style="outline:2px solid #ccc;"><div style="background:#fff;color:#999;">'.
                     '<span class="fa fa-close"></span>'.
                  '</div></div>';
    }
   
    if($list){
        foreach(\App\Utilities\ColorsUtility::$colors as $clr_id=>$clr){//array $clr: 0 bg, 1 text
             if(!$clr)continue;
             if(!is_array($clr))$clr = explode('|',$clr);//0 bg, 1 text
             $bg = $clr[0];
             $text = $clr[1]??'#fff';
             echo '<div class="aw-colorbox-item'. ($clr_id==$this_value?' select':'') .'" data-value="'. $clr_id .'"><div style="background-color:'.$bg.';color:'.$text.';">'.
                     '<span class="fa fa-check hiddenx"></span>'.
                  '</div></div>';
        }
    }else{
        echo 'Lista não informada';
    }
    echo'<span class="help-block"></span>
    </div>
</div>';



static $load_js=false;
if(!$load_js){
$load_js=true;
@endphp
<script>
/*$('[data-type2=colorbox]').data('data-select',{templateResult: formatState});;
function formatState(state){
    if(!state.id)return state.text;
    var cbg=state.text.split('|');
    var ctx=cbg[1]??'#fff';
    var text=cbg[2]??'Texto';
        cbg=cbg[0];
    var $state = $('<span class="colorbox-select-item" style="background-color:'+cbg+';color:'+ctx+';">'+text+'</span>');
    return $state;
};*/
</script>
<style>
.colorbox-select-item{width:18px;height:18px;display:inline-block;margin:5px 5px 0 0;text-align:center;border:1px solid #fff;}
.colorbox-select-item:hover{border:1px solid #000;}
</style>
@php
}
@endphp