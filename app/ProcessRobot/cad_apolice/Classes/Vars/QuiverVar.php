<?php

namespace App\ProcessRobot\cad_apolice\Classes\Vars;


/**
 * Classe com variáveis padrões para todos os casos do Quiver
 */

class QuiverVar{
    
    /**
     * Lista completa dos modos de pagamentos
     */
    public static $pgto_all_codes=[
        '62884'     =>'1ª BOLETO - DEMAIS CARTÃO CREDITO',
        '9'         =>'1ª BOLETO - DEMAIS DEBITO EM CONTA',
        '1471027'   =>'1ª DEBITO E DEMAIS BOLETOS',
        '12'        =>'AUTO-FINANCIAMENTO',
        '10'        =>'BOLETO',
        '2'         =>'CARNÊ',
        '3'         =>'CARTÃO DE CRÉDITO',
        '13'        =>'CARTÃO DE DÉBITO',
        '1471024'   =>'Cartão Porto',
        '8'         =>'CARTÃO PORTO SEGURO',
        '62885'     =>'CHEQUE',
        '15'        =>'CHEQUE PRÉ-DATADO',
        '5'         =>'CHEQUES NA CORRETORA',
        '6'         =>'CRÉDITO EM CONTA',
        '4'         =>'DÉBITO EM CONTA',
        '1471026'   =>'DEPÓSITO',
        '11'        =>'DINHEIRO',
        '62889'     =>'EMISSAO',
        '62886'     =>'ESTORNO DE PRÊMIO',
        '24'        =>'FATURA BIMESTRAL',
        '21'        =>'FATURA MENSAL',
        '25'        =>'FATURA SEMESTRAL',
        '23'        =>'FATURA TRIMESTRAL',
        '16'        =>'FICHA DE COMPENSAÇÃO',
        '62887'     =>'ORDEM DE PAGAMENTO',
        '62892'     =>'SUBVENÇÃO ESTADUAL',
        '62891'     =>'SUBVENÇÃO FEDERAL',
        '1471025'   =>'SUBVENÇÃO NÃO CONTEMPLADA',
        '62890'     =>'TERCEIRO CONGÊNERE',
        '14'        =>'TRANSFERÊNCIA',
        '62888'     =>'VALOR PAGO COM PONTOS',
    ];
    
    /**
     * Lista resumida dos modos de pagamentos
     */
    public static $pgto_codes_types=[
        '2'     =>'carne',
        '10'    =>'boleto',
        '4'     =>'debito',
        '3'     =>'cartao',
        '9'     =>'1boleto_debito',
        '62884' =>'1boleto_cartao',
    ];
   
}