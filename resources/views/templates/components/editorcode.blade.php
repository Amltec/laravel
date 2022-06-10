@php

Form::loadScript('codemirror');
$editor_opt=[];


//*** height ***
    $editor_opt['height']=$height ?? 300;
    if($auto_height??false){
        if(is_int($auto_height))$editor_opt['max_height'] = $auto_height;
        $editor_opt['auto_height'] = true;
    }


//*** theme dark ***
if($theme_dark??false){
    Form::loadScript('codemirror-addons',['css'=>'/plugins/codemirror/5.61.1/theme/monokai.min.css']);
    $editor_opt['theme']='monokai';
}


//*** mentions ***
if($mention??false){
    $editor_opt['mention']=$mention;
}


//*** editor mode ***
$editor_mode=$editor_mode??'html';
$editor_opt['editor_mode']=$editor_mode;
$all_js=[];
if($editor_mode=='css'){
    $all_js['css']='/plugins/codemirror/5.61.1/mode/css.min.js';
    
}elseif($editor_mode=='js'){
    $all_js['javascript']='/plugins/codemirror/5.61.1/mode/javascript.min.js';
    
}elseif($editor_mode=='markdown'){
    $all_js['overlay']='/plugins/codemirror/5.61.1/mode/overlay.min.js';
    $all_js['xml']='/plugins/codemirror/5.61.1/mode/xml.min.js';
    $all_js['markdown']='/plugins/codemirror/5.61.1/mode/markdown.min.js';
    $all_js['gfm']='/plugins/codemirror/5.61.1/mode/gfm.min.js';
    $all_js['javascript']='/plugins/codemirror/5.61.1/mode/javascript.min.js';
    $all_js['css']='/plugins/codemirror/5.61.1/mode/css.min.js';
    $all_js['js']='/plugins/codemirror/5.61.1/mode/htmlmixed.min.js';
    
}else{//html
    $all_js['javascript']='/plugins/codemirror/5.61.1/mode/javascript.min.js';
    $all_js['css']='/plugins/codemirror/5.61.1/mode/css.min.js';
    $all_js['vbscript']='/plugins/codemirror/5.61.1/mode/vbscript.min.js';
    $all_js['xml']='/plugins/codemirror/5.61.1/mode/xml.min.js';
    $all_js['js']='/plugins/codemirror/5.61.1/mode/htmlmixed.min.js';
}
//dd($all_js);
Form::loadScript('codemirror-mode-'.$editor_mode, ['js'=>$all_js]);


//*** salva com ctrl+s ***
$editor_opt['save_key']=$save_key??true;



//*** toolbar ***
    $toolbar = $toolbar??true;
    if($toolbar===true)$toolbar=[true];
    if(!is_array($toolbar))$toolbar=[];

    //verifica os botões ativos
    $buttons_def=['filemanager'=>false,'fullscreen'=>false,'textwrap'=>false];
    $r=[];
    foreach($toolbar as $tbid => $tb){
        if($tb===true){
            //$r[] = array_map($buttons_def,function($value) {$value=true;});//seta true em toda a array
            foreach($buttons_def as $f=>$v){
                $r[$f]=true;
            }
        }elseif(is_string($tb) && isset($buttons_def[$tb]) ){
            $r[$tb]=true;
        }elseif(is_array($tb)){
            $r[$tbid]=$tb;
        }elseif(is_callable($tb)){
            $r[$tbid]=callstr($tb,null,true);
        }elseif($tb instanceof \Illuminate\View\View){//é uma view
            $r[$tbid]=$tb;
        }
    }
    $toolbar=$r;

    if($toolbar['filemanager']??false){
        if(!isset($filemanager))$filemanager=true;
        if(!is_array($filemanager))$filemanager=['controller'=>'files'];
        $json = trim(trim(json_encode($filemanager),'}'),'{');
        $toolbar['filemanager'] = ['title'=>false,'alt'=>'Arquivo','color'=>'link','icon'=>'fa-image','attr'=>'data-editor-cmd="filemanager" data-filemanager=\'{'. $json .'}\''];
    }

    if($toolbar['textwrap']??false)    $toolbar['textwrap'] = ['title'=>false,'alt'=>'Quebrar linhas no editor','color'=>'link','icon'=>'fa-align-left','attr'=>'data-editor-cmd="textwrap"'];
    if($toolbar['fullscreen']??false)  $toolbar['fullscreen'] = ['title'=>false,'alt'=>'Tela cheia','color'=>'link','icon'=>'fa-arrows-alt','attr'=>'data-editor-cmd="fullscreen"'];


    
@endphp
<div class="form-group form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}'>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{!!$label!!}</label>
    @endif

    <div class="control-div {{$class_div ?? ''}}" >
        <div class="editorcode-wrap">
        @php
            if($toolbar){
                echo '<div class="editorcode-toolbar">';
                foreach($toolbar as $bt){
                    if(is_array($bt)){
                        echo view('templates.components.button',$bt);
                    }else{
                        echo $bt;
                    }
                }
                echo '</div>';
            }
            
            echo '<div class="editorcode-edit">';
                echo '<textarea data-type="editorcode" data-plugin-js="editorcode" data-editor-opt=\''. json_encode($editor_opt) .'\' class="form-control '. ($class_field ?? '') .'" '. (!empty($id) ? 'id="'.$id.'"':'') .' name="'.$name.'" '. ($attr??'') .' >'. (data_get($autodata??null,$name) ?? $value ?? Form::getValueAttribute($name)) .'</textarea>';
                if(!empty($info_html))echo '<div class="control-html">'. $info_html .'</div>';
                echo '<span class="help-block" style="'. (($html_pre??false)?'right:0;':'') .'"></span>';
            echo '</div>';
            
            if($html_pre??false){
                echo '<label class="nostrong"><input type="checkbox" name="'.$name.'_html_pre" data-name="html_pre" value="s" '. ( (data_get($autodata??null,$name.'_html_pre') ?? $value_pre ?? Form::getValueAttribute($name.'_html_pre')) ?'checked':'') .'><span class="checkmark"></span> Adicionar parágrafos automaticamente</label>';
            }
            
        @endphp
        </div>
        
        
    </div>
</div>
