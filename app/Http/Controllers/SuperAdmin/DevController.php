<?php
/*
 * Controllers de acesso com informações, ferramentas e demais recursos para gerenciamento do desenvolvedor / programador do sistema.
 */

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;
use App\Http\Controllers\WSFileExtractTextController;
use Gate;
use DB;
use App\Utilities\FilesUtility;
use App\Utilities\ValidateUtility;
use App\Models\ProcessRobot_CadApolice;


class DevController extends SuperAdminBaseController {
    public function __construct(){
        if(!Gate::allows('dev')){//somente desenvolvedor pode ter acesso
            return self::redirectUserDenied();//permissão negado para o usuário não super programador
        }
        $this->list_exec_class = require( app_path() .'/ProcessRobot/cad_apolice/Classes/ExecClass/_list.php' );
    }

    public function get_view(Request $request){$pag = $request->input('pag');
        return view('super-admin.dev.'. ($pag?$pag:'dashboard') );
    }


    public function post_pdftextTest(Request $request, \App\Http\Controllers\FilesController $filesController){
        $r= $filesController->postDirect($request);//faz o upload conforme parâmetros $request
        if(!$r['success'])return $r;

        $file = $r['file_path'];
        $ret = ['success'=>true,'msg'=>'Sucesso','filename'=>$r['file_name_full']];
        $file_original_name = $request->file->getClientOriginalName();
        $pass=null;

        $engine = $request->input('pdf_engine');
        if(substr($engine,0,4)=='ait_'){
            $pass = FilesUtility::getPassByName($file_original_name);

            $pdfExtract = WSFileExtractTextController::add($engine,$r['file_url'], $file, 'manual', 0, $pass);
            $ret['html'] = 'Extração em andamento. Aguarde...';
            $ret['pdf_engine'] = $engine;
            $ret['file_extract_text_id'] = $pdfExtract->id;

        }else{
            $pdfExtract = FilesUtility::readPDF($file,[
                'engine'=>$engine,
                'file_url'=>$r['file_url'],
                'pass'=>true,
                'file_name'=>$file_original_name
            ]);
            $text = $pdfExtract['text'];
            //deleta o arquivo
            @unlink($file);

            $ret['html'] = $text;
            $ret['pdf_engine'] = $pdfExtract['engine'];
        }

        return $ret;
    }

    /**
     * Retorna o registro da extração pelo orc (originado pela função post_pdftextTest()) foi concluído
     */
    public function post_pdftextTestLoad(Request $request){
        $r = WSFileExtractTextController::load($request->input('id'));//obs: por padrão esta função já remove automaticamente o registro após a leitura
        if($r['success']){
            if(file_exists($r['path']))@unlink($r['path']);//deleta o arquivo
            unset($r['path']);
        }
        return $r;
    }


    /**
     * Ações da Ferramenta Assistente de Leitura de PDFs
     * Url: /super-admin/dev/view?pag=tool-test-read-pdf
     * @param $request[actions]
     *      add - adiciona o registro na tabela 'dev_process_robot_test_read_pdf' para verificação
     *
     */
    public function get_toolTestReadPdfActions(Request $request){ if($request->action=='process_result')return $this->post_toolTestReadPdfActions($request); }
    public function post_toolTestReadPdfActions(Request $request){
        $action = $request->action;
        if($action=='add'){
                $ids = $request->ids;
                if(!$ids)return ['success'=>false,'msg'=>'Parâmetro IDs inválido'];

                $processModel = new \App\Models\Base\ProcessRobot;
                $ids=explode(',',$ids);
                $opt = $request->opt ?? [];

                $compare_fields = in_array('extract_compare',$opt) ? $request->compare_fields : null;

                if(in_array('exec_class',$opt)){
                    $exec_class_name = $request->exec_class;
                    //dd('xx',$exec_class_name, $this->existsClassExec($exec_class_name));
                    if(!$this->existsClassExec($exec_class_name))return ['success'=>false,'msg'=>'Classe de Execução "'. $exec_class_name .'" não encontrada'];
                }else{
                    $exec_class_name = null;
                }
                //armazena em metadado
                \App\Services\MetadataService::set('dev_test_read_pdf', '0', 'compare_fields', $compare_fields);

                //limpa todos os registros antes
                if(in_array('clear',$opt)){
                    DB::delete('delete from dev_process_robot_test_read_pdf');
                }

                if(in_array('exec_class',$opt))$opt=[];//limpa o campo, pois as demais ações aqui serão executadas apenas na classe personalizada

                foreach($ids as $id){
                    if(!is_numeric($id))continue;

                    //carrega o registro do respectivo id
                    $reg = $processModel->where('process_name','cad_apolice')->find($id);
                    $status='0';
                    if(!$reg)$status='x';//registro não existe

                    //insere na tabela do desenvolvedor
                    DB::insert('insert into dev_process_robot_test_read_pdf (process_id,dt_start,dt_end,status,opt_extract,opt_save_index,opt_extract_compare,exec_class_name) values (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=?, exec_class_name=?;', [
                        $id,
                        date('Y-m-d H:i:s'),
                        null,
                        $status,
                        in_array('extract',$opt),           //Executa a extração de textos
                        in_array('save_index',$opt),        //Salva o resultado da indexação *
                        in_array('extract_compare',$opt),   //Salva o resultado da indexação *
                        $exec_class_name,
                        $status,
                        $exec_class_name,
                    ]);
                }
                return ['success'=>true];


        }else if(in_array($action,['trash','remove','remove_all'])){
                if($action=='remove_all'){
                    $ids = DB::table('dev_process_robot_test_read_pdf')->select('process_id')->pluck('process_id')->toArray();//return array ids
                }else{
                    $ids = [$request->id];
                }
                foreach($ids as $id){
                    //remove o respectivo registro (caso exista) na tabela de extração de arquivos
                    DB::delete('delete from files_extract_text where area_name=? and area_id=?',['process_robot',$id]);
                    //remove o registro da tabela de ferramentas de leitura de pdf
                    DB::delete('delete from dev_process_robot_test_read_pdf where process_id=?',[$id]);
                }
                return ['success'=>true];


        }else if($action=='process_result'){
                $auto_continue=false;
                if(!$request->stop){

                    $compare_fields = \App\Services\MetadataService::get('dev_test_read_pdf', '0', 'compare_fields');

                    //verifica se tem registros para processar
                    $model=DB::table('dev_process_robot_test_read_pdf')->whereIn('status',['a','0'])->orderBy('dt_start','asc')->take(1);
                    $reg=$model->first();
                    if($reg){//capturou o primeiro registro disponível

                        //captura os dados da classe
                        $model->update(['status'=>'a']);
                        $processClass = \App::make('\\App\\Http\\Controllers\\Process\\ProcessCadApoliceController');
                        $is_indexing=true;

                        //verifica a classe de correção / verificações
                        if($reg->exec_class_name){
                            //neste caso executa apenas este bloco e já retorna, pois nele contém as verificações personalizadas e as demais execuções fora deste IF não não necessárias
                            $ExecClass = $this->loadClassExec($reg->process_id, $processClass, $reg->exec_class_name);
                            $r = $ExecClass->process();
                            $model->update(['status'=>($r['success']?'f':'e'), 'msg'=>(!empty($r['msg'])?$r['msg']:($r['success']?'Sucesso':'Erro ao processar')), 'exec_data'=> serialize($r) ]);
                            goto process_result_end1;
                        }


                        if($reg->opt_extract){//faz a extração do texto
                            try{
                                $process=$processClass->extractOnlyText($reg->process_id,null,$reg->engine,'\\App\\Http\\Controllers\\SuperAdmin\\DevControllerNotAuth@cbFileExtractTextSave');
                                if($process['process_wait']??false){//indexação offline
                                    $model->update(['status'=>'o','msg'=>'Aguardando extração ('.$reg->engine.')','opt_extract'=>false]);//seta opt_extract=false para que não processe mais este bloco, pois está aguardando o callback dos dados extraídos
                                    $is_indexing=false;

                                }elseif($process['success']){//já completou a indexação //atualiza o texto
                                    $reg->engine = $process['pdf_engine'];
                                    $model->update(['status'=>'0','msg'=>$process['msg'],'opt_extract'=>false,'engine'=>$reg->engine]);//seta '0' para que seja reprocessado novamente abaixo o bloco indexação (em caso de recarregamento desta página)
                                    $process['process_model']->setText('text',$process['file_text']);
                                    $process['process_model']->addLog('log',['msg'=>'Extração de texto atualizado pela Ferramenta Assistente de Leitura de PDFs']);

                                    //atualiza no banco de dados

                                }else{//erro
                                    if($reg->engine && $process['pdf_engine']!=$reg->engine){//quer dizer que foi alterado o motor de busca na execução da classe, portanto armazena esta informação para reprocessar
                                        $model->update(['status'=>'0','engine'=>$process['pdf_engine'],'msg'=>'Novo reprocessamento para '.$process['pdf_engine']]);
                                        return ['success'=>false,'auto_continue'=>true];//apenas retorna a função para repetir este caso
                                    }

                                    $model->update(['status'=>'e','msg'=>$process['msg']]);
                                    $is_indexing=false;
                                }
                            }catch (\Exception $e){
                                $model->update(['status'=>'e','msg'=>'Erro de execução no arquivo da classe (extração)']);
                                $is_err=false;
                            }
                        }

                        if($is_indexing){
                            try{
                                $process = $processClass->test_extractTextFromPdf($reg->process_id,[
                                    'save_index'=>$reg->opt_extract_compare ? false : (bool)$reg->opt_save_index, //se $reg->opt_extract_compare, então não pode salvar o resultado da indexação
                                    'pdf_engine'=>$reg->engine,
                                ]);
                                //dd($reg,$process,$reg->engine && $process['pdf_engine']!=$reg->engine);

                                if($reg->engine && $process['pdf_engine']!=$reg->engine){//quer dizer que foi alterado o motor de busca na execução da classe, portanto armazena esta informação para reprocessar
                                    $model->update(['status'=>'0','engine'=>$process['pdf_engine'],'opt_extract'=>true]);//seta '0' para que seja repetida esta função e executado a extração novamente
                                    return ['success'=>false,'auto_continue'=>true];//apenas retorna a função para repetir este caso
                                }

                                $diff=[];

                                if($process['success'] && $reg->opt_extract_compare){
                                    //faz a comparação da extração do texto atual com a que está salva no db
                                    $prAnterior = $process['process_model']->getText('data');
                                    $prAtual = $process['data'];

                                    //faz a comparação dos resultados
                                    $diff = $this->compareExtractData($prAnterior,$prAtual,$compare_fields);
                                    //dd($prAnterior , $prAtual, $diff);
                                }

                                $arr = ['status'=>$process['success']?'f':'e', 'msg'=>$process['msg'],'diff_fields'=>($diff?serialize($diff):null) ];
                                $model->update($arr);
                                if($process['success']){
                                    //Salva os dados do log
                                    $process['process_model']->addLog('log',['msg'=>'Indexação atualizada pela Ferramenta Assistente de Leitura de PDFs']);
                                }

                            }catch (\Exception $e){
                                $model->update(['status'=>'e','msg'=>'Erro de execução no arquivo da classe (indexação)']);
                            }
                        }
                        $auto_continue=true;
                    }
                }

                process_result_end1:

                //faz a contagem agrupada por status
                $reg_counts=DB::table('dev_process_robot_test_read_pdf')->selectRaw('status,count(1) as total')->groupBy('status')->pluck('total','status')->toArray();
                $status_list=['0'=>0,'a'=>0,'e'=>0,'f'=>0,'x'=>0,'o'=>0,];
                $count=0;
                foreach($reg_counts as $st=>$total){
                    $status_list[$st]=$total;
                    $count+=$total;
                }
                $status_list['all']=$count;

                //conta quantas registros tem o campo diff_fields
                $status_list['diff']=DB::table('dev_process_robot_test_read_pdf')->selectRaw('count(1) as total')->whereNotNull('diff_fields')->value('total');

                return ['success'=>true,'status_list'=>$status_list,'auto_continue'=>$auto_continue];


        }else if($action=='reprocess'){
                DB::table('dev_process_robot_test_read_pdf')->update(['status'=>'0','msg'=>'']);
                return ['success'=>true];

        }else if($action=='get_ids_by_qs'){
                $f_st = $request->f_st;
                $reg = DB::table('dev_process_robot_test_read_pdf');
                if($f_st){
                    if($f_st=='diff'){
                        $reg->whereNotNull('diff_fields');
                    }else{
                        $reg->where('status',$f_st);
                    }
                }
                $ids = $reg->select('process_id')->take(10000)->pluck('process_id')->toArray();
                return ['success'=>true,'ids'=>$ids];
        }else{
                return ['success'=>false,'msg'=>'Parâmetro action inválido'];
        }
    }



    /**
     * Faz a comparação de valores extraídos
     * @param $dataAnterior e $dataAtual - array da extação de dados
     * @param $fields - campos para comparação, valores:
     *                  all - todos os campos
     *                  ... - nomes dos campos com valores separados por virgula, caso o campo seja multilinhas (ex 'cep_1'), então deverá escrever apenas 'cep'
     * @return array $allow - retorna aos campos que tiverem valores diferentes
     */
    public function compareExtractData($dataAnterior,$dataAtual,$allow){
        $allow = $allow=='all' ? null : explode(',', str_replace([chr(10),chr(13),',,',',,',',,'],',',$allow) );
        $diff=[];
        $not_find_field=array_flip($allow);
        //dd($not_find_field);
        foreach($dataAnterior as $f=>$v){
            if(is_array($v))continue;//se o campo for do tipo array, então desconsidera
            $t=false;
            //verifica se deve comparar o campo
            if($allow){
                foreach($allow as $a){
                    if(is_array($a))continue;//se o campo for do tipo array, então desconsidera
                    //lógica: retira o último caractere {n} caso seja númerico na sintaxe 'field_a_b_{n}'
                    $n=trim($a);
                    $n=explode('_',$n);
                    $i=$n[count($n)-1];
                    if(is_numeric($i))unset($n[count($n)-1]);
                    $a2 = trim(join('_',$n));

                    $n=trim($f);
                    $n=explode('_',$n);
                    $i=$n[count($n)-1];
                    if(is_numeric($i))unset($n[count($n)-1]);
                    $f2 = trim(join('_',$n));

                    //verifica se é igual ao campo do loop
                    //dump([$f2,$a,$n]);
                    if($a2==$f2){
                        $t=true;
                        //dd($a2,$f2,$not_find_field);
                        unset($not_find_field[$a2],$not_find_field[$a]);//retira o campo encontrado
                        break;
                    }
                }
            }else{
                $t=true;
            }

            if($t){
                foreach($dataAtual as $f2=>$v2){
                    if($f==$f2 && $v!=$v2){
                        $diff[$f]=[$v,$v2];
                    }
                }
            }
        }
        //dd($dataAtual);
        if($not_find_field){
            foreach($not_find_field as $f=>$v){
                $f=trim($f);
                if($f)$diff[$f]='não existe';//$f não pode ser vazio
            }
        }
        //dd('*a',$diff,'***',$not_find_field);
        return $diff;
    }


    /**
     * Carrega a classe de correção / verificação personalizada informada pelo campo 'exec_class_name'
     * @param $process_id - id da tabela process_robot[cad_apolice]
     * @param $processClass - processCadApoliceController class
     * @param $exec_class_name - precisa ser um dos valores de \app\ProcessRobot\cad_apolice\Classes\ExecClass\_list.php
     * @return new Class ou null
     */
    private function loadClassExec($process_id,$processClass,$exec_class_name){
        $list = $this->list_exec_class;
        if(isset($list[$exec_class_name])){
            $processModel = ProcessRobot_CadApolice::find($process_id);
            $c='\\App\\ProcessRobot\\cad_apolice\\Classes\\ExecClass\\'.$exec_class_name;
            return new $c($processClass, $processModel, $processModel->getText('text'));
        }else{
            return null;
        }
    }
    /**
     * Verifica se a classe de corração / verificação existe
     * @return boolean
     */
    private function existsClassExec($exec_class_name){
        $list = $this->list_exec_class;
        if(isset($list[$exec_class_name])){
            $c= '\\App\\ProcessRobot\\cad_apolice\\Classes\\ExecClass\\'.$exec_class_name;
            return class_exists($c);
        }else{
            return false;
        };
    }



    /**
     * Exibe os dados retornados do campo exec_data
     */
    public function get_pageRetData(Request $req){
        echo '<h3>Dados retornados #'.$req->id.'</h3>';
        $reg = DB::table('dev_process_robot_test_read_pdf')->where('process_id',$req->id)->first();
        $n = $reg->exec_data;
        if($n){
            if(ValidateUtility::isSerialized($n))$n = unserialize($n);
        }else{
            echo '<p>Registro não possui campo exec_data</p>';
        }
        dd($n);
    }



    /**
     * Callback do arquivo de extração (complementar da função acima post_toolTestReadPdfActions() -> $request['action']='process_result')
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
    /*public function cbFileExtractTextSave($opt){
        ddx($opt);
        $model = (new \App\Models\Base\ProcessRobot)->find($opt['area_id']);
        if(!$model)return ['success'=>false,'msg'=>'Registro não localizado'];
        if(!$opt['success'])return ['success'=>$opt['success'],'msg'=>$opt['msg']];

        //atualiza o texto retornado na tabela process_robot_data
        $model->setText('text',$opt['file_text']);

        //atualiza o registro da extração dev_process_robot_test_read_pdf
        $model=DB::table('dev_process_robot_test_read_pdf')->where('process_id',$opt['area_id'])->take(1);
        $reg=$model->first();
        if($reg){//capturou o primeiro registro disponível)
            $model->update(['status'=>'f', 'msg'=>'Extração de texto concluída','opt_extract'=>false]);//seta opt_extract=false para que não processe a extração novamente
        }


        return ['success'=>true,'msg'=>'Extração de texto concluída'];
    }*/

}
