<?php

namespace App\Errors;

class bro01Error implements _ErrorInterface{
    public static function title(){
        return 'Corretor não encontrado';
    }
    
    public static function description(){
        return 'Corretor não encontrado.';
    }
    
    public static function descriptionAdmin(){
         return 'Corretor não encontrado.';
    }
    
     
    public static function solution(){
        return '
            - O corretor não está cadastrado no painel do robô, necessário cadastrar o corretor, em seguida mudar o status da apólice no painel do robô para "pronto para emitir";<br>            
            - Caso não cadastre o corretor a emissão deve ser feita manual no Quiver, em seguida mudar o status da apólice no painel do robô para "concluído";<br>  
        ';
    }
}