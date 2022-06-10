<?php

namespace App\Errors;

class quid03Error implements _ErrorInterface{
    public static function title(){
        return 'Proposta já emitida (verificada)';
    }
    
    public static function description(){
        return 'Proposta já foi emitida no Quiver por usuário diferente do robô.';
    }
    
    public static function descriptionAdmin(){
         return 'Proposta já foi emitida no Quiver por usuário difefente do robô, verificar se realmente foi emitida por outro usuário.';
    }
    
     
    public static function solution(){
       
        return '
            - Verificar no Quiver se a proposta está emitida;<br>
            - Verificar no Quiver se a proposta foi emitida por outro usuário;<br>              
        ';
    }
}