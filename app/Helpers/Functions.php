<?php
/*
 * Funções gerais
 * _GET()
 * _GETNumber()
 * isMobile()
 * ddx()
 * callstr()
 * rqWithQuery()
 */

if(!function_exists('_GET')){
    /** 
     * Função GET com filtro de segurança.
     * @param string $name - nome da variável
     * @param filtro-php $filter_type - tipo do filtro. Padrão FILTER_DEFAULT. Aceita todos os filtro da função 'filter_input'.
     */
   function _GET($name){
       return \Request::get($name);
   }
}

if(!function_exists('_GETNumber')){
    /** 
     *  Função GET com filtro de segurança para números.
     * @param string $name - nome da variável
     * @param filtro-php $filter_type - tipo do filtro. Padrão FILTER_DEFAULT. Aceita todos os filtro da função 'filter_input'.
     */
   function _GETNumber($name,$filter_type=FILTER_SANITIZE_NUMBER_INT){
       $n=filter_input(INPUT_GET, $name,$filter_type);
       return $n?$n:null;
   }
}

if(!function_exists('isMobile')){
    /**
     * Detecta se é acesso mobile.
     * Return boolean
     */
    function isMobile(){
        static $r=null;
        if(is_null($r))$r=preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
        return $r;
    }
}


if(!function_exists('ddx')){
    /**
     * O mesmo da função dd(), mas diretamente com o var_dump (e sem a formatação do frontend)
     * Param boolean $pre - se =true, então exibe dentro das tags html PRE;
     */
    function ddx(...$as){
        foreach($as as $a){
            echo '<pre>';var_dump($a);echo '</pre>';
        }
        http_response_code(500); // Seta 500 erro interno de servidor
        exit;
    }
}

if(!function_exists('callstr')){
    /**
     * Verifica se $cb é uma função e executá-lo ou se é um texto e escreve na tela.
     * Embora retorne ao valor, também ser usado um 'echo' dentro de um callback em $cb
     * @param $args (array de argumentos para $cb)
     */
    function callstr($cb,$args=null,$isReturn=false){
        if($cb!=='' && $cb!==null){
            if(is_callable($cb)){
                if(gettype($cb)=='string' && strlen($cb)<3){//o nome da função $cb precisa ter mais 3 caracteres ou mais
                    if($isReturn){return $cb;}else{echo $cb;return '';}
                }
                
                if(is_array($args)){
                    return call_user_func_array($cb,$args);
                }else{
                    return call_user_func($cb);
                }
            /*}else if(in_array(getType($cb),['string','integer','double','array'])){
                if($isReturn){return $cb;}else{echo $cb;}
            }else{
                //echo ''; não precisa escrever
                return null;
            }*/
            }else{
                if($isReturn){return $cb;}else{echo $cb;}
            }
        }else{
            return '';
        }
    }
}


if(!function_exists('fpath')){
    //Formata o caminho de \\ para /
    function fpath($path){
        return str_replace('\\', '/', $path);
    }
}