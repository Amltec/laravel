<?php

namespace App\Http\Controllers\SuperAdmin;
use DB;

/**
 * Classe complementar do controller \App\Http\Controllers\SuperAdmin\DevController 
 * É utilizado para demais controllers acessarem os métodos desta classe não estando autenticados (como um admin ou superadmin).
 * Ex de uso: retorno do robô por parâmetro POST que executa callback desta classe automaticamente (e logado não irá funcionar)
 */
class DevControllerNotAuth{
       
    /**
     * Callback do arquivo de extração (complementar da função acima \App\Http\Controllers\SuperAdmin\DevController->post_toolTestReadPdfActions() -> $request['action']='process_result')
     * Importante: este método será sempre inicializado como função callback, portanto ao chegar até aqui, todas as validações de segurança já verificações
     * @param array $opt: 
     *      area_id
     *      area_name
     *      success (boolean) - 
     *      msg - mensagem de retorno (válido para success=false)
     *      file_text - texto retornado
     *      file_url
     *      file_path
     * @return array[success,msg]
     */
    public function cbFileExtractTextSave($opt){
        $model = (new \App\Models\Base\ProcessRobot)->find($opt['area_id']);
        if(!$model)return ['success'=>false,'msg'=>'Registro não localizado'];
        if(!$opt['success'])return ['success'=>$opt['success'],'msg'=>$opt['msg']];
        
        //atualiza o texto retornado na tabela process_robot_data
        $model->setText('text',$opt['file_text']);
        
        //atualiza o registro da extração dev_process_robot_test_read_pdf
        $model=DB::table('dev_process_robot_test_read_pdf')->where('process_id',$opt['area_id'])->take(1);
        $reg=$model->first();
        if($reg){//capturou o primeiro registro disponível)
            $model->update(['status'=>'0', 'msg'=>'Extração de texto concluída','opt_extract'=>false]);//seta status=0 and opt_extract=false para que processe novamente somente a indexação
        }
        
        return ['success'=>true,'msg'=>'Extração de texto concluída'];
    }
}
