<?php

namespace App\Http\Controllers\Process\SeguradoraData;
use Illuminate\Filesystem\Filesystem;
use App\Services\PrSegService;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;
use App\ProcessRobot\seguradora_data\boleto_seg\BoletoSegConfig;

/**
 * Classe responsável pelo processo de ações nos sites das seguradoras: captura de boletos do cadastro de apólice
 * Válido para o processo de cadastro de apólice (process_name=cad_apolice)
 */
class ProdBoletoSeg{
    private static $process_name = 'seguradora_data';
    private static $process_prod = 'boleto_seg';
    private static $max_records_get_process=30;//número máximo de registros da tabela pr_seguradora_data retornados no process $this->wsrobot_data_getAfter_process()
    //private static $max_days_get_process=30;//número máximo de dias a partir do registro mais recente para retornar aos registros (ex: =30 - não retorna a resultados com mais de 30 dias da data de emissão)


    //Método utilizado a partir de ProcessSeguradoraDataController@wsrobot_data_getAfter_process
    public function wsrobot_data_getAfter_process($params,$thisClass){
        extract($params);

        //captura a relação de apólices a serem enviadas
        $regs=$thisClass->getProcessModel('cad_apolice')->select('process_robot.*')
                ->join('pr_seguradora_data', 'process_robot.id', '=', 'pr_seguradora_data.process_rel_id')
                ->where(['pr_seguradora_data.process_id'=>$ProcessModel->id,'pr_seguradora_data.process_prod'=>'boleto_seg'])
                ->whereIn('pr_seguradora_data.status',['p','a'])//captura todos os status: p - aguardando robo, a - em andamento
                ->where(function($query){
                                return $query
                                        ->where('pr_seguradora_data.process_next_at','<=',date('Y-m-d H:i:s'))
                                        ->orWhere(['pr_seguradora_data.process_next_at'=>null]);
                            })
                ->take( 500 )  //limite de registros para comparar abaixo
                ->orderBy('process_rel_id','asc')   //orderna para que os registros mais antigos adicionado na fila sejam os primeiros a serem processados
                ->get();

        if($regs->count()==0){//não tem registros para processar, portanto finalizado o registro
            $ProcessModel->update(['process_status'=>'f']);//f - finalizado
            $ProcessModel->setData('error_msg','');
            return ['repeat'=>true];
        }

        //caminho relativo da pasta do upload do robô (para o ftp)
            //$filename_tmp = $this->getFilenameToZip($ProcessExecModel);//captura o nome do arquivo temporário por ftp
            $path = $ProcessModel->getPaths();
            if(!file_exists($path['upload_robo']))(new Filesystem)->makeDirectory($path['upload_robo'], 0777, true, true);

        //obs: tira a pasta inicial upload_robo/, pois este processo acessado por um usuário de FTP adicional que só terão permissão a partir da pasta 'upload_robo' (e esta mesma não aparece no diretório FTP)
            $ftp_dir = $path['relative_upload_robo'];
            $ftp_dir = ltrim($ftp_dir,'upload_robo');
            $ftp_dir = ltrim($ftp_dir,'/');


        $r=[
            //'filename_tmp'=>$filename_tmp,
            'ftp_host'=>env('ROBO_FTP_HOST'),
            'ftp_user'=>env('ROBO_FTP_USER'),
            'ftp_pass'=>($method=='GET' ? '*******' : env('ROBO_FTP_PASS') ),
            'ftp_dir'=>$ftp_dir,
        ];


        $dtmin=''; $dtmax='';
        //descobre a data máxima e mínima
        foreach($regs as $reg){
            $reg->_tmp = $reg->getSegData();
            $d = $reg->_tmp['data_emissao']??'';

            if($dtmin=='' || ValidateUtility::ifDate($d,'<', $dtmin))$dtmin=$d;
            if($dtmax=='' || ValidateUtility::ifDate($d,'>', $dtmax))$dtmax=$d;
        }
        $dtmin = FormatUtility::convertDate($dtmin);
        $dtmax = FormatUtility::convertDate($dtmax);

        /**** Atualização 22/07/2021 ****
         * Todos os registros de pr_seguradora_data que estiverem relacioandos com process_robot[seguradora_data] precisam serem carregados abaixo para processamento,
        /*
        //lógica abaixo: os registros não podem ter o intervalo da data de emissão superior a 30 dias para evitar erros ao pesquisar nos sites das seguradoras
        $n = FormatUtility::dateDiffFull($dtmin, $dtmax, 'd1');//diferença de dias entre as datas
        if($n>self::$max_days_get_process){//quer dizer que a data máxima é superior ao limite de tempo entre a data mínima da emisaão
            $dtmax = date('Y-m-d', strtotime($dtmin . ' + '. self::$max_days_get_process .' days'));//está no formato yyyy-mm-dd
        }
        *********************************/

        $i=1; $ids=[];
        //monta a array apenas com as datas dentro de $min e $max
        foreach($regs as $reg){
            $d = $reg->_tmp;
            //if(ValidateUtility::ifDate($d['data_emissao'],'>', $dtmax))continue;//data máxima acima do permitido //*** desativado (atualização 22/07/2021)

            $ids[]=$reg->id;
            $r['process_id_'.$i]            = $reg->id;
            $r['apolice_num_'.$i]           = $d['apolice_num']??'';
            $r['apolice_num_quiver_'.$i]    = $d['apolice_num_quiver']??'';
            $r['segurado_nome_'.$i]         = $d['segurado_nome']??'';
            $r['segurado_doc_'.$i]          = $d['segurado_doc']??'';
            $r['tipo_pessoa_'.$i]           = $d['tipo_pessoa']??'';
            $r['data_emissao_'.$i]          = $d['data_emissao']??'';
            $r['inicio_vigencia_'.$i]       = $d['inicio_vigencia']??'';
            $r['termino_vigencia_'.$i]      = $d['termino_vigencia']??'';
            $r['fpgto_n_prestacoes_'.$i]    = $d['fpgto_n_prestacoes']??'';
            $r['proposta_num_'.$i]          = $d['proposta_num']??'';
            $r['process_prod_'.$i]          = $reg->process_prod;

            $i++;
            if(($i-1)>=self::$max_records_get_process)break; //já atingiu o tamanho do lote para comparar
        }

        if($i==1){//quer dizer que ocorreu algum erro na verficação acima, pois não existem registros disponíveis
            $ProcessModel->update(['process_status'=>'e']);//f - finalizado
            $ProcessModel->setData('error_msg','wbot02');//Dados insuficientes para o robô processar
            return ['repeat'=>true];
        }

        //formata em dd/mm/aaaa
        $dtmin = FormatUtility::dateFormat($dtmin,'date');
        $dtmax = FormatUtility::dateFormat($dtmax,'date');

        //if(\Auth::user()->user_level=='dev')dd($i,$r);

        $r['process_count']=$i-1;
        $r['process_emissao_dt_min']=$dtmin;
        $r['process_emissao_dt_max']=$dtmax;
        //dd($r,$ids);
        //atualiza todos os registros selecionados acima da tabela pr_process_robot para o status='a' - em andamento
        $thisClass->PrSeguradoraData->where(['process_id'=>$ProcessModel->id,'process_prod'=>'boleto_seg'])->whereIn('process_rel_id',$ids)->update(['status'=>'a']);

        return $r;
    }


    //Método utilizado a partir de ProcessSeguradoraDataController@wsrobot_data_set_process
    //Obs: este método não precisa de retorno
    public function wsrobot_data_set_process($params, $thisClass){
        /* Lógica:
         * 1) Verifica cada registro do json retornado em $data_robot para verificar se o boleto está gravado com sucesso
         * 2) Procura o arquivo zip (com o nome padrão {process_id}_boleto_all.pdf) no diretório enviado por ftp, descompacta-o,
         *      acessa a pasta de cada processo retornado com sucesso (esperado arquivo do boleto com o nome boleto_{N}.pdf)
         *      e move os para a pasta do processo de cadastro de apólice associado, ex \storage\accounts\{account_id}\cad_apolice\automovel\{yyyy}\{mm}\{process_id}\boleto_seg
         *      Nesta mesma pasta é gerado o arquivo {process_id}_boleto_seg.data com dados serializados deste retorno
         * 3) Adiciona o processo seguradora_data.boleto_quiver, para que estes respectivos boletos possam ser anexados no quiver
         * 4) Depois finaliza ecom o status 'f' ou 'w'
         *
         * Ex de json esperado em $data_robot:
         *  {
                "1234":{  //process_id
                  "status_code": "ok",
                  "parcelas":{
                    "2":{"valor":"456.12","datavenc":"10/08/2021"},
                    "3":{"valor":"456.12","datavenc":"10/19/2021"},
                    "4":{"valor":"456.12","datavenc":"10/10/2021"},
                  }
                },
                "5678":{"status_code":"err"},
            }
         *
         * Obs: faz a leitura de toda a var $data_robot mesmo que $status='e', pois o status de 'f' ocorrerá apenas se todos os registros forem finalizados
         *      em resumo, se na var $data_robot pelo menos 1 registro retornar ok, já finalizado este registro que deu certo
         */

        extract($params);

        if($status=='e'){//erro geral do processo, mas mesmo assim marca cada registro que foi enviado para processamento como erro (pois assim fica melhor controlar todo o processo de erros e finalizados)
            $thisClass->PrSeguradoraData->where(['process_id'=>$ProcessModel->id,'process_prod'=>'boleto_seg','status'=>'a'])->update(['status'=>'e']);
            //dd(123);
            return false;
        }


        //interrompe a função, pois abaixo só deve processar se estiver finalizado ok (pois se não receber o arquivo zip, não tem o que fazer nesta parte do processo)
        if($status!='f')return false;

        //para o caso de reprocessamentos
        $insurer_basename = $ProcessModel->insurer->insurer_basename;
        $next_at = BoletoSegConfig::getNextAt($insurer_basename,'reprocess');

        $fileSystem = new Filesystem;

        //*** $status=='f' - daqui para baixo faz considerando que retornou ok do robô ***
        //captura o caminho do ftp e nome do arquivo zip
            //$filename_tmp = $this->getFilenameToZip($ProcessExecModel);
            $path = $ProcessModel->getPaths();
            //$filezip = $path['upload_robo']. DIRECTORY_SEPARATOR . $filename;
            //if(!file_exists($filezip) && $status_code=='ok')return $this->wsrobot_data_setError($ProcessModel,'sdbs01');//Erro ao localizar arquivo zip na pasta de ftp
            //$filezip = $path['upload_robo']. DIRECTORY_SEPARATOR;

        /* //atualização 12/01/2022 - o arquivo virá no formato pdf ao invés de vir compactado (pois será sempre um só arquivo)
        //descompacta o arquivo
            if(file_exists($filezip)){
                $zip = new \ZipArchive;
                if($zip->open($filezip)!==true)return $this->wsrobot_data_setError($ProcessModel,'sdbs02');//Falha ao abrir arquivo zip para extração
                //extrai na mesma pasta
                if($zip->extractTo($path['dir'])!==true)return $this->wsrobot_data_setError($ProcessModel,'sdbs03');//Falha ao extrair arquivo
                $zip->close();
            }
            */
            //######### desnecessário abaixo (apenas conferir) e deletar!!!
            //move o arquivo da var $filezip para $path['dir']
            /*$r = $fileSystem->move($filezip,$path['dir'].'/'.$filezip);
            if(!$r){
                sleep(3);
                $r = $fileSystem->move($filezip,$path['dir'].'/'.$filezip);
                if(!$r){
                    return $this->wsrobot_data_setError($ProcessModel,'sdbs05');//Falha ao extrair arquivo
                }
            }*/



        $modelCadApolice = $thisClass->getProcessModel('cad_apolice');
        //dd($params,$data_robot);
        //em $data_robot contém todos os dados retornados de vários registros, sintaxe: [process_id => [dados=>...,parcelas...,{prodname}], .... ]
        foreach($data_robot as $process_id=>$data_item){
            $model_item = $modelCadApolice
                        ->whereRaw('process_robot.id = (select pr.process_rel_id from pr_seguradora_data pr where pr.process_rel_id=process_robot.id and pr.process_id=? and pr.process_prod=?)',[ $ProcessModel->id, self::$process_prod ]) //verifica no sql se o id é um registro da tabela pr_seguradora_data associado ao id principal
                        ->find($process_id);
            if(!$model_item)continue;//não achou o registro
            $code = $data_item['status_code'];
            //dd($code,$model_item,$path);
            //if(in_array($code,['ok','ok2'])){
            if($code=='ok'){//tem boletos para baixar
                //move os arquivos dos boletos para a pasta do cadastro de apólice
                //$path_from = $path['dir_final'] . DIRECTORY_SEPARATOR . $process_id;
                $path_from = $path['upload_robo']. DIRECTORY_SEPARATOR . $process_id .'_boleto_all.pdf';
                $path_to = $model_item->baseDir()['dir_final'] . DIRECTORY_SEPARATOR . 'boleto_seg';
                if(!file_exists($path_to))$fileSystem->makeDirectory($path_to, 0777, true, true);
                $path_to .= DIRECTORY_SEPARATOR .'boleto_all.pdf';//cria a pasta boleto_seg
                $r = $fileSystem->moveDirectory($path_from, $path_to, true);
                //dd('a02',$path_from, $path_to, file_exists($path_from), file_exists($path_to), $r);

                if($r===true){//deu certo ao mover os arquivos
                    //captura os dados atuais do boleto e armazena considerando sempre o id da tabela process_robot_execs, para evitar que o conteúdo seja sobrescrito
                    $data_boleto_seg = $model_item->getText('boleto_seg')??[];
                    $data_boleto_seg[$ProcessExecModel->id] = $data_item;
                    $model_item->setText('boleto_seg',$data_boleto_seg);

                    $this->wsrobot_data_setError_relItem($ProcessModel->id,$thisClass->PrSeguradoraData,$model_item,'w',$code,$thisClass);//f - finalizado sem alterações, w finalizado com alterações

                    //Adiciona o processo seguradora_data.boleto_quiver, para que estes respectivos boletos possam ser anexados no quiver
                    $thisClass->addProcessCheck($model_item,'boleto_quiver');

                }else{//falhou ao mover os arquivos
                    $this->wsrobot_data_setError_relItem($ProcessModel->id,$thisClass->PrSeguradoraData,$model_item,'e',$code,$thisClass);//e - erro
                }

                //deleta a pasta de origem dentro de seguradora_data/boleto_seg (caso por alguma motivo, não tenha movido com sucesso)
                $fileSystem->deleteDirectory($path_from);

            }elseif(in_array($code,['ok2','segd06','segd07'])){//não tem boletos disponíveis para baixar: ok2 (finalizado sem alterações), segd06 (Apólice não encontrada na seguradora (provável credencial incorreta), segd07 (Boleto não disponível - Todas as parcelas pagas)
                $path_from = $path['dir_final'] . DIRECTORY_SEPARATOR . $process_id;
                $fileSystem->deleteDirectory($path_from);//deleta a pasta de origem dentro de seguradora_data/boleto_seg
                $this->wsrobot_data_setError_relItem($ProcessModel->id,$thisClass->PrSeguradoraData,$model_item,'f',$code,$thisClass);//f - finalizado sem alterações, w finalizado com alterações

            }elseif(in_array($code,['segd08'])){//encontrado apenas o boleto da primeira parcela, precisa reagendar para outro dia para tentar novamente
                $this->wsrobot_data_setError_relItem($ProcessModel->id,$thisClass->PrSeguradoraData,$model_item,'p',$code,$thisClass,$next_at);//p - aguardando o robô

            }else{
                $this->wsrobot_data_setError_relItem($ProcessModel->id,$thisClass->PrSeguradoraData,$model_item,'e',$code,$thisClass);//e - erro
            }
            //dd($params, $path_from , $path_to, $data_item);
        }
        //dd('c',$params,$zip);

        //como o arquivo na pasta temporária de ftp já foi utilizado, pode ser removido (obs: será removido junto o zip que está nesta pasta)
        //$fileSystem->deleteDirectories($path['upload_robo']);//remove as subpastas
        //if(file_exists($filezip))$fileSystem->delete($filezip);//remove o arquivo zip

        //sem retorno
    }
        //complemento de wsrobot_data_set_process() - ações de finalização do registro
        private function wsrobot_data_setError_relItem($id,$modelSeguradoraData,$model_item,$status,$status_code,$thisClass,$next_at=null){
            $model_item->setData('boleto_seg',$status);
            $m = $modelSeguradoraData->where(['process_id'=>$id,'process_rel_id'=>$model_item->id,'process_prod'=>self::$process_prod])->first();
            $m->update(['status'=>$status,'finished_at'=>date('Y-m-d H:i:s'),'process_next_at'=>$next_at]);

            $n=' - Status: '. strtoupper($status) . ' ('. strtoupper($status_code).')';
            $m->addLog('down', $thisClass::getStatusCode($status_code,false) . $n);
        }

        //complemento de wsrobot_data_set_process() - seta um erro no registro de $ProcessModel
        private function wsrobot_data_setError($ProcessModel,$code){
            $ProcessModel->update(['process_status'=>'e']);
            $ProcessModel->setData('error_msg', $code);
            return ['status'=>'E','msg'=>$code];
        }


    /** (decartado!)
     * Gera um nome de arquivo para o arquivo enviado pelo robô via FTP
     * @param $execModel - model da tabela process_robot_execs da execução atual
     * @param $force - se true regrava caso exista. Defautl false.
     */
    /*private function getFilenameToZip($execModel){
        return $execModel->process_id.'_boleto_seg_'.$execModel->id.'.zip';
    }*/

}
