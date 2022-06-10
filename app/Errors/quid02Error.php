<?php

namespace App\Errors;

class quid02Error implements _ErrorInterface{
    public static function title(){
        return 'Proposta cancelada / diferente da emitida';
    }
    
    public static function description(){
        return 'Proposta está cancelada no Quiver ou a proposta é diferente da emitida.';
    }
    
    public static function descriptionAdmin(){
         return 'Proposta está cancelada no Quiver ou a proposta é diferente da emitida.';
    }
    
     
    public static function solution(){
       
        return '
            - Verificar no Quiver se a proposta realmente está cancelada;<br>
            - Verificar no Quiver se os dados da proposta são iguais aos do PDF da Apólice;<br> 
            - Se realmente estiver cancelada mudar o status no painel do robô para "concluído";<br>
        ';
    }
}