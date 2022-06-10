<?php

namespace App\ProcessRobot;
use App\ProcessRobot\VarsProcessRobot;
use App\Models\BrokerInsurerData;
    
/**
 * Funções gerais relacionadas de uso global do processo robô
 * Relacionado as variávis da classe VarsProcessRobot
 * Obs: esta classe não é um controller
 */
class FunctionsProcessRobot{
    
    private static $modelBrokerInsurerData=null;
    private static $results=[];
    
    /**
     * Verifica se seguradora tem permissão / está configurado para o respectivo o processo e produto.
     * Utiliza a classe de controle VarsProcessRobot.php
     * @param $ramo - opcional para comparar. Informar o nome do ramo, ex: automovel, residencial...
     * @return boolean
     */
    public static function allowProcessInsurer($insurer_basename,$process_name,$process_prod,$ramo=null){
        $n = array_get(VarsProcessRobot::$configProcessNames,$process_name.'.products.'.$process_prod.'.insurers_allow');
        if($n){
            if($ramo){
                $n = $n[$ramo]??[];
            }
            return is_array($n) ? in_array($insurer_basename,$n) : $insurer_basename==$n;
        }else{
            return true;
        }
    }
    
    
    /**
     * Verifica se a seguradora e corretor tem permissão e logins necessários para prosseguir com as ações do robô
     * Obs: os dados são armazenados em cache estático na classe
     * @return self::checkInsurerBrokerLogin()
     */
    public static function isActiveInsurerBrokerLogin($insurer_id,$broker_id){
        $k=$insurer_id.'_'.$broker_id;
        if(!isset($results[$k]))$results[$k]=self::checkInsurerBrokerLogin($insurer_id,$broker_id);
        return $results[$k];
    }
    
    
    
    /**
     * Verifica se a seguradora e corretor tem permissão e logins necessários para prosseguir com as ações do robô
     * @return error - [success=false,code]
     * @return success - [success=true,active,login,pass,user,code]
     */
    private static function checkInsurerBrokerLogin($insurer_id,$broker_id){
        if(!self::$modelBrokerInsurerData)self::$modelBrokerInsurerData = \App\Models\BrokerInsurerData::CLASS;
        $tmp = self::$modelBrokerInsurerData::where(['broker_id'=>$broker_id,'insurer_id'=>$insurer_id,'meta_name'=>'seguradora_config'])->first();
        if(!$tmp)return ['success'=>false,'code'=>'wbot05'];
        try{
            $tmp = unserialize($tmp->meta_value);
        }catch(\Exception $e){//erro ao desserializar os dados
            return ['success'=>false,'code'=>'wbot07'];
        }
        if(($tmp['active']??false)!==true)return ['success'=>false,'code'=>'wbot08'];//login não ativado
        $tmp['success']=true;
        return $tmp;
    }
    
}

