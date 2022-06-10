<?php

namespace App\Errors;

class quid05Error implements _ErrorInterface{
    public static function title(){
        return 'Mais de uma proposta encontrada';
    }
    
    public static function description(){
        return 'Foi encontrada mais de uma proposta, sem parâmetro programado para selecionar qual a é a correta.';
    }
    
    public static function descriptionAdmin(){
         return 'Foi encontrada mais de uma proposta, sem parâmetro programado para selecionar qual a é a correta.';
    }
    
     
    public static function solution(){
       
        return '
            - O robô encontrou mais de uma proposta segundo os critérios de busca, por esse motivo ele não fez a emissão pois não conseguiu definir qual era a correta;<br>
            - Verificar no Quiver qual é a correta, cancelar uma das duas, em seguida mudar o status da apólice no painel do robô para "pronto para emitir";<br> 
            - Ou então fazer a baixa manual, em seguida mudar o status da apólice no painel do robô para "concluído";<br>              
        ';
    }
}