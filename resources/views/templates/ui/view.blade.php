@php
use \App\Utilities\FormatUtility;
use \App\Utilities\FunctionsUtility;
use \App\Utilities\HtmlUtility;

if(!function_exists('awViewUI_fncx01')){
function awViewUI_fncx01($superdata){
extract($superdata);

if(!isset($data) && !isset($model))return;
$data_type = $data_type??'default';
$hide_title = $hide_title??false;
$model = $model??null;
$arrange = explode('-',($arrange??'2-10').'-');//0 col field, 1 col label
if($arrange[0]=='line')$arrange[0]="";
$filter=$filter??false;


$class_field=$class_field??'';
$class_value=$class_value??'';
$class_f_m='';
$class_v_m='';
if($arrange[0]!='' && $arrange[0]!='12')$class_f_m.='col-sm-'.$arrange[0];
if($arrange[1]!='' && $arrange[1]!='12')$class_v_m.='col-sm-'.($hide_title?'12':$arrange[1]);


//object model com a origem dos dados
if($model){
    //autaliza o parâmetro data
    if(empty($data))$data=[];
    foreach($model->attributesToArray() as $field=>$value){
        if(!isset($data[$field]))$data[$field]=[];
        if(!isset($data[$field]['title']))$data[$field]['title']=$field;
        $data[$field]['value']=$value;
    }
    $model=null;
}
//object stdclass or model
else if(is_object($data)){
    if($data_type=='model'){
        $data=$data->attributesToArray();
    }else{
        $data=(array)$data;
    }
    $data_type='array';
}


//filtra os dados
if($filter){
    $tmp=$data;$data=[];
    foreach($tmp as $k=>$v){
        if($filter=='not_empty'){
            if(!empty($k))$data[$k]=$v;
        }else{
            if(in_array($k,$filter))$data[$k]=$v;
        }
    }
    unset($tmp);
}


//para células do tipo array, object, etc
$sub_class='';
if(isset($class)){
    if(strpos($class,'view-bordered')!==false)$sub_class.='view-bordered ';
    if(strpos($class,'view-condensed')!==false)$sub_class.='view-condensed ';
    //if(strpos($class,'view-large')!==false)$sub_class.='view-large ';//analisar, acho que não precisa
    if($sub_class)$sub_class=trim($sub_class);
}

echo '<div class="ui-view margin-bottom '. ($class??'') .'" '. ($attr??'') .'>';
foreach($data as $field=>$val){
    $this_sub_class = $sub_class;
    
    if(is_array($val) && !is_callable($val['value']??false)){
        //'format' do parâmetro $val
        if(isset($val['format']) && is_callable($val['format']) && isset($val['value'])){
            $val['value']=call_user_func($val['format'],$val['value']);
        }
        
        //'format' do parâmetro global
        if(($format??false) && is_callable($format) && isset($val['value'])){
            $val['value']=call_user_func($format,$val['value']);
        }
    }
    
    if($data_type=='array'){
        echo '<div class="ui-view-row ui-view-content-array clearfix no-padd-left '. ($class_row??'') .'" data-name="'.$field.'">';
                if(!$hide_title)echo '<div class="ui-view-field '.$class_field .' '. $class_f_m .'">'. $field .'</div>';
                echo '<div class="ui-view-value '. ($class_value .' '.$class_v_m)  .'">';
                    
                    if(is_array($val) && is_callable($val['value']??null)){
                            //é uma função e portanto executa-a
                            //echo 'closure(){}';
                            echo callstr($val['value'],null,true);
                            
                    }else{
                            //dump([$data_type,$val]);
                            if(is_object($val)){
                                if(method_exists($val,'toArray')){
                                    $val=$val->toArray();
                                }else{
                                    $val=(array)$val;
                                }
                            }
                            if(is_array($val)){
                                echo awViewUI_fncx01(['data'=>$val,'data_type'=>$data_type,'hide_title'=>$hide_title,'class'=>$this_sub_class]);
                            }else{
                                echo callstr($val,null,true);
                            }
                    }
                echo '</div>';
        echo '</div>';
        
        
    }else if(is_array($val)){
        
        $type = $val['type']??null;
        
        if(isset($val['value'])){
            //verifica e modifica os valores para serem compatíveis com os formaos mais abaixo (do comando switch($type))
            if($type=='taxonomy'){
                //*** monta um padrão considerando $val como array de taxonomias (tabela terms e taxs) ***
                //captura o termo
                if(array_get($val,'title')===true){
                    $term=null;
                    foreach($val['value'] as $tx_id=>$tx_data){$term=$tx_data->term;break;} //captura o termo
                    if($term){//captura o nome do termo
                        $val['title']=$term->term_title;
                    }
                }
                //monta o value
                $val['value']=function() use($val){
                    foreach($val['value'] as $tx_id=>$tx_data){
                        echo view('templates.components.tag_item',['model'=>$tx_data]);
                    }
                };
                $type='string';
            }
        }
        
        
        $attr=$val['attr']??''; if(is_array($attr))$attr=HtmlUtility::buildAttributes($attr);
        if(isset($val['id']) && $val['id'])$attr.=' id="'. $val['id'] .'"';
        $is_title = ($hide_title===true ? false : isset($val['title']) && $val['title']!==false);
        
        echo '<div title="'. ($val['alt']??'') .'"  class="ui-view-row ui-view-content-'.($type?$type:'default').' clearfix no-padd-left '.  trim( ($class_row??'') .' '. ($val['class_row']??'') ) .'" data-name="'.$field.'" '. trim($attr) .' >';
            if($is_title)echo '<div class="ui-view-field '. trim($class_field .' '.$class_f_m.' '.($val['class_field']??'')) .'">'. $val['title'] .'</div>';
            echo '<div class="ui-view-value '.  trim($class_value.' '.  ($is_title?$class_v_m:'col-sm-12')  .' '.($val['class_value']??''))  .'">';
            
            $value = callstr($val['value']??null,null,true);//a var $value pode conter uma função com 'echo' e por isto precisa estar nesta posição
            if($value!=''){
                
                $attr_value=$val['attr_value']??''; if(is_array($attr_value))$attr_value=HtmlUtility::buildAttributes($attr_value);
                
                switch($type){
                case 'img':
                    echo '<img title="Duplo click para ampliar" src="'.$value.'" '.$attr_value.' ondblclick="window.open(\''.$value.'\');">'; break;
                    
                case 'video': case 'youtube':
                    if(strpos($attr_value,'width')===false)$attr_value='width="560" height="315" ';
                    $yb_id = FunctionsUtility::getIdYoutube($value);
                    if($yb_id){//youtube
                        echo '<iframe src="https://www.youtube.com/embed/'.$yb_id.'?rel=0&amp;controls=0" frameborder="0" allowfullscreen '.$attr_value.'></iframe>';
                    }else if(strpos($value,'&')!==false){//url
                        echo '<embed src="'.$value.'" '.$attr_value.' autostart="off" />';
                    }else{//restante
                        echo '<video src="'.$value.'" controls="controls" '.$attr_value.'>Your browser does not support the HTML5 Video element.</video>';
                    }
                    break;
                    
                case 'audio':
                    echo '<audio controls="controls" src="'.$value.'">Your browser does not support the HTML5 Audio element.</audio>';
                    break;
                    
                case 'iframe':
                    if(strpos($attr_value,'width')===false)$attr_value='width="100%" height="200" ';
                    echo '<iframe src="'.$value.'" '.$attr_value.'></iframe>'; break;
                    
                case 'date': case 'time': case 'dateauto': case 'datetime':
                    $d=$type=='dateauto'?'auto': ($type=='datetime'?'':$type);
                    echo FormatUtility::dateFormat($value, $d ); break;
                    
                case 'number': case 'price':
                    echo ($type=='price'?'R$ ':'') . FormatUtility::numberFormat($value); break;
                    
                case 'link': case 'file':
                    if($type=='file'){
                        $n=basename($value);
                    }else{
                        $n=str_ireplace(['http://','https://'],['',''],$value);
                    }
                    echo '<a title="'. ($val['alt']??'') .'" href="'. (substr(strtolower($value),0,4)!=='http'? 'http://' : '') . $value .'" target="_blank" title="'. $value .'">'.$n.'</a>'; break;
                    
                case 'bytes':
                    echo FormatUtility::bytesFormat($value); break;
                
                    
                case 'boolean':
                    echo $value?'Verdadeiro':'Falso'; break;
                    
                case 'sn':
                    echo $value?'Sim':'Não'; break;
                
                case 'array': case 'object':
                    
                    if($type=='object')$value=(array)$value;
                    echo awViewUI_fncx01(['data'=>$value,'data_type'=>'array','hide_title'=>$val['hide_title']??$hide_title,'class'=>$this_sub_class]);
                    break;
                
                case 'model':
                    $value = $value->attributesToArray();
                    echo awViewUI_fncx01(['data'=>$value,'data_type'=>'array','hide_title'=>$val['hide_title']??$hide_title,'class'=>$this_sub_class]);
                    break;
                
                case 'dump':
                    dump($value);
                    break;
                    
                default;
                    if(substr($type,0,1)=='@'){//include view
                        $type = substr($type.'*',1,-1);
                        if(view()->exists($type)){
                            echo view($type,$value);
                        }else{
                            echo 'Erro ao carregar view '.$type;
                        }
                    }else if(is_array($value) || is_object($value)){
                        if(is_object($value)){
                            if(method_exists($value,'toArray')){
                                $value=$value->toArray();
                            }else{
                                $value=(array)$value;
                            }
                        }
                        echo awViewUI_fncx01(['data'=>$value,'data_type'=>'array','hide_title'=>$hide_title,'class'=>$this_sub_class]);
                        
                        
                    }else{
                        echo $value;
                    }
                }
            }
                
            echo '</div>';
        echo '</div>';
        
    }else{
        echo '<div class="ui-view-row '. ($class_row??'') .'" data-name="'.$field.'" >'.
                '<div class="ui-view-value '. $class_value .'">';
                if(is_callable($val)){
                    echo call_user_func($val);
                }else{
                    echo $val;
                }
        echo    '</div>'.
            '</div>';
    }
    
}

echo'</div>';

}//end function
}//end if

awViewUI_fncx01(get_defined_vars());

@endphp