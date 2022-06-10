<?php

namespace App\Errors;

class quid04Error implements _ErrorInterface{
    public static function title(){
        return 'Proposta não cadastrada';
    }

    public static function description(){
        return 'Proposta não está cadastrada no Quiver.';
    }

    public static function descriptionAdmin(){
         return 'Todos os campos bateram como e a proposta já está emitida, mas o número da apólice está diferente, portanto considera que não está cadastrada.';
    }


    public static function solution(){

        return '
            - Verificar no Quiver se a proposta foi cadastrada;<br>
            - Se realmente não estiver cadastrata basta cadastrar, em seguida mudar o status no painel do robô para "pronto para emitir";<br>
        ';
    }
}
