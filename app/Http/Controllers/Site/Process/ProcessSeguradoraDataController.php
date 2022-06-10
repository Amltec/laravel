<?php

namespace App\Http\Controllers\Site\Process;
use App\Http\Controllers\Controller;
use App\Models\ProcessRobot_CadApolice;

/**
 * Classe de cadastro de apólices no Quiver
 * Rotas não autenticadas para esta classe
 * Ex de url: site.com/{class_name}/{method_name}/
 */
class ProcessSeguradoraDataController extends Controller{
    //minutos até expirar o token de acesso do arquivo
    private static $token_file_expire = 20;
    
    //nome do controller do cadastro de apólice
    private static $controller_name='\\App\\Http\\Controllers\\Process\\ProcessSeguradoraDataController';
    
    public function __construct(ProcessRobot_CadApolice $prca){
        $this->ProcessRobotCadApolice = $prca;
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
        
        $process = $this->ProcessRobotCadApolice->find($param['process']);
        if(!$process)exit('Erro: registro não encontrado');
        $data = $process->getData();
        $process_token = $data['token']??null;
        
        //calcula os minutos //obs: o campo $param['token'] contém o mesmo valor de $process_token e por isto não precisa ser usado nesta função
        $allow=false;
        if($process_token && $process_token===($param['token']??'')){
            $minutes = \App\Utilities\FormatUtility::dateDiffFull($process_token,'now','m1');
            if($minutes<=self::$token_file_expire)$allow=true;
        }
        if(!$allow)exit('Erro: token expirado');
        
        //captura o número do boleto pelo nome do arquivo $filename
        $process->_num_boleto=str_replace(['boleto_','.pdf'],'',$filename);
        
        return \App::call(self::$controller_name.'@get_fileload'. studly_case($param['process_prod']) ,[$process]);
        
    }
}
