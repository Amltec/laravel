@php
Form::execFnc('awFormAjax');//seta o nome da função que deve ser inicializada


$form_action_param=[
    'url'=>($url??''),
    'form-auto-init'=>'on',
    'method'=>$method??'put',
    'autocomplete'=>'off'
];

//opções do formulário
$form_action_param['data-opt']=(isset($data_opt) ? $data_opt : []);
if(isset($id))$form_action_param['id']=$id;
if(isset($class))$form_action_param['class']=$class;
if(isset($attr)){
    if(is_array($attr)){
        $form_action_param=array_merge($form_action_param,$attr);
    }else{
        $form_action_param[]=$attr;
    }
}
if(isset($url_success))$form_action_param['data-opt']['urlSuccess']=$url_success;
if($form_action_param['data-opt']){$form_action_param['data-opt']=json_encode($form_action_param['data-opt']);}else{unset($form_action_param['data-opt']);}


echo Form::open($form_action_param),
    callstr($content??null),
    '<div style="margin-top:20px;">',
        ($bt_back??true!==false ? view('templates.components.button',['title'=>($bt_back??'Voltar'),'href'=>($url_back??''),'class'=>'bt-back pull-right']) : ''),
        
        ($bt_save??true!==false ? view('templates.components.button',['type'=>'submit','title'=> ($bt_save??'Salvar') ]) : ''),
        //'<br><br>',
        (($alert_msg??true) ? view('templates.components.alert-structure') : ''),
    '</div>',
    
        Form::close();
@endphp