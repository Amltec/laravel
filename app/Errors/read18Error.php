<?php

namespace App\Errors;

class read18Error implements _ErrorInterface{
    public static function title(){
        return 'Arquivo bloqueado para leitura';
    }
    
    public static function description(){
        return 'O arquivo PDF é bloqueado para leitura.';
    }
    
    public static function descriptionAdmin(){
         return 'O arquivo PDF é bloqueado para leitura.';
    }
         
    public static function solution(){
        return '
            - Este arquivo é bloqueado por isso o robô não pode fazer a leitura e extrair os dados, deve ser feita a emissão manual no Quiver;<br>
            - Em seguida deve ser mudado o status no painel do robô para "concluído";<br>      
        ';
    }
}