<?php

namespace App\Errors;

class quid01Error implements _ErrorInterface{
    public static function title(){
        return 'Proposta não localizada';
    }
    
    public static function description(){
        return 'Proposta não cadastrada no Quiver ou não localizada na pesquisa devido dados divergentes aos do PDF da apólice.';
    }
    
    public static function descriptionAdmin(){
         return 'Proposta não cadastrada no Quiver ou não localizada na pesquisa devido a divergência de dados do Quiver e os dados extraídos do PDF.';
    }
    
     
    public static function solution(){
        return '
            - Verificar no Quiver buscando a proposta primeiramente através do número do chassi;<br>
            - Verificar no Quiver buscando a proposta através do CPF/CNPJ e Data inicial de vigência (tolerância de 20 dias);<br>
            - Verificar no painel do robô a data de processamento e comparar com a data de cadastro da proposta no Quiver, se data de processamento for anterior a data de cadastro da proposta no Quiver basta mudar o status no painel para "pronto para emitir" e o robô irá processar normalmente;<br>
            - Caso não encontre a proposta é porque a proposta não foi cadastrada ou esta cadastrada com dados insuficientes/errados para ser encontrada.<br>
        ';
    }
}