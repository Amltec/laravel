<?php

namespace App\Http\Controllers\Site\Process;
use App\Http\Controllers\Controller;
use App\Models\Base\ProcessRobot;
use App\Models\User;
use App\Models\FileExtractText;
use Auth;

/**
 * Classe de cadastro de apólices no Quiver
 * Rotas não autenticadas para esta classe
 * Ex de url: site.com/{class_name}/{method_name}/
 */
class ProcessCadApoliceController extends Controller{
    //minutos até expirar o token de acesso do arquivo
    private static $token_file_expire = 30;
    
    //nome do controller do cadastro de apólice
    private static $controller_name='\\App\\Http\\Controllers\\Process\\ProcessCadApoliceController';
    
    
    public function __construct(User $UserModel, ProcessRobot $ProcessRobotModel){
        $this->UserModel = $UserModel;
        $this->ProcessRobot = $ProcessRobotModel;
    }
    
    /**
     * Carrega a visualização do arquivo PDF de modo seguro para o download pelo robô app autoit
     * $param - string base64 de um array serialize: [user(int id), process(int id)]
     * $filename - apenas o nome do arquivo utilizado no momento em que for salvar o arquivo
     */
    public function robotFileLoad($param,$filename){
        $param = base64_decode($param);
        if(\App\Utilities\ValidateUtility::isSerialized($param)){
            try{
                $param = unserialize($param);
            }catch(\Exception $e){
                exit($e->getMessage());
            }
        }else{
            exit('Parâmetro inválido');
        }
        
        //obs: com a função Auth::loginUsingId(), a sessão do usuário não é persistida (sem este recurso não tem como prosseguir com as funções abaixo, que sempre verificam o usuário)
        //obs atualizada: isto foi desconsiderado, pois nas funções mais abaixo foram ajustadas para funcionar sem o parâmetro do usuário logado.
        //                e tem o detalhe que arquivos que vem pela área de seguradoras, não tem um usuário responsável pelo cadastro
        //if(!Auth::loginUsingId($param['user']))exit('Acesso negado (user)');
        
        
        //dd('b',$param);
        $process = $this->ProcessRobot->find($param['process']);
        if(!$process)exit('Erro: registro não encontrado');
        $data = $process->getData();
        $process_token = $data['token']??null;
        
        //calcula os minutos //obs: o campo $param['token'] contém o mesmo valor de $process_token e por isto não precisa ser usado nesta função
        $allow=false;
        if($process_token && $process_token===($param['token']??'')){
            $minutes = \App\Utilities\FormatUtility::dateDiffFull($process_token,'now','m1');
            if($minutes<=self::$token_file_expire)$allow=true;
        }
        
        //verifica se o arquivo está na fila de arquivos disponíveis para extrações pela tabela files_extract_text, e se estiver liberado o acesso
        $fileExtract = (new FileExtractText)->where(['area_name'=>'process_robot','area_id'=>$process->id])->whereIn('status',['0','a'])->count();
        if($fileExtract)$allow=true;
        
        if(!$allow)exit('Erro: token expirado');
        
        return \App::call(self::$controller_name.'@get_fileload',[$process]);
    }
    
    /**
     * Faz o processo de indexação/leitura do pdf e gravação dos dados no DB de todos que estiverem com process_status=0
     * @obs este processo é agendado para ser executado em segundo plano a cada 5 minutos (executado via GET na url /admin/process_cad_apolice/processFilesAll)
     * Ex de rota para este método: site.com/process_cad_apolice/processFilesAll
     */
    public function get_processFilesAll(){
        return \App::call(self::$controller_name.'@get_processFilesAll');
    }
    
    /**
     * Faz o envio automático de processos com status='i' (ignorados) para a lixeira (válido somente se o campo process_auto=true)
     * Este processo deve ser executado somente via agendamento automático no sistema.
     */
    public function get_sendProcessAutoTrash(){
        return \App::call(self::$controller_name.'@get_sendProcessAutoTrash');
    }
    
    public function get_processFixHistorico(){
        return \App::call(self::$controller_name.'@get_processFixHistorico');
    }
}
