<?php

namespace App\Errors;

class read07Error implements _ErrorInterface{
    public static function title(){
        return 'Erro na leitura do arquivo';
    }
    
    public static function description(){
        return 'Arquivo PDF corrompido ou inválido.';
    }
    
    public static function descriptionAdmin(){
         return 'Arquivio PDF corrompido ou inválido.';
    }
    
     
    public static function solution(){
        return '
            - Verificar o arquivo PDF pois pode estar corrompido;<br>
            - Caso esteja corrompido deve ser excluído do painel do robô;<br>
            - Deve ser baixado um novo PDF no site da seguradora e em seguida enviar para o robô processar;<br>
        ';
    }
}