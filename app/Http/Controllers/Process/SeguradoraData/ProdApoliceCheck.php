<?php

namespace App\Http\Controllers\Process\SeguradoraData;
use App\Services\PrSegService;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;

/**
 * Classe responsável pelo processo de ações nos sites das seguradoras: verificação de dados da apólice
 * Válido para o processo de cadastro de apólice (process_name=cad_apolice)
 */
class ProdApoliceCheck{
    private static $process_name = 'seguradora_data';
    private static $process_prod = 'apolice_check';
    private static $max_records_get_process=50;//número máximo de registros da tabela pr_seguradora_data retornados no process $this->wsrobot_data_getAfter_process()
    private static $max_days_get_process=30;//número máximo de dias a partir do registro mais recente para retornar aos registros (ex: =30 - não retorna a resultados com mais de 30 dias da data de emissão)
    
    private $PrSegService;
    
    public function __construct() {
        $this->PrSegService = new PrSegService();
    }

    
    /**
     * Captura os dados extraídos do site da seguradora (process_name=apolice_check)
     * @param ProcessRobotCadApolice $model
     */
    public function getDataApoliceCheck($model){
        $data = $model->getText('apolice_check');//obs: aqui os dados já estão formatados
        if(is_array($data) && in_array($data['status_code'],['ok','ok2'])){
            return $data;
        }else{
            return null;
        }
    }
    
    /**
     * Mescla os dados da função getDataPdf() com getDataApoliceCheck()
     * @param $pdf = \App\Services\PrSegService->getDataPdf()
     * @param $apoliceCheck = $this->getDataApoliceCheck()
     * @return dados mesclados
     */
    public function mergeDataPdfApoliceCheck($pdf,$apoliceCheck,$prod_name){
        if($pdf && $apoliceCheck){
            if($apoliceCheck['dados']??false){
                foreach($apoliceCheck['dados'] as $f=>$v){
                    $pdf[$f]=$v;
                }
            }
            foreach(['parcelas',$prod_name] as $tb){
                if($apoliceCheck[$tb]??false){
                    foreach($apoliceCheck[$tb] as $i=>$arr){
                        foreach($arr as $f=>$v){
                            $pdf[$f.'_'.$i]=$v;
                        }
                    }
                }
            }
        }
        return $pdf ?? $apoliceCheck;
    }
    
    
    
    /**
     * Verifica quais os dados de getDataPdf() com getDataApoliceCheck() são diferentes.
     * @param $pdf = \App\Services\PrSegService->getDataPdf()
     * @param $apoliceCheck = $this->getDataApoliceCheck()
     * @return array - nomes dos campos - ex: [field1,field2,...] ou true caso sejam iguais
     */
    public function equalDataPdfApoliceCheck($pdf,$apoliceCheck,$prod_name){
        $r=[];
        if($pdf && $apoliceCheck){
            if($apoliceCheck['dados']??false){
                foreach($apoliceCheck['dados'] as $f=>$v){
                    if($pdf[$f]!=$v)$r[]=$f;
                }
            }
            foreach(['parcelas',$prod_name] as $tb){
                if($apoliceCheck[$tb]??false){
                    foreach($apoliceCheck[$tb] as $i=>$arr){
                        foreach($arr as $f=>$v){
                            if($pdf[$f.'_'.$i]!=$v)$r[]=$f.'_'.$i;
                        }
                    }
                }
            }
        }
        return $r?$r:true;
    }
    
    
    /**
     * Retorna a classe do serviço App\Services\PrCadApoliceService
     */
    private $servicePrCadApolice=null;
    private function servicePrCadApolice(){
        if(!$this->servicePrCadApolice)$this->servicePrCadApolice = new \App\Services\PrCadApoliceService;
        return $this->servicePrCadApolice;
    }
    
    
    //Método utilizado a partir de ProcessSeguradoraDataController@wsrobot_data_getAfter_process
    public function wsrobot_data_getAfter_process($params,$thisClass){
        extract($params);
        
        //captura a relação de apólices a serem enviadas
        $regs=$thisClass->getProcessModel('cad_apolice')->select('process_robot.*')
                ->join('pr_seguradora_data', 'process_robot.id', '=', 'pr_seguradora_data.process_rel_id')
                ->where(['pr_seguradora_data.process_id'=>$ProcessModel->id,'pr_seguradora_data.process_prod'=>'apolice_check'])
                ->whereIn('pr_seguradora_data.status',['p','a'])//captura todos os status: p - aguardando robo, a - em andamento
                ->take( 500 )  //limite de registros para comparar abaixo
                ->orderBy('process_rel_id','asc')   //orderna para que os registros mais antigos adicionado na fila sejam os primeiros a serem processados
                ->get();
        if($regs->count()==0){//não tem registros para processar, portanto finalizado o registro
            $ProcessModel->update(['process_status'=>'f']);//f - finalizado
            $ProcessModel->setData('error_msg','');
            return ['repeat'=>true];
        }
        
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
        
        //lógica abaixo: os registros não podem ter o intervalo da data de emissão superior a 30 dias para evitar erros ao pesquisar nos sites das seguradoras
        $n = FormatUtility::dateDiffFull($dtmin, $dtmax, 'd1');//diferença de dias entre as datas
        if($n>self::$max_days_get_process){//quer dizer que a data máxima é superior ao limite de tempo entre a data mínima da emisaão
            $dtmax = date('Y-m-d', strtotime($dtmin . ' + '. self::$max_days_get_process .' days'));//está no formato yyyy-mm-dd
        }
        
        $r=[]; $i=1; $ids=[];
        //monta a array apenas com as datas dentro de $min e $max
        foreach($regs as $reg){
            $d = $reg->_tmp;
            if(ValidateUtility::ifDate($d['data_emissao'],'>', $dtmax))continue;//data máxima acima do permitido
            
            $id[]=$reg->id;
            $r['process_id_'.$i]            = $reg->id;
            $r['apolice_num_'.$i]           = $d['apolice_num']??'';
            $r['apolice_num_quiver_'.$i]    = $d['apolice_num_quiver']??'';
            $r['segurado_nome_'.$i]         = $d['segurado_nome']??'';
            $r['segurado_doc_'.$i]          = $d['segurado_doc']??'';
            $r['tipo_pessoa_'.$i]           = $d['tipo_pessoa']??'';
            $r['data_emissao_'.$i]          = $d['data_emissao']??'';
            $r['inicio_vigencia_'.$i]       = $d['inicio_vigencia']??'';
            $r['termino_vigencia_'.$i]      = $d['termino_vigencia']??'';
            $r['proposta_num_'.$i]          = $d['proposta_num']??'';
            
            if($i>=self::$max_records_get_process)break; //já atingiu o tamanho do lote para comparar
            $i++;
        }
        
        if($i==1){//quer dizer que ocorreu algum erro na verficação acima, pois não existem registros disponíveis
            $ProcessModel->update(['process_status'=>'e']);//f - finalizado
            $ProcessModel->setData('error_msg','wbot02');//Dados insuficientes para o robô processar
            return ['repeat'=>true];
        }
        
        //formata em dd/mm/aaaa
        $dtmin = FormatUtility::dateFormat($dtmin,'date');
        $dtmax = FormatUtility::dateFormat($dtmax,'date');
        
        $r['process_count']=$i-1;
        $r['process_emissao_dt_min']=$dtmin;
        $r['process_emissao_dt_max']=$dtmax;
        
        //atualiza todos os registros selecionados acima da tabela pr_process_robot para o status='a' - em andamento
        $thisClass->PrSeguradoraData->where(['process_id'=>$ProcessModel->id,'process_prod'=>'apolice_check'])->whereIn('process_rel_id',$ids)->update(['status'=>'a']);
        
        //atualiza o controle geral de verificação da apólice
        foreach($regs as $reg){
            $this->servicePrCadApolice()->add($reg->id,'apolice_check','a','edit',false);//false para não setar o usuário logado
        }
        
        return $r;
    }
    
    
    //Método utilizado a partir de ProcessSeguradoraDataController@wsrobot_data_set_process
    //Obs: este método não precisa de retorno
    public function wsrobot_data_set_process($params, $thisClass){
        /* Lógica: 
         * 1) esta função irá gravar os dados retornados das seguradoras com os dados corretos da apólice
         * 2) Se os dados da extração forem diferentes, os campos que divergirem devem ser atualizados pelo enviado nesta função.
         * 3) Como os dados retornados em $data_robot já estão gravados em dados serializados no diretório, entõa apenas mescla estes resultados no DB substituindo os campos da extração (mas não altera os campos alterados manualmente plo usuário)
         */
        extract($params);
        //dd($params);
        
        $modelCadApolice = $thisClass->getProcessModel('cad_apolice');
        $arr_changes=[];//matriz de campos divergentes
        //dd('x',$data_robot);
        //em $data_robot contém todos os dados retornados de vários registros, sintaxe: [process_id => [dados=>...,parcelas...,{prodname}], .... ]
        foreach($data_robot as $process_id=>$data_item){
            $model_item = $modelCadApolice
                        ->whereRaw('process_robot.id = (select pr.process_rel_id from pr_seguradora_data pr where pr.process_rel_id=process_robot.id and pr.process_id=? and pr.process_prod=?)',[ $ProcessModel->id, self::$process_prod ]) //verifica no sql se o id é um registro da tabela pr_seguradora_data associado ao id principal
                        ->find($process_id);
            if(!$model_item)continue;//não achou o registro
            //
            //salva os dados verificados da seguradora na pasta do respectivo processo
            $model_item->setText('apolice_check',$data_item);
            
            //precisa do status_code='ok' para prosseguir (pois indica que houve alterações)
            if($data_item['status_code']=='ok2'){
                //atualiza o registro do processo inserindo o metadado 'apolice_check' para indicar que foi atualizado com os dados da seguradora
                $this->wsrobot_data_set_process_x1($ProcessModel->id,$thisClass->PrSeguradoraData,$model_item,'f','ok2');//f - finalizado sem alterações
                continue;
            }else if($data_item['status_code']!='ok'){
                $this->wsrobot_data_set_process_x1($ProcessModel->id,$thisClass->PrSeguradoraData,$model_item,'e',$data_item['status_code']);//e - erro ao verificar
                continue;
            }
            //dd($data_item);
            //dados
            $arr = $data_item['dados']??null;
            if($arr){
                $fields_not = $this->PrSegService->getDataCtrlStatus('dados',$model_item->id,'user',true);//retorna somente aos campos que o usuário alterou
                if($fields_not){
                    $fields_not = array_keys($fields_not);
                    //retira os campos que não devem ser atualizados
                    foreach($arr as $f=>$v){
                        if(in_array($f,$fields_not))unset($arr[$f]);
                    }
                }
                ////desativo!!! if($arr)$this->PrSegService->setTableDados($model_item->id,$arr,['model'=>$model_item]);
                if($arr)$arr_changes['dados']=$arr;
            }
            
            //parcelas e produto
            $err_count =['parcelas'=>0,$model_item->process_prod=>0];
            foreach(['parcelas',$model_item->process_prod] as $table){
                    $arr = $data_item[$table]??null;
                    if($arr){
                        //conta quantos itens existem na tabela
                        $n = $this->PrSegService->getTableModel($table)->where(['process_id'=>$process_id])->count();
                        //dd($table,$arr,$n,count($data_item[$table]));
                        if($n!=count($data_item[$table])){//quer dizer que o número de itens cadastrados na tabela é diferente do número de itens retornados na verificação
                            //portanto retorna a erro, pois o programador deve analisar
                            $err_count[$table]++;
                            continue;
                        }//else //prossegue normalmente
                        
                        $fields_not = $this->PrSegService->getDataCtrlStatus($table,$model_item->id,'user',true);//retorna somente aos campos que o usuário alterou
                        //dd($table,$model_item->id,$fields_not);
                        foreach($arr as $i => $arr2){
                            if($fields_not[$i]??false){
                                $arr_not = array_keys($fields_not[$i]);
                                //retira os campos que não devem ser atualizados
                                foreach($arr2 as $f=>$v){
                                    if(in_array($f,$arr_not))unset($arr2[$f]);
                                }
                            }
                            //if($table=='automovel')dd($arr2);
                            if($arr2){//atualiza os dados
                                //desativo!!! $this->PrSegService->setTableSeguro($table,$model_item->id,$arr2,$i);//aqui $i deve ser em zero, e por isto subtrai -1
                                if(!isset($arr_changes[$table]))$arr_changes[$table]=[];
                                if(!isset($arr_changes[$table][$i]))$arr_changes[$table][$i]=[];
                                $arr_changes[$table][$i] = array_merge($arr_changes[$table][$i],$arr2);
                            }
                            //dump([$i, ($arr_not??null), $arr2, $is_user_change ]);
                        }
                    }
            }
            //dd('x',$arr_changes,$data_item);
            if($err_count['parcelas']==0 && $err_count[$model_item->process_prod]==0){//não ocorreram erros de número de itens acima
                    //verifica se ocorreram alterações entre os dados do pdf e o retornado nesta verificação. Obs: o pdf sem a mesclagem dos dados verificados
                    $dataPdf = $this->PrSegService->getDataPdf($model_item,'view','view',false);
                    //dd($dataPdf, $this->getDataApoliceCheck($model_item),$arr_changes);
                    if($this->equalDataPdfApoliceCheck($dataPdf, $arr_changes, $model_item->process_prod)===true){//os dados são iguais, portanto não existem alterações
                        $s='f';
                    }else{//existem alterações
                        $s='w';
                    }
                    //dd($s,$dataPdf, $arr_changes);
                    //atualiza o registro do processo inserindo o metadado 'apolice_check' para indicar que foi atualizado com os dados da seguradora
                    $this->wsrobot_data_set_process_x1($ProcessModel->id,$thisClass->PrSeguradoraData,$model_item,$s,'ok');
                    
            }else{//quer dizer que o número de parcelas ou produtos não bateram, e portanto é considerado erro
                    $a=$err_count['parcelas'];
                    $b=$err_count[$model_item->process_prod];
                    if($a>0 && $b>0){
                        $n='segc01';//Número de itens incompatível
                    }elseif($a>0){
                        $n='segc03';//Parcelas: Número de itens incompatível
                    }else{
                        $n='segc02';//Produto: Número de itens incompatível
                    }
                    $this->wsrobot_data_set_process_x1($ProcessModel->id,$thisClass->PrSeguradoraData,$model_item,'e',$n);
            }
        }
        //dd(111,$s);
        //sem retorno
    }
    
        //complemento de wsrobot_data_set_process() - ações de finalização do registro
        private function wsrobot_data_set_process_x1($id,$modelSeguradoraData,$model_item,$status,$status_code){
            //@param $status - valores f (finalizado sem alteração), w (finalizado com alteração), e (erro)
            //dd($status);
            $model_item->setData('apolice_check',$status);
            $m = $modelSeguradoraData->where(['process_id'=>$id,'process_rel_id'=>$model_item->id,'process_prod'=>self::$process_prod])->first();
            $m->update(['status'=>$status,'finished_at'=>date('Y-m-d H:i:s')]);
            
            if($status=='w' || $status=='f'){//finalizado
                $n=$status=='w' ? 'Dados verificados na seguradora, e contém campos divergentes. Setado nova revisão manual' : 'Dados verificados na seguradora. Sem alterações.';
                $model_item->addLog($status=='w'?'diff':'check', $n);
            }
            
            $n=' - Status: '. strtoupper($status) . ' ('. strtoupper($status_code).')';
            if($status=='f' || $status=='w'){//finalizado
                $m->addLog('check','Dados verificados na seguradora'.$n);
            }else{//erro
                $m->addLog('error','Erro ao verificar os dados na seguradora'.$n);
            }
            
            //atualiza o controle geral de verificação da apólice
            $status_pr=$status;
            if($status=='w'){//finalizado com alterações
                //altera o status para 'n' para que o usuário (talvez programador) revise novamente estes dados
                $status_pr='n';
            }
            $m = $this->servicePrCadApolice()->add($model_item,'apolice_check',$status_pr,'edit',false); //false para não setar o usuário logado
            if(!$m['model']){//achou o cadastro correspondente
                $m = $this->servicePrCadApolice()->add($model_item,'apolice_check',$status_pr,'add',false); //false para não setar o usuário logado
            }
            if($m['model']){//achou o cadastro correspondente
                $m['model']->update(['is_done'=>($status=='f')]);//somente se finalizado sem alterações, é que marcar como concluído
            }
        }
}