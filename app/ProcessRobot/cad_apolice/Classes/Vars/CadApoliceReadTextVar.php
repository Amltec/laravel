<?php

namespace App\ProcessRobot\cad_apolice\Classes\Vars;


/**
 * Classe com variáveis padrões para o processo de leitura do texto da apólice
 */

class CadApoliceReadTextVar{
    
    /**
     * Strings que indicam que o texto deve ser extraído novamente
     */
    public static $re_extract=[
        'err:Invaild respond status'
    ];
    
    
    /**
     * Strings que indicam no texto que é endosso, faturas, etc
     * @obs se iniciar com 'regex:...' indica que o conteúdo é uma expressão regular. Ex: 'regex:/[0-9]{2}/'
     */
    public static $is_ignore=[
        ['code'=>'read03', 'str'=>'ENDOSSO DE CANCELAMENTO'],
        ['code'=>'read03', 'str'=>'Comunicamos que a fatura para o Segurado indicado'],
        ['code'=>'read03', 'str'=>'Comunicamos que o endosso para o Segurado indicado'],
        ['code'=>'read03', 'str'=>'Endosso Item Endosso'], //ex: Tipo Produto N.º Apólice N.º Endosso Item Endosso Alfa Empres]a
        ['code'=>'read03', 'str'=>'Demonstrativo do Endosso'],
        ['code'=>'read03', 'str'=>'Endosso - Ramo 31 Automoveis'],
        ['code'=>'read02', 'str'=>'Tokio Marine Imobiliário Residencial'],
        ['code'=>'read02', 'str'=>'Tokio Marine Imobiliário Empresarial'],
        ['code'=>'read16', 'str'=>'Documento de Seguro - Via Corretor'],
        ['code'=>'read02', 'str'=>'regex:/(liberty seguros)(.*)(comunicamos que a apólice de seguros para o Segurado indicado acima foi emitida)/i'],
        ['code'=>'read00', 'str'=>'Confirmamos o cancelamento de sua apólice'],
        ['code'=>'read03', 'str'=>'TIPO DE ENDOSSO : PRORROGACAO DE VIGENCIA'],
        //obs: regx abaixo desconsiderado, pois o mesmo não está compatível para os casos da bradesco, ex: 'Item Endosso 1835 . 990 ...' //esta verificação ficou para cada classe da seguradora
        //[str=>'regex:/(endosso)(:*)\s+(([0-9]*)(?!0).)(\s)/i',      //Obs: 'Endosso: N' ignorando os ':', espaços internos ' ' e não pode ter espaço depois do número, e ignora se for apenas '0'
        //[str=>'regex:/(endosso)(:)(([0-9]*)(?!0).)(\s)/i',          //Obs: igual ao acima, mas desconsidera espaços depois de ':'
    ];
    
    
    /**
     * Strings que indicam que é uma seguradora que deve ser ignorada
     */
    public static $ignore_insurer=[
        '03.502.099/0001-18',    //Chubb Seguros
        '42.366.302/0001-28',    //KOVR Seguradora
        '33065699000127',       //sura
    ];
    
    
}