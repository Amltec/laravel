<?php

namespace App\ProcessRobot\cad_apolice\Classes\Vars;


/**
 * Classe de variáveis gerais para filtros de dados, códigos de erros, relatórios, etc
 */

class FilterVar{
    //**** Agrupamento de Erros *****
    //Obs: abaixo os caracteres '..' representam todos os erros que tiverem este prefixo
    
    //relação de erros de operador
    public static $group_err=[
        'quid01,quid04,quid05'  => 'Proposta não localizada',
        'quid02'                => 'Proposta cancelada',
        'quid03,quid07'         => 'Proposta já emitida',
        'read01'                => 'Campos inválidos',
        //'quiv03'                => 'Campos bloqueados',
        'read05,read12,read13'  => 'Divergência de Valor',
        'bro..'                 => 'Corretor não encontrado',
        'quiv..'                => 'Erros no Quiver',
        'quil..'                => 'Erros no Login',
    ];
    
    //erros que correspondem a opção 'Outros'
    public static $group_err_other='quid01,quid04,quid05,quid02,quid03,read01,quiv03,read05,read12,read13,bro..,quiv..';
    
    
    
    
}