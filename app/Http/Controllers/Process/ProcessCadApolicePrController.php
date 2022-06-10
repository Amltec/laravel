<?php
namespace App\Http\Controllers\Process;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;
use Gate;
use Auth;
use Exception;

use App\ProcessRobot\VarsProcessRobot;

use App\Models\ProcessRobot_CadApolice as ProcessRobot;
use App\Models\Insurer;
use App\Models\Broker;
use App\Models\PrCadApolice;
use App\Models\PrCadApoliceData;
use App\Models\ProcessRobotExecs;
use App\Services\PrCadApoliceService;
use App\Services\PrSegService;

/**
 * Controller responsável pelos processo da tabela 'pr_cad_apolice'
 * Complementa as funções desta tabela para o processo 'cad_apolice'
 */
class ProcessCadApolicePrController{
    public static $basename='cad_apolice';
    
    
    public function __construct(ProcessRobot $ProcessRobotModel,PrCadApoliceService $PrCadApoliceService, PrSegService $PrSegService){
        $this->ProcessRobotModel = $ProcessRobotModel;
        $this->PrCadApoliceService = $PrCadApoliceService;
        $this->PrSegService = $PrSegService;
    }
       
    
    /**
     * Página de lista de processos pr_cad_apolice/uploads
     * @param $return - modo de retorno. Valores: view (default), array ids
     */
    public function get_list(Request $request,$return='view'){
        $userLogged = Auth::user();
        $data = $request->all();
        $prefix = \Config::adminPrefix();
        //dd($data);
        
        $filter=[
            'account_id'=>$request->input('account_id'),
            'cpf'=>$request->input('cpf'),
            'nome'=>$request->input('nome'),
            'id'=>$request->input('id'),
            'ids'=>$request->input('ids'),
            'ctrl_id'=>$request->input('ctrl_id'),
            'status'=>$request->input('status'),
            'is_done'=>$request->input('is_done'),
            'process_name'=>self::$basename,
            'dtype'=>$request->input('dtype'),//valores: 'c' cadastro, 'p' processamento
            'dt'=>$request->input('dt'),//date aaaa-mm-d      //aceita também date_start - date_2_end (sintaxe: yyyy-mm-dd - yyyy-mm-dd)
            'dts'=>$request->input('dts'),//date start aaaa-mm-d,
            'dte'=>$request->input('dte'),//date end aaaa-mm-d,
            'broker_id'=>$request->input('broker_id'),
            'insurer_id'=>$request->input('insurer_id'),
            'cfilter'=>$request->input('cfilter'),
            
            //filtros para a tabela pr_cad_apolice
            'pr_process'=>$request->input('pr_process')
        ];
        
        if(!$filter['pr_process'])exit('erro de parâmetro');
        
        if(strpos($filter['dt'],' - ')!==false){
            //separa a data entre data inicial e final
            $n=explode(' - ',$filter['dt']);
            $filter['dts']=$n[0];
            $filter['dte']=$n[1];
            unset($filter['dt']);
        }elseif($filter['dt']){
            $filter['dts']=$filter['dte']=$filter['dt'];
            $filter['dt']=null;
        }
        foreach(['dt','dts','dte'] as $dt){
            if(strpos($filter[$dt],'/')!==false)$filter[$dt]=FormatUtility::convertDate($filter[$dt]);
        }
        
        $model = $this->ProcessRobotModel->select('process_robot.*')->where('process_name',self::$basename);
        
        
        if($filter['account_id'])$model->where('account_id',$filter['account_id']);
        
        if($filter['ids'])$model->whereIn('id',explode(',',$filter['ids']));
        
        if($filter['id'])$model->where('id',$filter['id']);
        if($filter['ctrl_id'] && is_numeric($filter['ctrl_id']))$model->where('process_ctrl_id','like','%'.FormatUtility::extractNumbers($filter['ctrl_id']).'%');
        
        if($filter['nome'] || $filter['cpf']){
            $arr=[];
            if($filter['nome'])$arr['segurado_nome__LIKE']='%'.$filter['nome'].'%';
            if($filter['cpf'])$arr['segurado_doc']=$filter['cpf'];
            if($arr)$model->wherePrSeg('dados',$arr);
            //if($arr)$model->whereData($arr);
            //dump($model->toSql(),$model->getBindings());
        }
        if($filter['broker_id'])$model->where('broker_id',$filter['broker_id']);
        if($filter['insurer_id'])$model->where('insurer_id',$filter['insurer_id']);
        
        //filtros para a tabela pr_cad_apolice
            $arr=[$filter['pr_process']];
            $sql='process_robot.id = '.
                '(select a2.process_id from pr_cad_apolice a2 where a2.process_id=process_robot.id ';
            
                    $sql.='and (select a3.is_done from pr_cad_apolice a3 where a3.process_id=a2.process_id and a3.process=? ';
                        //if($filter['is_done']=='s')$sql.='and a3.user_id is not null ';  //lógica: se user_id<>null então quer dizer que é um registro gerado manualmente pelo usuário, e portanto somente estes registros podem ser marcados como concluído
                        if($filter['status']){$sql.='and a3.status=? ';$arr[]=$filter['status'];}
                        $dt_col = $filter['dtype']=='p'?'finished_at':'created_at';
                        if($filter['dt']){$sql.='and date(a3.'.$dt_col.')=? ';$arr[]=$filter['dt'];}
                        if($filter['dts']){$sql.='and date(a3.'.$dt_col.')>=? ';$arr[]=$filter['dts'];}
                        if($filter['dte']){$sql.='and date(a3.'.$dt_col.')<=? ';$arr[]=$filter['dte'];}
                    $sql.='order by a3.num desc limit 1)=? '; $arr[]=$filter['is_done']=='s';
                    
            $sql.='order by a2.num desc limit 1)';
            //dump($sql,$arr);
            $model->whereRaw($sql, $arr);
        
       
        //if($userLogged->user_level=='dev')dump($model->toSql(),$model->getBindings());
        
        
        
        if($return=='ids'){
            if($userLogged->user_level=='dev'){
                $ids=[];
                $ids=$model->select('id')->take(10000)->pluck('id')->toArray();
                return $ids;
            }else{
                return 'acesso negado';
            }
                    
        }else{//==view
             $model=$model
                ->orderBy('id', 'desc')
                ->paginate(_GETNumber('regs')??15);
             
            //captura a lista de $fileInfoas
            $insurers_list = Insurer::get()->pluck('insurer_alias','id')->toArray();

            //catpura a lista corretores
            $brokers_list = Broker::get()->pluck('broker_alias','id')->toArray();
            
            return view('admin.process_robot.'.self::$basename.'.pr_process.'.$filter['pr_process'].'-list' ,[
                'model'=>$model,
                'filter'=>$filter,
                'configProcessNames'=>VarsProcessRobot::$configProcessNames,
                'insurers_list'=>$insurers_list,
                'brokers_list'=>$brokers_list,
                'status_list'=>$this->PrCadApoliceService::getStatusByProcess($filter['pr_process']),
                'user_logged_level'=>$userLogged->user_level,
                'thisClass'=>$this,
                'pr_process'=>$filter['pr_process'],
                'servPrCadApolice'=>$this->PrCadApoliceService,
            ])->render();
        }
    }
    
    
     
    /**
     * Retorna apenas a relação de ids a partir dos parâmetros querystring informados
     */
    public function post_getIdsByQs(Request $request){
        $qs = $request->input('qs_from');
        parse_str($qs,$qs);
        $request->replace($qs);//substitui os valores do request
        $ids = $this->get_list($request,'ids');
        return ['success'=>true,'ids'=>$ids];
    }
 
    
    /**
     * Altera os status de todos os ids informados
     * Função para o botão 'alterar status' da lista de processos
     */
    public function post_changeAllStatus(Request $request){
        $data   = $request->all();
        
        if(!is_array($data['ids']))return ['success'=>false,'msg'=>'Erro de parâmetro ID'];
        if($data['status']=='')return ['success'=>false,'msg'=>['status'=>'Status não definido']];
        foreach($data['ids'] as $id){
            $r=$this->changeProcessStatus($data['process'],$id,$data['status']);
            if(!$r['success'])return $r;
        }
        return ['success'=>true];
    }
    private function changeProcessStatus($process,$id,$status){
        //obs: sempre que esta função for chamada, indica que a alteração de status foi realizada manualmente pelo usuário        
        $statusList = $this->PrCadApoliceService::$status;
        
        $model = $this->ProcessRobotModel->find($id);
        if($model){
            //verifica se a conta não está cancelada, e neste caso não pode atualizar
            if($model->account->account_status!='a')return ['success'=>false,'msg'=>'Conta cancelada'];
            
            $old_status = $model->process_status;
            $this->PrCadApoliceService->set($model->id,$process,$status,true);//true para setar o usuário logado
            $model->addLog('status','Processo: '.$process.' - Status atualizado de: '. $statusList[$old_status] .'('.$old_status.')  - para: '. $statusList[$status] .'('.$status.')');
        }
        return ['success'=>true];
    }
    
    
    
    
    /**
     * Exibe os dados do arquivo de execução da tabela process_robot_execs
     * @param $type - valores: exec, review, apolice_check (valores da página /process_cad_apolice/file_data_view)
     */
    public function get_FileDataView(Request $request){
        $userLogged = Auth::user();
        if($userLogged->user_level!='dev')exit('Permissão negada');
        
        $process_id = $request->input('process_id');
        $id = $request->input('id');
        $type = $request->input('type');
        
        $processModel = $this->ProcessRobotModel->find($process_id);
        if(!$processModel)exit('Erro de parâmetro 1');
        
        echo '<h3>Process Id '.$process_id.' - Arquivo '.$type.' '.$id.'</h3>';
        
        if($type=='exec'){
            $model = (new ProcessRobotExecs)->where(['process_id'=>$process_id,'id'=>$id])->first();
            if(!$model)exit('Erro de parâmetro 2');
            $n=$model->getText($processModel);
            dump($n);
            dd(json_encode($n));
            
        }elseif($type=='review'){
            $model = $processModel->getPrCadApolice('review');
            dd('... em desenvolvimento ...');
        }
        
        exit;
    }
    
    
    
    /**
     * Adiciona um processno na tabela pr_cad_apolice
     * @param $request - valores esperados:
     *           $ids - string ids separados por virgula ou array da tabela "process_robot.id where process_name=cad_apolice"
     *           $process - nome do process, ex: apolice_check, review, ... (consulte a documentação em xlsx)
     *           $status - valor inicial de status (consulte a documentação em xlsx)
     *           $add_again - se true irá permitir adicionar novamente o registro para processamento (ter mais de um processo regitrado) caso já esteja inserido / processado
     */
    public function post_addProcess(Request $request){
        $ids = $request->input('ids');
        $process = $request->input('process');
        $add_again = $request->input('add_again')=='s';
        
        //status padrão por $process
        $status = [
            'apolice_check' => 'm', //precisa de revisão manual
            'review' => 'p',        //aguardando robo
        ][$process]??null;
        if(!$status)return ['success'=>false,'msg'=>'Nome do processo inválido'];
        
        if(!$ids)return ['success'=>false,'msg'=>'Nenhum registro encontrado'];
        $ids=explode(',',$ids);
        
        $model = $this->ProcessRobotModel->whereIn('id',$ids)->get();
        $ids = array_flip($ids);
        $r=[]; $err=0;
        foreach($model as $reg){
            $n = $this->PrCadApoliceService->add($reg->id,$process,$status, ($add_again?'add+':'add'),true );//true para setar o usuário logado
            $r[]=$reg->id .' - '.$n['msg'];
            if(!$n['success'])$err++;
            unset($ids[$reg->id]);
        }
        if($ids){//se existir, quer dizer que os respectivos ids não existem
            $err=1;
            $r[]=join(',',array_keys($ids)) .' - não existe(m)';
        }
        return ['success'=>$err==0,'msg'=>join('<br>',$r)];
    }
    
    
    
    
    /**
     * Filtra o sql model para que seja aplicado somente aos registros da tabela pr_cad_apolice.process='apolice_check'
     * @param $status - filtro por status (opcional)
     */
    public function whereApoliceCheck($model,$status=''){
        $sql='process_robot.id = (select a2.process_id from pr_cad_apolice a2 where a2.process_id=process_robot.id and a2.process=? ';
        $arr=['apolice_check'];
        if($status){
            $sql.='and a2.status=? ';
            $arr[]=$status;
        }
        $sql.='order by a2.num desc limit 1)';
        return $model->whereRaw($sql,$arr);
    }
    /**
     * Captura a model do registro da tabela pr_cad_apolice para process_robot.process_name='cad_apolice'
     * Caso retorna a null, quer dizer que o registro não existe
     * @param $model - model do registro de process_robot.process_name='cad_apolice'
     */
    public function getApoliceCheck($model,$status,$is_done=null){
        $arr=['process_id'=>$model->id, 'process'=>'apolice_check','status'=>$status];
        if(is_bool($is_done))$arr['is_done']=$is_done;
        return $model ? PrCadApolice::where($arr)->first() : null;
    }
    
    
    /**
     * Carrega a view personalizada da página para edição manual dos dados
     */
    public function get_apoliceCheckRevisaoManual(Request $request,$status=null){
        if($status=='n'){
            //$status='n';
            $view='revisao-manual2';
        }else{
            $status='m';
            $view='revisao-manual';
        }
        
        $id = $request->input('id');
        $model = $this->ProcessRobotModel->find($id);
        $modelPr = $this->getApoliceCheck($model,$status,false);
        if(!$modelPr)exit('Nenhum registro pendente de revisão');
        
        \Config::setItemMenu('cad_apolice-apolice_check');
        
        return view('admin.process_robot.cad_apolice.pr_process.apolice_check-'.$view,[
            'id'=>$request->input('id'),
            'pr_process'=>'apolice_check',
            //'prCadApoliceService'=>$this->PrCadApoliceService,
            'model'=>$model,
            'modelPr'=>$modelPr,
        ]);
    }
     /**
     * Carrega a view personalizada da página para edição manual dos dados
     */
    public function get_apoliceCheckRevisaoManual2(Request $request){
        return $this->get_apoliceCheckRevisaoManual($request,'n');
    }
    
    
    /**
     * Verifica os dados digitados manualmente pelo usuário para verificação dos dados da apólice
     * O objetivo é comparar se os dados digitados estão compatíveis com os dados extraídos do pdf
     */
    public function post_apoliceCheckRevisaoManual(Request $request,int $id){
        $model = $this->ProcessRobotModel->find($id);
        $modelPr = $this->getApoliceCheck($model,'m');
        if(!$modelPr)return ['success'=>false,'msg'=>'Registro inválido'];
        
        //classe do produto (ex automovel)
        $prodClass = $this->PrSegService->getSegClass($model->process_prod);
        $prod_count = (int)$request->input('_prod_count');
        
        //relação de campos
        $arrData=['proposta_num','apolice_num','data_emissao','inicio_vigencia','termino_vigencia','fpgto_premio_total','fpgto_premio_liquido','fpgto_n_prestacoes'];
        $arrProd=$prodClass::fields_review_manual();
        
        //dados originais do pdf
        $dataPdf = $this->PrSegService->getDataPdf($model,'view','view',false);
        
        //compara os valores: dados
        $fields_err=[];
        $fields_ok=[];
        foreach($arrData as $f){
            $v0 = $request->input($f);
            $v = $this->f1RemoveFormat($v0);
            if($v=='')return ['success'=>false,'msg'=>[$f=>'Campo requerido']];
            $d0=$dataPdf[$f]??null;
            $d=$this->f1RemoveFormat($d0);
            if(in_array($f,$arrData) && $v!=$d)$fields_err[$f]=['pdf'=>$d0,'revisado'=>$v0];
            $fields_ok[$f]=$v0;
        }
        //compara os valores: {prod}
        foreach($arrProd as $f){
            for($i=1;$i<=$prod_count;$i++){
                $f2=$f.'_'.$i;
                $v0 = $request->input($f2);
                $v = $this->f1RemoveFormat($request->input($f2));
                if($v=='')return ['success'=>false,'msg'=>[$f2=>'Campo requerido']];
                $d0=$dataPdf[$f2]??null;
                $d=$this->f1RemoveFormat($d0);
                if(in_array($f,$arrProd) && $v!=$d)$fields_err[$f2]=['pdf'=>$d0,'revisado'=>$v0];
                $fields_ok[$f2]=$v0;
            }
        }
        //dd($fields_ok,$fields_err);
        if($fields_err){//quer dizer que os dados não estão compatíveis
            return ['success'=>false,'msg'=>'diff','fields_err'=>$fields_err];
            
        }else{//os dados estão compatíveis
            $modelPr->update(['status'=>'f','finished_at'=>date('Y-m-d H:i:s'),'user_id'=>Auth::user()->id,'is_done'=>true]);//altera para o status 'f' - finalizado sem alterações
            $model->addLog('check',$fields_ok,'cad_apolice.apolice_check.'.$modelPr->num);
            
        };
        return ['success'=>true,'msg'=>'Dados salvos', 'next'=>$this->getNextIdRevisaoManual('m')['route']];
    }
        //Auxiliar de post_apoliceCheckRevisaoManual()
        private function f1RemoveFormat($v){
            return trim(str_replace(['.',',','-','/',' '],'',$v));//remove a formatação
        }
    
    /**
     * Salva a confirmação manual do usuaário registro está divergente
     */
    public function post_apoliceCheckRevisaoManualErr(Request $request,int $id){
        $type = $request->input('type');//valores: diff, diff_manual
        $obs = $request->input('obs');
        $fields_err = $request->input('fields_err');
        
        $model = $this->ProcessRobotModel->find($id);
        $modelPr = $this->getApoliceCheck($model,'m');
        if(!$modelPr)return ['success'=>false,'msg'=>'Registro inválido'];
        
        if($type=='diff_manual'){
            if(!$obs)return ['success'=>false,'msg'=>['obs'=>'Campo requerido']];
        }else{
            if($fields_err)$fields_err= json_decode($fields_err,true);
        }
        $data=[];
        if($obs)$data['obs']=$obs;
        if($fields_err)$data['fields_err']=$fields_err;
        
        $modelPr->update(['status'=>'c','finished_at'=>date('Y-m-d H:i:s'),'user_id'=>Auth::user()->id]);//altera para o status 'c' - precisa de correção manual
        $modelPr->setData('apolice_check_m',$data);
        $model->addLog('diff',$data,'cad_apolice.apolice_check.'.$modelPr->num);
        
        return ['success'=>true,'msg'=>'Dados enviados', 'next'=>$this->getNextIdRevisaoManual('m')['route'] ];
    }
    
    
    /**
     * Apenas marca como concluído, pois neste caso o usuário (talvez o programador) já corrigiu manualmente (pois esta apólice já foi verificada automaticamente pelo processo seguradora_data.apolice_check e precisava apenas da confirmação final do usuário)
     */
    public function post_apoliceCheckRevisaoManual2(Request $request,int $id){
        $obs = $request->input('obs');
        $fields_err = $request->input('fields_err');
        $model = $this->ProcessRobotModel->find($id);
        $modelPr = $this->getApoliceCheck($model,'n',false);//verifica se não está concluído
        if(!$modelPr)return ['success'=>false,'msg'=>'Registro inválido'];
        
        $data=[];
        if($obs)$data['obs']=$obs;
        if($fields_err)$data['fields_err']=json_decode($fields_err,true);
        //dd($data);
        
        $modelPr->update(['finished_at'=>date('Y-m-d H:i:s'),'user_id'=>Auth::user()->id,'is_done'=>true]);//apenas finaliza marcando como concluído
        $modelPr->setData('apolice_check_m',$data);
        $model->addLog('check',$data,'cad_apolice.apolice_check.'.$modelPr->num);
        
        return ['success'=>true,'msg'=>'Dados atualizados', 'next'=>$this->getNextIdRevisaoManual('n')['route'] ];
    }
    
    /**
     * Captura o próximo id do registro para revisão manual
     * @param $status - valores: 'm' (revisão manual 1) ou 'n' (revisão manual 2)   //veja a documento para mais detalhes
     */
    private function getNextIdRevisaoManual($status){
        $prefix = \Config::adminPrefix();
        $modelPr = PrCadApolice::where(['process'=>'apolice_check','status'=>$status])->orderBy('process_id','asc')->orderBy('num','asc')->first();
        if($modelPr){
            $id = $modelPr->process_id;
            return ['id'=>$id, 'route'=>route($prefix.'.app.get',['process_cad_apolice_pr','apolice_check_revisao_manual'.( $status=='n'?'2':'' ),'?id='.$id ]) ];
        }else{
            return ['id'=>null,'route'=>null];
        }
    }
    
    /**
     * Salva informações dos campos
     */
    public function post_changeFields(Request $request){
        $userLogged = Auth::user();
        if($userLogged->user_level!='dev')return ['success'=>false,'msg'=>'Acesso negado'];
        
        $process = $request->input('process');
        $id = $request->input('id');
        $num = $request->input('num');
        $action = $request->input('action');
        
        $model = $this->ProcessRobotModel->find($id);
        if(!$model)return ['success'=>false,'msg'=>'Registro não encontrado'];
        
         
        if($action=='is_done'){
            $is_done = $request->input('is_done')=='s';
            $modelPr = PrCadApolice::where(['process_id'=>$id, 'process'=>$process,'num'=>$num])->first();
            if(!$modelPr)return ['success'=>false,'msg'=>'Registro não encontrado (2)'];
            if($modelPr->is_done==$is_done)return ['success'=>true,'msg'=>'Dados alterados'];
            
            $modelPr->update(['is_done'=>$is_done]);
            $model->addLog('check', ($is_done?'Marcado como Concluído':'Desmarcado como Concluído') ,'cad_apolice.apolice_check.'.$modelPr->num);
        }
        
        return ['success'=>true,'msg'=>'Dados alterados'];
    }
    
    
    
    /**
     * Remove o processo
     */
    public function remove(Request $request){
        $userLogged = Auth::user();
        if($userLogged->user_level!='dev')return ['success'=>false,'msg'=>'Acesso negado'];
        $id = $request->input('id');
        $process = $request->input('process');
        
        $model = $this->ProcessRobotModel->find($id);
        if(!$model)return ['success'=>false,'msg'=>'Registro cad_apolice id '.$id.' não existe'];
        
        PrCadApolice::where(['process'=>$process,'process_id'=>$id])->delete();
        if($process=='apolice_check')PrCadApoliceData::where('process_id',$id)->delete();
        
        $model->addLog('remove','Processo '.$process.' removido pelo usuário '.$userLogged->id, 'cad_apolice.'.$process );
        
        return ['success'=>true,'msg'=>'Removido com sucesso'];
    }
    
    /**
     * Limpa os registros vazios/não concluídos de revisão manual 
     * Considera todos os registros da tabela pr_cad_apolice para process=apolice_check onde é registor manual (user_id<>null) e não está concluído (is_done=false)
     * @param $request - esperado id (pr_cad_apolice.process_id)
     */
    public function post_clearRevisaoManual(Request $request){
        $userLogged = Auth::user();
        if($userLogged->user_level!='dev')return ['success'=>false,'msg'=>'Acesso negado'];
        $ids = $request->input('ids');
        if(!is_array($ids))return ['success'=>false,'msg'=>'Erro de parâmetro ID'];
        
        $PrCadApolice = new PrCadApolice;
        foreach($ids as $id){
            $PrCadApolice->where(['process_id'=>$id,'process'=>'apolice_check','is_done'=>false])->whereNotNull('user_id')->delete();
        }
        
        return ['success'=>true,'msg'=>'Registros removidos com sucesso'];
    }
}