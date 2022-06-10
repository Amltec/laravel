<?php

namespace App\Errors;

class quil06Error implements _ErrorInterface{
    public static function title(){
        return 'Acesso bloqueado';
    }
    
    public static function description(){
        return 'A quantidade de usuários utilizando o módulo Professional excedeu o limite de licenças contratadas.';
    }
    
    public static function descriptionAdmin(){
         return self::description();
    }
         
    public static function solution(){
        return '
            - Entre em contato com o Quiver para a liberação do acesso<br>
        ';
    }
}