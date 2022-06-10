<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FileExtractText;
use Auth;

/**
 * Classe para receber todas as requsições do aplicativo do robô
 * Todas as requisições para esta classe não são autenticadas
 */
class WSFileExtractTextController extends Controller{

    //chave usada para autenticar as requisições POST do autoit para esta aplicação
    private static $key_autoit = 'qGzSNYokPB36QVmVYj4B80CFrp3yUmxs';


    public function __construct(FileExtractText $FileExtractTextModel){
        $this->FileExtractTextModel = $FileExtractTextModel;
    }



    //**************** Funções de acesso interno diretamente pela classe ****************//

    /**
     * Retorna ao conteúdo da tabela caso o registro não esteja lido
     * @para $remove - se true irá remover o registro automaticamente após carregar os dados. Default false.
     * Return array[success, msg, text, file_url, file_path]    //obs: (text, file_url, file_path   somente success=true)
     */
    public static function load($id,$remove=true){
        $model = self::getModel()->find($id);
        if(!$model)return ['success'=>false,'msg'=>'Registro de extração não encontrado'];
        if(in_array($model->status,['a','0']))return ['success'=>false,'msg'=>'Extração em andamento','processing'=>true];
        $r=['success'=>true,'text'=>$model->file_text,'url'=>$model->file_url,'path'=>$model->file_path];
        if($remove)$model->delete();
        return $r;
    }

    /**
     * Adiciona o registro a ser processado na tabela files_extract_text
     * @param $engine - precisa iniciar com 'ait_...'
     * @param $pass - senha do arquivo. Esperado uma string json - sintaxe: ['1234','4321',...]
     * @param $callback - ex de valor: \App\Http\Controllers\ClassController@mehod.
     * @return boolean false para erro ou com a model do registro inserido
     */
    public static function add($engine,$file_url,$file_path,$area_name,$area_id,$pass=null,$callback=null){

        //verifica se o método existe
        if($callback){
            $n = explode('@',$callback);
            if( !class_exists($n[0]??'') )return false;
            if( !method_exists($n[0],($n[1]??'')) )return false;
        }

        if($pass){
            if(is_array($pass))$pass = json_encode($pass);
        }

        //verifica se já foi adicionado para evitar duplicação
        $model = self::getModel()->where(['area_name'=>$area_name,'area_id'=>$area_id,'file_url'=>$file_url])->first();
        if($model){
            return $model;
        }else{
            //adiciona
            return self::getModel()->create([
                'file_url'=>$file_url,
                'file_path'=>$file_path,
                'area_name'=>$area_name,
                'area_id'=>$area_id,
                'created_at'=>date('Y-m-d H:i:s'),
                'status'=>'0',//=0 - não iniciado
                'engine'=>$engine,
                'pass'=>$pass,
                'callback'=>$callback,
            ]);
        }
    }


    //**************** Funções de acesso externo - Robô AutoIt ****************//

     /**
     * Solicitação de processo pelo robo de extração.
     * Request POST|GET esperados:
     *      key          - token de ativação
     *      id           - id do processo (tabela process_robot.id)
     *      status       - valor de status (tabela process_robot.robot_status), valores:
     *                          R - ok
     *                          E - erro
     *                          T - Tentar novamente. Se definido, não altera o status e apenas atualiza o campo 'process_next_at' para que seja tentado novamente no futuro
     *      msg          - mensagem de retorno (pode ser mensagem de erro - opcional)
     *      action       - nome da ação - valores:
     *          'set_process'      - (POST) alteração de dados do processo. Parãmetros esperados: id, status, msg
     *          'get_process'      - (POST|GET) captura de dados do processo. Parãmetros esperados: id.
     *                                      Obs: caso não informado o id, irá exibir o primeiro registro registro disponível para ser processado
     *                                      Obs2: Somente esta ação é permitida via método GET
     *
     * @return string xml [status, msg, action, data...]
     * @obs valores de 'status' retornados nesta função: R - ok, E - erro, A - aguardar e tentar novamente
     */
    public function data(Request $request){
        $method = $request->method();//GET, POST
        $key    = $request->input('key_active');//token de instalação do robô
        $action = $request->input('action');//ação
        //$userLogged = \Auth::user();

        if($method=='GET'){
            if($action!='get_process')return $this->wsrobot_return(['status'=>'E','msg'=>'Acesso negado']);
            //tem que estar logado e ter permissão 'dev'
            if(!Auth::check())return $this->wsrobot_return(['status'=>'E','msg'=>'Acesso negado(2)']);
            if(Auth::user()->user_level!=='dev')return $this->wsrobot_return(['status'=>'E','msg'=>'Acesso negado(3)']);

        }else{//POST
            //valida o campo $key
            if($key!==self::$key_autoit)return $this->wsrobot_return(['status'=>'E','msg'=>'Acesso negado(4)']);
        }

        if( $action=='get_process'){
            return $this->get_process($request);
        }elseif($action=='set_process'){
            return $this->set_process($request);
        }else{
            exit('Ação inválida');
        }
    }




    /**
     * Recebe a solicitação do arquivo a ser processado pelo robô Autoit
     * Retorna ao primeiro arquivo da fila.
     * @return array - [status(R|A), id, file-url, engine]
     */
    private function get_process($request){
        //tempo em minutos que irá durar a expiração de registros travados
        $lock_minutes = 10;//obs: 10min pois no autoit será extraído até 8 páginas por documento, e muitaz vezes demora bastante... este é o tmepo máximo
        $lock_expire = date('Y-m-d H:i:s', strtotime('-'.$lock_minutes.' min', strtotime(date('Y-m-d H:i:s'))) );

        //conta quantos registros existem em andamento e libera apenas se houver mais que 3 em andamento
        $count_st_a = $this->FileExtractTextModel->where('status','a')->count();
        //dd($count_st_a);
        if($count_st_a>3)return $this->wsrobot_return(['status'=>'A']);//não exibe registros para processar, pois no momento existe apenas um robô que pode gerar muitos processos consecutivos


        $model = $this->FileExtractTextModel->whereIn('status',['0','a']);
        $filter_id = $request->input('id');
        if($filter_id){
            $model=$model->find($filter_id);
        }else{
            $model->where(function($query) use($lock_expire){
                return $query->orWhereNull('locked_at')->orWhere('locked_at','<=',$lock_expire);
            });
            $model=$model->orderBy('id', 'asc')->first();
        }
        //dd($model);

        if(!$model)return $this->wsrobot_return(['status'=>'A']);//não existe registro para processar
        $model->update(['status'=>'a','locked_at'=>date('Y-m-d H:i:s')]);// = a - em andamento  //locked_at - trava o registro
        $r=[
            'status'=>'R',
            'id'=>$model->id,
            'file_url'=>$model->file_url,
            'engine'=>$model->engine,
            'pass'=>$model->pass
        ];
        return $this->wsrobot_return($r);
    }

    /**
     * Recebe a resposta da solicitação do robô AutoIt
     * Esta etapa retorna ao texto capturado e removo o respectivo registro da tabela
     * @param $request - valores esperados: id, status (R|E), msg, text (texto extraído)
     * @return array [status(R|E), msg]
     */
    private function set_process($request){
        $id = $request->input('id');
        $status = $request->input('status');
        $msg = $request->input('msg');
        $text = $request->input('text');
        //ddx($request->except(['text']), $text);
        $model = $this->FileExtractTextModel->find($id);
        if(!$model)return ['success'=>false,'msg'=>'Erro ao localizar registro de arquivo de extração'];

        //ddx(strlen($text),$text);
        //if($text)$text=utf8_encode(base64_decode($text));

        //obs: se existir callback, remove automaticamente o arquivo
        if($model->callback){
            /*$cls = explode('@',$model->callback);//0 class, 1 method
            $processClass = \App::make($cls[0]);
            $r = $processClass->$cls[1]([[
                'success'=>$status=='R',
                'msg'=>$status=='R'?'':$msg,
                'area_name'=>$model->area_name,
                'area_id'=>$model->area_id,
                'file_text'=>$text,
                //obs: abaixo é setado null, pois dentro deste processo, é sempre removido o arquivo
                'file_url'=>null,
                'file_path'=>null,
            ]]);*/
            $r=\App::call($model->callback,[[
                'success'=>$status=='R',
                'msg'=>$status=='R'?'':$msg,
                'area_name'=>$model->area_name,
                'area_id'=>$model->area_id,
                'file_text'=>$text,
                //obs: abaixo é setado null, pois dentro deste processo, é sempre removido o arquivo
                'file_url'=>null,
                'file_path'=>null,
            ]]);
            $model->delete();
            if($r['success']){
                $r = ['status'=>'R','msg'=>''];
            }else{
                $msg = is_array($r) ? ' '.($r['msg']??'') : '';
                $r = ['status'=>'E','msg'=>'Erro na extração - retorno callback'. $msg];
            }

        }else{
            if($status=='R'){
                $model->update(['file_text'=>$text,'status'=>'f']);
            }else{
                $model->update(['file_text'=>$msg,'status'=>'e']);
            }
            $r = ['status'=>'R','msg'=>''];
        }
        return $this->wsrobot_return($r);
    }


    public static function getModel(){
        return (new \App\Models\FileExtractText);
    }


    /**
     * Retorno padrão em xml
     */
    private function wsrobot_return($r){
        $r = \App\Utilities\XMLUtility::convertArrToXml($r);
        return response($r, 200)->header('Content-Type', 'application/xml; charset=utf-8');
    }

}
