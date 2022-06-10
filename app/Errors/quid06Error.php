<?php

namespace App\Errors;

class quid06Error implements _ErrorInterface{
    public static function title(){
        return 'Proposta duplicada pelo Quiver ID';
    }
    
    public static function description(){
        return '';
    }
    
    public static function descriptionAdmin(){
         return 'Analisar o caso, pois não era pra existir Quiver ID duplicado.';
    }
    
     
    public static function solution(){
        return '';
    }
}