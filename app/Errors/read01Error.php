<?php

namespace App\Errors;

class read01Error implements _ErrorInterface{
    public static function title(){
        return 'Campos inválidos';
    }
    
    public static function description(){
        return 'Verificar quais campos são inválidos.';
    }
    
    public static function descriptionAdmin(){
         return 'Verificar quais campos são inválidos.';
    }
    
     
    public static function solution(){
        return '
            - Verificar na lista abaixo os campos destacados, eles estão com dados inválidos;<br>
            - Conferir no PDF se eles realmente estão errados;<br>
            - Caso estejam errados corrigir no painel do robô e salvar, ele irá processar normalmente;<br>
        ';
    }
}