@php
/***
    Template carregad dentro da visualização do processo de cadastro de apólice
    Ex: process_cad_apolice/{process_id}/show?
    
    Variáveis: error_code
***/
use App\Error;


$view='errors.process_robot.'.$error_code;
if(!Error::exists($error_code))return;

if(Error::get($error_code,'description')){
    echo '<div style="margin-top:15px;">'. Error::get($error_code,'description') .'</div>';
}

if( Error::get($error_code,'solution') ){
    echo '<div style="margin-top:15px;"><strong style="font-size:1.2em;">O que fazer</strong><br>'. Error::get($error_code,'solution') .'</div>';
}

if(in_array(Auth::user()->user_level,['dev','superadmin'])){
    if(Error::get($error_code,'description_admin')){
        echo '<div style="margin:15px 0 0 0;padding:15px 0 0 0;border-top:1px dotted #ffffff66;font-size:0.85em;color:#ffffffcc;"><strong>Descrição interna:</strong><br> ('. strtoupper($error_code) .') '.  Error::get($error_code,'description_admin')  .'</div>';
    }
}

@endphp