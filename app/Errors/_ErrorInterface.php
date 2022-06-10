<?php

namespace App\Errors;

/**
 * Base de erros para os arquivos de cada erro.
 * A classe \App\Error::get() existe para chamar estes arquivos.
 */
interface _ErrorInterface{
    //descrição sobre o título
    public static function title();
    
    //descrição sobre o erro
    public static function description();
    
    //descrição sobre a solução do problema
    public static function solution();
    
    //descrição para o dev ou superadmin
    public static function descriptionAdmin();
    
}