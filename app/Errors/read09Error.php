<?php

namespace App\Errors;

class read09Error implements _ErrorInterface{
    public static function title(){
        return 'Emissão fora do prazo permitido';
    }
    
    public static function description(){
        return 'A data de emissão não é permitida, exedeu 365 dias.';
    }
    
    public static function descriptionAdmin(){
         return 'A data de emissão não é permitida, exedeu 365 dias.';
    }
    
     
    public static function solution(){
        return '
            - Verificar no arquivo PDF a data de emissão da apólice, provavlemente é uma apólice já vencida ;<br>
            - Caso esteja vencida excluir esse caso no painel do robô;<br>            
        ';
    }
}