<?php

namespace App\ProcessRobot\seguradora_data\boleto_seg;

/**
 * Configurações gerais para o processo de boletos da seguradoras
 * Válido para o processo seguradora_data.boleto_seg
 * As configurações abaixo são válidas para a tabela pr_seguradora_data.process_next_at
 */
class BoletoSegConfig{
    
    /**
     * Seguradoras que devem esperar para executar o primeiro processamento
     * Sintaxe: [insurer_basename => days ]
     * Obs: caso alguma seguradora não precisa de dias de atraso, não precisa ser informada abaixo
     */
    public static $insurers_process_first=[
        'allianz'=>1,
    ];
    
    
    /**
     * Tempo em dias padrão para reprocessamento em caso de não encontrar os boletos esperados, e ter que voltar outro dia para procurar novamente
     * Válido para os códigos: segd08
     */
    public static $insurers_reprocess_days=1;
    
    
    /**
     * Horário do dia em que deve ser programado o agendamento para buscar os arquivos nas seguradoras.
     * Ex: se um registro tiver o primeiro processamento para 1 dia, considerando que o processo na tabela pr_seguradora_data foi gerado em 01/12/2021 (qualquer horário), 
     *      então o processo será agendado para 02/12/2021 (no horário informado nesta variávei)
     * Sintaxe: H:i:s
     */
    public static $insurers_process_time='08:00:00';
    
    
    /**
     * Retorna a data do próximo reprocessamento considerando a as variáveis acima
     * @param $type    - valores: first, ou reprocess
     * @param $insurer - nome base do cadastro da seguradora, ex: libety, porto, allianz, ... (conforme arquivo App\ProcessRobot\VarsProcessRobot::$configProcessNames
     * @return string new date | null 
     */
    public static function getNextAt($insurer,$type){
        if($type=='first'){
            $next_at = self::$insurers_process_first[$insurer]??null;
        }else{//reprocess
            $next_at = self::$insurers_reprocess_days;
        }
        if($next_at)$next_at = date('Y-m-d', strtotime( $next_at  .' day', strtotime(date('Y-m-d'))) ). ' '. self::$insurers_process_time;
        return $next_at;
    }
    
}
