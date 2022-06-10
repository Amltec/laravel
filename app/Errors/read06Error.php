<?php

namespace App\Errors;

class read06Error implements _ErrorInterface{
    public static function title(){
        return 'Arquivo com senha';
    }
    
    public static function description(){
        return 'Arquivo PDF com senha.';
    }
    
    public static function descriptionAdmin(){
         return 'Arquivo PDF com senha.';
    }
    
     
    public static function solution(){
        return'
            - Verificar o arquivo PDF pois pode estar protegido com senha;<br>
            - Caso esteja protegido com senha deve ser excluído do painel do robô;<br>
            - Deve ser baixado um novo PDF no site da seguradora e em seguida enviar para o robô processar;<br>
        ';
    }
}