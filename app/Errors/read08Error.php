<?php

namespace App\Errors;

class read08Error implements _ErrorInterface{
    public static function title(){
        return 'Emissão fora do limite da vigência';
    }
    
    public static function description(){
        return 'A data da vigência exede o limite permitido.';
    }
    
    public static function descriptionAdmin(){
         return 'A data da vigência exede o limite permitido.';
    }
    
     
    public static function solution(){
        return '
            - Verificar no arquivo PDF a data de início da vigência, pois ela deve esatar iniciando vários dias antes da data de emissão ;<br>
            - Caso esteja correto basta salvar no painel do robô e ele ira processar normalmente;<br>            
        ';
    }
}