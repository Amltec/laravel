@php
use App\Utilities\FormatUtility;

    if(!isset($attr))     $attr='';
    if(!isset($type))     $type='text';
    $type2=$type;
    
    $this_value = data_get($autodata??null,$name) ?? Form::getValueAttribute($name) ?? $value ?? '';
    
    if($type=='date'){
        //verifica se o value está como aaaa-mm-dd e altera para dd/mm/aaaa
        if(strpos($this_value,'-')!==false)$this_value=FormatUtility::dateFormat($this_value,'d/m/Y');
        
        $attr.='data-mask="99/99/9999" ';
        if(isset($picker) && $picker===true){
            Form::loadScript('datepicker');
            $attr.='data-picker="on" ';
        }
        
    }else if($type=='time'){
        $attr.='data-mask="99:99:99" ';
        /*if(isset($picker) && $picker===true){
            Form::loadScript('timepicker');
            $attr.='data-picker="on" ';
        }*/
        
    }else if($type=='daterange'){
        $attr.='data-mask="99/99/9999 - 99/99/9999" ';
        if(isset($picker) && $picker===true){
            Form::loadScript('daterangepicker');
            $attr.='data-picker="on" ';
        }
        
    }else if($type=='color'){
        $type='text';
        if(empty($placeholder))$placeholder='Ex: #000000';
        if(isset($picker) && $picker===true){
            Form::loadScript('colorpicker');
            $attr.='data-picker="on" ';
            $button=['html'=>'<div class="input-group-addon"><i></i></div>'];
            $class_input_group=' colorpicker-element';
        }
        
    }else if($type=='search'){
        if(!isset($placeholder))$placeholder='Pesquisar';
        
    }else if($type=='cep'){
        $attr.='data-mask="99999-999" ';
        
    }else if($type=='cpf'){
        $attr.='data-mask="999.999.999-99" ';
        
    }else if($type=='cnpj'){
        $attr.='data-mask="99.999.999/9999-99" ';
        
    }else if($type=='phone_only' || $type=='phone'){
        $attr.='data-mask="(99) 9999-9999" ';
        $type2='phone';
        
    }else if(in_array($type,['decimal','currency'])){
        $attr.='data-mask="'.$type.'" ';
        if(is_float($this_value) || is_int($this_value)){
            $this_value=FormatUtility::numberFormat($this_value);
        }
        //dd($this_value);
        
    }else if($type=='email' && empty($maxlength)){
        $maxlength=150;
        
    }else if($type=='password' && empty($maxlength)){
        $maxlength=20;
        if(!empty($this_value))$placeholder='Preencher somente se for alterar';
    }
    
    
    
    if(in_array($type, ['date','time','cpf','cnpj','cep','phone_only','phone','decimal','currency']) ){
        Form::loadScript('inputmask');
        $type='text';
    }
    
@endphp

<div class="form-group form-group-{{$name}} {{$class_group ?? ''}}" id='form-group-{{$name}}' {!! isset($width_group)?'style="width:'. $width_group .'px;"':'' !!}>
    @if(!empty($label))
    <label {!! !empty($id) ? 'for="'.$id.'"':'' !!} class="control-label {{$class_label ?? ''}}">{!!$label!!}</label>
    @endif

    <div class="control-div {{$class_div ?? ''}}" {!! isset($width)?'style="width:'. $width .'px;"':'' !!}>
      @php
        if(isset($button) && is_array($button)){
            echo '<div class="input-group'. ($class_input_group??'') .'">';
            if(array_get($button,'align')=='left'){
                if($button['html']??false){
                    echo $button['html'];
                }else{
                    @endphp
                    <div class="input-group-btn">@include('templates.components.button',$button)</div>
                    @php
                }
            }
        }
        
        if($type=='search')echo '<span class="relative block"><span class="fa fa-close" style="position:absolute;display:none;right:7px;margin-top:12px;font-size:0.8em;" onclick="$(this).next().val(\'\').focus();"></span>';
      @endphp
      
      
      <input type="{{$type ?? 'text'}}" data-type="{{$type2}}" class="form-control {{$class_field ?? ''}}" {!! !empty($id) ? 'id="'.$id.'"':'' !!} name='{{$name}}' placeholder="{{$placeholder ?? ''}}" maxlength="{{$maxlength ?? ''}}" value="{{ (isset($type) && $type=='password'?'':$this_value)}}" autocomplete="no" data-label="{{$data_label??$label??''}}" {!!$attr!!} >
      
      @php
        if($type=='search')echo '</span>';
        
        if(isset($button) && is_array($button)){
            if(array_get($button,'align')!='left'){
                if($button['html']??false){
                    echo $button['html'];
                }else{
                    //echo $bt_tmp;//echo '<span title="'. array_get($button,'title') .'" class="input-group-addon" data-field-bt="on"><i class="fa '. array_get($button,'icon','fa-folder') .'"></i></span>';
                    echo '<div class="input-group-btn">';
                    echo view('templates.components.button',$button);
                    echo '</div>';
                }
            }
            echo '</div>';
        }
      if(!empty($info_html))echo '<div class="control-html">'. $info_html .'</div>';
      @endphp
      <span class="help-block"></span>
    </div>
</div>