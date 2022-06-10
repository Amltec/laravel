<?php

namespace App\Errors;

class quiv30Error implements _ErrorInterface{
    public static function title(){
        return 'Distribuição de comissões informada - alterar tipo de comissão';
    }
    
    public static function description(){
        return 'Quando a distribuição de comissões é informada o tipo de comissão deve ser comissão com % variando por parcela';
    }
    
    public static function descriptionAdmin(){
         return '';
    }
         
    public static function solution(){
        return '
            - Deve ser acessado a proposta no Quiver e realizar a alteração solicitada: tipo de comissão deve ser comissão com % variando por parcela
        ';
    }
}