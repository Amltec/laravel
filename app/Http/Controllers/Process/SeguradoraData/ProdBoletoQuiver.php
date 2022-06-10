<?php

namespace App\Http\Controllers\Process\SeguradoraData;

use App\Services\AccountsService;
use App\Utilities\FormatUtility;

/**
 * Classe responsável pelo processo de ações nos sites das seguradoras: cadastro de boletos no quiver (que foram capturados pelo processo seguradora_data.boleto_seg)
 * Válido para o processo de cadastro de apólice (process_name=cad_apolice)
 */
class ProdBoletoQuiver{
    private static $process_name = 'seguradora_data';
    private static $process_prod = 'boleto_quiver';


    //Método utilizado a partir de ProcessSeguradoraDataController@wsrobot_data_getAfter_process
    public function wsrobot_data_getAfter_process($params,$thisClass){
        extract($params);

        //captura a relação de apólices a serem enviadas
        //importante: neste processo 'boleto_quiver' será considerado apenas 1 registro por vez da tabela pr_seguradora_data
        $reg=$thisClass->getProcessModel('cad_apolice')->select('process_robot.*')
                ->join('pr_seguradora_data', 'process_robot.id', '=', 'pr_seguradora_data.process_rel_id')
                ->where(['pr_seguradora_data.process_id'=>$ProcessModel->id,'pr_seguradora_data.process_prod'=>'boleto_quiver'])
                ->whereIn('pr_seguradora_data.status',['p','a'])//captura todos os status: p - aguardando robo, a - em andamento
                ->orderBy('process_rel_id','asc')   //orderna para que os registros mais antigos adicionado na fila sejam os primeiros a serem processados
                ->first();
        if(!$reg){//não tem registros para processar, portanto finalizado o registro
            $ProcessModel->update(['process_status'=>'f']);//f - finalizado
            $ProcessModel->setData('error_msg','');
            return ['repeat'=>true];
        }

        //libera o acesso externo ao arquivo atualizado o token com a data e hora atual
        $reg->setData('token',date('Y-m-d H:i:s'));

        //captura a lista de boletos
        $boleto_r=$reg->getBoletoSeg();
        //dd($reg,$boleto_r);
        if(!$boleto_r){
            //marca como erro o registro da tabela pr_seguradora_data adicionando no respectivo log os detalhes do erro
            $m = $thisClass->PrSeguradoraData->where(['process_id'=>$ProcessModel->id,'process_rel_id'=>$reg->id,'process_prod'=>self::$process_prod])->first();
            $m->update(['status'=>'e']);
            $m->addLog('error','Erro ao localizar boletos já gravados na pasta cad_apolice (sdbq01)');

            $model_item = $thisClass->getProcessModel('cad_apolice')->find($reg->id);
            $this->setRelStatus($model_item,'e','sdbq01');
            return ['repeat'=>true];
        }

        $config_cad_apolice = AccountsService::getCadApoliceConfig($accountModel);
        $dataReg = $reg->getData();
        $r = [
                'process_quiver_id'=> ($dataReg['quiver_id']??''),
                'process_rel_id'=> $reg->id,
                'process_rel_name'=> $reg->process_name,
                'process_rel_prod'=> $reg->process_prod,
                'config_search_products' => strtoupper(FormatUtility::removeAcents( $config_cad_apolice['search_products'][$reg->process_prod]??'' )),
            ] + $reg->getSegData();

            foreach(['names_anexo'] as $f){
                $arr=[];
                //formata os valores
                foreach($config_cad_apolice[$f] as $a => $v){
                    $arr[strtoupper($a)] = strtoupper(FormatUtility::removeAcents($v));
                }
                $r['config_'. $f] = json_encode($arr);
            }

        //ajusta a var $boleto_r com a url
        $r['boleto_count']=count($boleto_r);
        $i=1;
        foreach($boleto_r as $num => $link){
            //$p = $path . DIRECTORY_SEPARATOR . 'boleto_'.$parc_num.'.pdf';
            $r['boleto_num_'.$i]=$num;
            $r['boleto_url_'.$i]=$link['url_np'];
            $i++;
        }

        return $r;
    }


    //Método utilizado a partir de ProcessSeguradoraDataController@wsrobot_data_set_process
    //Obs: este método não precisa de retorno
    public function wsrobot_data_set_process($params, $thisClass){
        extract($params);
        if(!$data_robot)return false;//apenas finalização a função
        //dd($params);
        $process_rel_id = $data_robot['process_rel_id'];
        $model_item = $thisClass->getProcessModel('cad_apolice')->find($process_rel_id);
        if(!$model_item)return false;//apenas finalização a função

        $s = ['ok2'=>'f','ok'=>'w'][$status_code]??'e';
        $this->setRelStatus($model_item,$s,$status_code);//grava o status code do retorno do processo do boleto

        //erros que devem marcar como finalizado sem alterações, pois não tem o que ser feito (como apólices canceladas, não encontradas, etc)
        $errors_status_f=[
            'quid01','quid02'
        ];
        if(in_array($status_code,$errors_status_f))$s='f';//f - finalizado sem alterações

        $m = $thisClass->PrSeguradoraData->where(['process_id'=>$ProcessModel->id,'process_rel_id'=>$process_rel_id,'process_prod'=>self::$process_prod])->first();
        $m->update(['status'=>$s,'finished_at'=>date('Y-m-d H:i:s')]);

        $log=$data_robot;
        unset($log['process_rel_id']);

        if($status=='e'){//erro geral do processo, mas mesmo assim marca cada registro que foi enviado para processamento como erro (pois assim fica melhor controlar todo o processo de erros e finalizados)
            $model_item->update(['status'=>'e']);
            $m->addLog('error',$log);
            //dd(123);
            return false;
        }

        //interrompe a função, pois abaixo só deve processar se estiver finalizado ok (pois se não receber o arquivo zip, não tem o que fazer nesta parte do processo)
        if($status!='f')return false;

        if($s=='f' || $s=='w'){//finalizado
           $m->addLog('upload',$log);
        }else{//erro
           $m->addLog('error',$log);
        }
        //dd($data_robot, $log);


        //sem retorno
    }


    /**
     * Seta o status / erro no item relacionado da tabela process_robot[cad_apolice]
     */
    private function setRelStatus($model_item,$s,$status_code){
        $model_item->setData('boleto_quiver',$s);
        $model_item->setData('boleto_status_code',$status_code);//grava o status code do retorno do processo do boleto
    }
}
