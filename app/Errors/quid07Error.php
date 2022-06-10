<?php

namespace App\Errors;

class quid07Error implements _ErrorInterface{
    public static function title(){
        return 'Proposta já emitida (erro)';
    }
    
    public static function description(){
        return 'Proposta já foi emitida no Quiver pelo mesmo usuário diferente do robô (é considerado um erro).';
    }
    
    public static function descriptionAdmin(){
         return 'Não deveria gerar esta situação, pois uma vez emitido pelo robô, nunca poderia retornar a este erro (analisar o motivo).';
    }
    
    public static function solution(){
        return '';
    }
}