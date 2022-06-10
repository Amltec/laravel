<script src="https://code.jquery.com/jquery-3.5.0.min.js" integrity="sha256-xNzN2a4ltkB44Mc/Jz3pT4iU1cmeR0FkXs4pru/JxaQ=" crossorigin="anonymous"></script>
&nbsp;
<strong>Dados extraídos</strong>  &nbsp; &nbsp;
Processo: {{$data['process_id']??'-'}} &nbsp; &nbsp;
Corretor: {{$data['broker_id']??'-'}} &nbsp; &nbsp;
Seguradora: {{$data['insurer_id']??'-'}}

<a href="#" onclick="$(this).next().fadeToggle('fast');return false;" style="position:absolute;right:15px;margin:5px 0 0 0;font-size:12px;text-decoration:none;">+ info</a>
<div style="display:none;font-size:12px;background:#f2f2f2;position:absolute;padding:1px 5px;border:1px solid #e2e2e2;border-radius:3px;right:15px;margin-top:3px;z-index:99;">
    <span style=""margin-right:10px;">Parâmetros querystring:</span>
    <span style="display:inline-block;margin-right:10px;"><strong>force</strong> - ok, all</span>
    <span style="display:inline-block;"><strong>pdf_engine</strong> = auto, pdfparser, ws01, ws02</span>
</div>
@php


/***** xxxx
$text='al: 08212 - SOROCABA Código: 523401
Nome Corretor: HUMBER ADMR TEC E CORRETORA DE SEG LTDA
CNPJ: 065.447.963/0001-83 Cód. SUSEP: 202059019
Endereço: RUA DR ERICO PIMENTEL DIAS 41 Bairro: CENTRO
CEP: 18400-811 Cidade: ITAPEVA UF: SP';

$tmp='202059019';
$n=str_replace(['/','.'],['\/','\.'],$tmp);
preg_match_all('/([^0-9]('.$n.')[^0-9])|([^0-9]'.$n.'$)|(^'.$n.'[^0-9])|(^'.$n.'$)/',$text,$matches);

dd($matches);
*/

@endphp
<table>
<tr valign="top">
    <td>
        <strong>Texto</strong>
        <textarea readonly="readonly">{!! $model->getText('text')??'-' !!}</textarea>
    </td><td>
        <strong>Dados</strong>
        <textarea readonly="readonly">@php
            //echo print_r($model->getText('data'),true);
            if($data['success']){
                $n=$data['file_data'];
            }else{
                $n=[
                    'success'=>$data['success'],
                    'msg'=>$data['msg'],
                    'code'=>(isset($data['code']) ? $data['code'] .': '. $thisClass::getStatusCode($data['code']) : null),
                    'data'=>$data['file_data']??[]
                ];
                if($data['validate']??false)$n['validate']=$data['validate'];
            }
            echo print_r($n,true);
            if(!$data['success'])echo chr(10).'Error: '.$data['msg'];

        @endphp</textarea>
    </td>
</tr>
</table>
<style>
body{margin:5px 0 0 0;overflow:hidden;}
body,td{font-family:arial;font-size:14px;line-height:135%;}
h2{margin-bottom:0;}
table{position:absolute;width:100%;height:calc(100% - 40px);left:0;top:30px;}
table td{width:50%;padding-right:15px;}
table td + td{padding-left:15px;padding-right:0;}
textarea{width:100%;height:100%;background:#222;color:#00d95a;padding:10px 15px;resize: none;}
</style>
