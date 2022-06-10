<?php

namespace App\Errors;

class read17Error implements _ErrorInterface{
    public static function title(){
        return 'IOF com valor zero';
    }
    
    public static function description(){
        return 'O valor do IOF está zerado no PDF da Apólice.';
    }
    
    public static function descriptionAdmin(){
         return 'O valor do IOF está zerado no PDF da Apólice.';
    }
    
     
    public static function solution(){
        return '
            - Verificar no PDF o porque o valor do IOF está zerado;<br>
            - Caso o segurado(a) tenha isenção de IOF basta confirmar no painel do robô e ele irá emitir normalmente;<br>
            - Caso o robô não tenha lido o IOF corretamente, é necessário corrigir manualmente inserindo o valor de IOF correto e reportar o erro ao suporte do robô.
        ';
    }
}