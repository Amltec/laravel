<?php
namespace App\Utilities;

/*
 * Classe de funções Gerais 
 */
Class FunctionsUtility{
    
    /**
     * Gera um hash/key aleatório. 
     */
    public static function keyGenerator($length = 32){
        return substr(str_repeat(md5(rand()), ceil($length/32)), 0, $length);
    }
    
    
    
    
    
    //Captura e retorna ao id do youtube a partir da url
    public static function getIdYoutube($url){
            $id='';
            if($url!=''){
                    $pattern = 
                            '%^# Match any youtube URL
                            (?:https?://)?  # Optional scheme. Either http or https
                            (?:www\.)?      # Optional www subdomain
                            (?:             # Group host alternatives
                              youtu\.be/    # Either youtu.be,
                            | youtube\.com  # or youtube.com
                              (?:           # Group path alternatives
                                    /embed/     # Either /embed/
                              | /v/         # or /v/
                              | /watch\?v=  # or /watch\?v=
                              )             # End path alternatives.
                            )               # End host alternatives.
                            ([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
                            $%x'
                            ;
                    $result = preg_match($pattern, str_replace(chr(13),'',$url), $matches);
                    if(false != $result)$id=$matches[1];
            }
            return $id;
    }
    
    
}