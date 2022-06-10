<?php

namespace App\ProcessRobot\cad_apolice\Classes\Segs;

/**
 * Funções gerais para as demais classes Seg
 */
class FunctionsSeg{
    /**
     * Valida o cep considerando o padrão de cep oculto, onde vem com 3 digitos e * no final (ex: '489*')
     * @return array [success,msg,ignore]
     */
    public static function cepValidate($cep,$dados_arr=null){
        //cep de pernoite 
        $ignore=false;
        if($cep!=''){
            $n=str_replace('-','',$cep);//deixa apenas números
            
            if(strlen($n)==4 && substr($n,-1)=='*'){//este é um padrão de cep oculto, onde vem com 3 digitos e * no final (ex: '489*')
                //Lógica: deixa passar
                if(!is_numeric( str_replace('*','',$n) ))return ['success'=>false,'msg'=>'CEP inválido'];
                //seta que deve ignorar o campo // obs: este campo gerado só tem validade nas informações enviadas para o robô
                $ignore=true;
                
            }else{
                //deixa obrigatório apenas para data-type=apolices
                if($dados_arr && $dados_arr['data_type']=='apolice' && empty($n))return ['success'=>false,'msg'=>'Campo CEP inválido'];
                //valida o campo cep com 8 digitos ou 5 digitos (5 dig padrão liberty)
                $n = str_replace('*','',$n);//remove os '*'
                if($n && (strlen($n)!=8 && strlen($n)!=5 && strlen($n)!=4))return ['success'=>false,'msg'=>'CEP inválido - precisa conter 8, 5 ou 4 caracteres'];
                if($n && !is_numeric($n))return ['success'=>false,'msg'=>'CEP inválido'];
            }
        }
        return ['success'=>true,'msg'=>'','ignore'=>$ignore];
    }
}