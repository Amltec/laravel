<?php

namespace App\Errors;

class quiv29Error implements _ErrorInterface{
    public static function title(){
        return 'Existe outra apólice com o número informado';
    }
    
    public static function description(){
        return '';
    }
    
    public static function descriptionAdmin(){
         return '';
    }
         
    public static function solution(){
        return '
            - Deve ser verificado no Quiver se existe mais de uma emissão com o mesmo número de apólice, e neste caso: <br>
            - Retirar o número da apólice da outra proposta;<br>
            - Ou emitir manual e marcar como concluído pelo robô<br>
        ';
    }
}