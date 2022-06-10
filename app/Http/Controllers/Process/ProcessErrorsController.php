<?php

namespace App\Http\Controllers\Process;

use App\Models\ProcessRobotErrors;
use Illuminate\Http\Request;
use App\Utilities\FormatUtility;
use App\ProcessRobot\VarsProcessRobot;
use Config;

/**
 * Classe para registrar os erros ocorridos nos processos que requerem ação manual
 */
class ProcessErrorsController {
    private $ModelProcessErrors;
    
    
    /**
     * Retorna ao texto da lista de erros (apenas os erros registrados
     */
    public static function getStatusCode($code){
        $list = [
            'quis02' => 'Login ou credencial do corretor não encontrado',
            'sega01' => 'Dados da apólice não encontrado na busca no site da seguradora',
            'segl02' => 'Página de seguradora não logada ou desconectada',
            'segl03' => 'Dados insuficientes para login na seguradora',
            'segl04' => 'A senha informada expirou, necessário atualizar',
            'segl01' => 'Login ou senha inválido',
            'segd06' => 'Apólice não encontrada na seguradora (provável credencial incorreta)',
            'segl05' => 'Credenciais para login (usuários) não carregada',
            'segl06' => 'Usuário bloqueado na seguradora',
            'segl07' => 'Seguradora requer atualização de senha',
            'quif09' => 'Tipo de imagem da apólice não encontrado na lista de opções',
        ];
        return $list[$code] ?? $code.': - Erro não registrado';
    }
    
    public function __construct(){
        $this->ModelProcessErrors = new ProcessRobotErrors;
    }
    
    /**
     * Seta um registro na tabela process_robot_errors para informação do operador manual
     * Lógica: deve existir apenas um registro por status_code
     * @param bool $ignore_update - se true irá sempre inserir um novo cadastro, mesmo que os registros se repetam, se false (default) irá atualizar caso exista
     */
    public function registerError($processModel,$status_code,$callback,$ignore_update=false){
        //prossegue apenas se não existir
        $m = $this->ModelProcessErrors->where([
            'account_id'=>$processModel->account_id,
            'process_name'=>$processModel->process_name,
            'process_prod'=>$processModel->process_prod,
            'broker_id'=>$processModel->broker_id,
            'insurer_id'=>$processModel->insurer_id,
            'status_code'=>$status_code,
            'status'=>'0',
        ])->first();
        if($m && $ignore_update==false){
            $m->update(['created_at'=>date('Y-m-d H:i:s')]);//atualiza a data da criação, pois só existe um registro para as condições acima
        }else{
            $this->ModelProcessErrors->create([
                'account_id'=>$processModel->account_id,
                'process_name'=>$processModel->process_name,
                'process_prod'=>$processModel->process_prod,
                'broker_id'=>$processModel->broker_id,
                'insurer_id'=>$processModel->insurer_id,
                'status_code'=>$status_code,
                'status'=>'0',
                'callback'=>$callback ? serialize($callback) : null,
            ]);
        }
    }
    
    
    /**
     * Marca como finalizado e inicia os processos parados
     */
    public function post_finish(Request $request){
        $id = $request->id;
        $account_id = Config::accountID();
        $prefix = Config::adminPrefix();
        if($prefix=='super-admin' && !$account_id){
            $m = $this->ModelProcessErrors->find($id);
        }else{
            $m = $this->ModelProcessErrors->where('account_id',$account_id)->find($id);
        }
        
        if(!$m)return;
        //dispara o callback - esperado array[class,params]
        $c=$m->callback;
        if($c){
            $c = unserialize($c);
            $r=\App::call($c['class'],[$m, $c['params']]);
        }
        
        //marca como finalizado
        $m->update(['status'=>'f']);
        
        return ['success'=>true];
    }
    
    
    /**
     * Retorna a lista erros registrados
     */
    public function get_data($opts=[]){
        $opt=array_merge([
            'regs'=>null,
            'account_id'=>null,
        ],$opts);
        
        $account_id = \Config::accountID();
        if(!$account_id)$account_id = $opt['account_id'];
        $prefix = Config::adminPrefix();
        $model = $this->ModelProcessErrors->where('status','0');
        
        if($prefix=='super-admin' && !$account_id){
            //nenhuma ação
        }else{
            $model->where('account_id',$account_id);
        }
        $model = $model->paginate($opt['regs']??9999);
        
        $r=[];
        foreach($model as $reg){
            try{
                $cb = unserialize($reg->callback);
            }catch (\Exception $e){
                $cb = [];
            }
            
            $r[] = (object)[
                'id'=>$reg->id,
                'status_code'=>$reg->status_code,
                'process_prod'=>$reg->process_prod,
                'process_name'=>$reg->process_name,
                'process_label'=> VarsProcessRobot::$configProcessNames[$reg->process_name]['products'][$reg->process_prod]['title_cli'],
                'error'=>array_get($cb,'params.msg',self::getStatusCode($reg->status_code)),
                'broker_id'=>$reg->broker_id,
                'insurer_id'=>$reg->insurer_id,
                'broker'=>$reg->broker->broker_alias,
                'insurer'=>$reg->insurer->insurer_alias,
                'created_at'=>FormatUtility::dateFormat($reg->created_at),
                'account_id'=>$reg->account_id,
                'account_name'=>$reg->account->account_name,
                'params'=>array_get($cb,'params'),
            ];
        }
        return ['total'=>$model->total(),'data'=>$r];
    }
    
    
    /**
     * Página que lista todos os erros
     */
    public function get_list(){
        return view('templates.pages.page', [
            'title'=>'Pendências de Configuração do Operador',
            'content'=> function(){
                echo view('super-admin.index-inc--process-errors',[
                    'regs'=>null,
                ]);     
            },
        ]);
    }
    
    
    /**
     * Remove o erro registrado
     */
    public function post_remove(Request $request){
        $account_id = \Config::accountID();
        $id = $request->id;
        $prefix = Config::adminPrefix();
        if($prefix=='super-admin' && !$account_id){
            $m = $this->ModelProcessErrors->find($id);
        }else{
            $m = $this->ModelProcessErrors->where('account_id',$account_id)->find($id);
        }
        if($m){
            $m->delete();
        }
        return ['success'=>true];
    }
    
}
