<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Filesystem\Filesystem;
use App\Models\ProcessRobot;
use App\Models\ProcessRobotData;
use Artisan;
use DB;
use App\Http\Controllers\Setup\Functions\SetupFunctions;

/**
 * Classe que executa executa as atualizações necessárias para a versão 3 do sistema.
 * Atualização: 07/01/2021
 * 
 * Acesse o link: /super-admin/setup-update-004-to-v3/index?...
 *      Parâmetro 'page={N}' para ir para a próxima página...
 */
class SetupUpdate004ToV3Controller extends Controller{
    
    private static $update_ctrl = '2021-01-17 18:48';
    private $Filesystem=null;
    
    public function get_index(){
        //Ação 1: *****
            //exit('A: certificar que nenhum registro nos status: Extração, Indexação, Pronto Robô, Andamento <br>B: Fazer uma cópia online do banco de dados!!!!.<br>C: Atualizar online os arquivos:<br> - \Setup\Setup004ToV3 <br> -\Models\ProcessRobot...');
            
        //Ação 2: *****
            //exit('A: Tabela insurers - add field insurer_find_rule text null <br>B: Atualizar o valor do campo insurer_find_rule conforme já configurado na versão de testes (ver com o Alisson)');
            
        //Ação 3: *****
            //Atualiza os dados da tabela process_robot_data para:
            //  - campos de log da (dados do log da baixa) para arquivo log_v1.data
            //  - campos file_data e grava para arquivo data.data
            //$this->updateRobotDataToFile();   //utilize ?page=...
        
        //Ação 4: *****
            //exit('Atualizar todos os arquivos onlie via ftp!!!<br>Depois ativar o modo de manutenção online');
        
        //Ação 5: *****
            //importar tabelas
            //exit('Primeiro precisa atualizar o arquivo .env com a propriedade (APP_ENV=local) para poder rodar o comando abaixo.
            //$this->infoImportTables();
            //exit('Lembrar depois que terminar de voltar o arquivo .env com a propriedade (APP_ENV=production)
        
        //Ação 6: *****
            //exit('Criar manualmente as tabelas: <br>-process_robot_execs <br>-')
            
        //Ação 7: ******
            //Atualizar os campos da tabela process_robot e process_robot_data para pr_seg_dados
            //$this->updateFieldsValues();     //utilize ?page=...
        
        
        
        //xxxx ... analisar mais comandos ....
        
        
        
        //Ação 10: *****
            //Remove os registros process_robot_data desnecessários
            //$this->deletaRobotDataFields();   //utilize ?page=...
        
        //Ação 11: ****
            //ajustar campos da tabela
            //$this->infoActionAdjustDB();
        
        //Ação 12: *****
            //exit('Deletar todos os registros da área de seguradoras');
        
        //Ação 13: *****
            //exit('Iniciar o teste com a importação da área de seguradoras (mas depois que importar, não indexar os registros)');
            
        //Ação 14: *****
            //exit('Pegar todos os registros que estão em Análise, Correção Suporte e Operador Manual e reprocessar/indexar');
            
        //Ação 15: *****
            //exit('Testar a importação de cada registro e ir corrigindo os erros');
            
        //Ação 16: *****
            //exit('Testar a emissões destes registros no Quiver');
            
        
        return 'Atualização concluída ' . date('Y-m-d H:i:s') .' - Page: '. _GET('page');
    }
    
    
    //**********************************************************************************************************
    
    /**
     * Atualiza os dados da tabela process_robot_data (dados do log da baixa) para arquivo
     * - Log da baixa log_v1.data
     *      Salva os dados no diretório do registro do process_robot \{process_id}_log_v1.data
     *      Campos salvos: process_robot_data, return_count, process_start|_1, process_end|_1, error_msg|_1
     * - Campos file_data e grava para arquivo data.data
     */
    private function updateRobotDataToFile(){
        $model = (new ProcessRobot)->where('process_name','cad_apolice')->withTrashed()->paginate(_GETNumber('regs')??1000);
        if($model){
            echo 'Total de '. $model->count() .' registros';
            echo '<table border=1 cellspacing=0 cellpadding=4 style="border-collapse: collapse;"><tr><td>ID</td><td>Status</td><td>Status</td><td>Excluído</td></tr>';
            foreach($model as $reg){
                    echo '<tr>'.
                            '<td>'. $reg->id .'</td>'.
                            '<td>'. $this->updateRobotDataToFile_x1($reg) .'</td>'.
                            '<td>'. strtoupper($reg->process_status) .'</td>'.
                            '<td>'. $reg->deleted_at .'</td>'.
                         '</tr>';
            }
            echo '</table>';
        }else{
            echo 'Nenhum registro encontrado';
        }
    }
        private function updateRobotDataToFile_x1($reg){
            $data = $reg->data_array??[];
            $fields=['return_count', 'process_start', 'process_end', 'error_msg'];
            $r=[];
            foreach($data as $f=>$v){
                $name='';
                foreach($fields as $n){
                    if(substr($f,0,strlen($n))==$n){$name=$f;break;}
                }
                if($name)$r[$name]=$v;//achou o campo
            }
            $n=[];
            //log_ v1
            if($r){
                try{
                    $n[]='log_v1';
                    $reg->setText($r,'log_v1');
                } catch (\Exception $e) {
                    $n[]='ERR:log_v1';
                }
            }
            //file_data
            $r=$data['file_data']??null;
            if($r){
                try{
                    $n[]='file_data';
                    $reg->setText($r,'data');
                } catch (\Exception $e) {
                    $n[]='ERR:file_data';
                }
            }
            return $n ? join(',',$n) : 'vazio';
        }
        
        
   /**
    * Atualizar os campos da tabela process_robot e process_robot_data para pr_seg_dados
    * xxxTrocar todo o texto do campo process_robot.data_type de 'apolice-hist' para 'historico'
    */
    private function updateFieldsValues(){
        $model = (new ProcessRobot)->withTrashed()->paginate(_GETNumber('regs')??1000);
        if($model){
            echo 'Total de '. $model->count() .' registros';
            echo '<table border=1 cellspacing=0 cellpadding=4 style="border-collapse: collapse;"><tr><td>ID</td><td colspan="2">Status</td><td>Excluído</td></tr>';
            
            foreach($model as $reg){
                $data = $reg->getData();
                $file_data = $reg->getText('data');
                //if($reg->id!=1212)continue;
                
                //ajusta o campo error_msg para o respectivo código
                if(in_array($reg->process_status,['e','c','1','i'])){//erro sistema, erro operador, em análise,
                    $m=$data['error_msg']??'';
                    if(!$m)$m=$data['error_msg_'.($data['return_count']??0)]??'';
                    if($m){
                        $m=mb_strtolower($m);
                        if(stripos($m,'erro ao localizar proposta'))                {$m='quid01';}
                        elseif(stripos($m,'seguradora não encontrado'))             {$m='ins01';}
                        elseif(stripos($m,'divergência no valor'))                  {$m='read05';}
                        elseif(stripos($m,'proposta não encontrada na consulta'))   {$m='quid01';}
                        elseif(stripos($m,'campos bloqueados'))                     {$m='quiv03';}
                        elseif(stripos($m,'fabricante do veículo deve ser'))        {$m='quiv28';}
                        elseif(stripos($m,'campos inválidos'))                      {$m='read01';}
                        elseif(stripos($m,'corretor não encontrado'))               {$m='bro01';}
                        elseif(stripos($m,'já existe veículo na lista'))            {$m='quiv04';}
                        elseif(stripos($m,'tipo endosso'))                          {$m='read03';}
                        elseif(stripos($m,'tipo frota'))                            {$m='read04';}
                        elseif(stripos($m,'tipo Endosso'))                          {$m='read03';}
                        elseif(stripos($m,'ramo inválido'))                         {$m='read02';}
                        elseif(stripos($m,'apólice não identificada'))              {$m='read00';}
                        elseif(stripos($m,'resumo da apólice'))                     {$m='read14';}
                        else{$m='err';}//demais erros
                    }
                    if($m)$reg->setData('error_msg',$m);
                }else{//status: f,w
                    $reg->setData('error_msg','ok');
                }
                
                $r='none';
                //ajuste os dados para a tabela pr_seg_dados
                if($reg->process_status!='i'){//todos menos os ignorados
                    $datatype = $reg->data_type;
                    if($datatype=='apolice-hist')$datatype='historico';
                    $arr=[
                        'process_id'=>$reg->id,
                        'data_type'=>substr($datatype, 0, 12),
                        'segurado_nome'=> substr($data['segurado_nome']??'', 0, 50),
                        'segurado_doc'=> substr($data['segurado_cpf']??'', 0, 20),
                        'apolice_num'=> substr($file_data['apolice_num']??'', 0, 20),
                        'apolice_num_quiver'=> substr($file_data['apolice_num_quiver']??'', 0, 20),
                    ];
                    $table=DB::table('pr_seg_dados');
                    $tableReg = $table->where(['process_id'=>$reg->id]);
                    //dump($reg, $tableReg, $tableReg->count());
                    if($tableReg->count()>0){
                        unset($arr['process_id']);
                        $tableReg->update($arr);
                        $r='ok';
                    }else{
                        $r=$table->insert($arr);
                        $r=$r?'ok':'err';
                    }
                    //dd($r);
                }
                
                
                echo '<tr><td>'. $reg->id .'</td><td>'. $r .'</td><td>'. strtoupper($reg->process_status) .'</td><td>'. $reg->deleted_at .'</td></tr>';
            }
            echo '</table>';
        }else{
            echo 'Nenhum registro encontrado';
        }
    }
    
    
    
    /**
     * Remove os registros process_robot_data desnecessários
     */
    private function deletaRobotDataFields(){
        $model = (new ProcessRobot)->withTrashed()->paginate(_GETNumber('regs')??1000);
        if($model){

            echo 'Total de '. $model->count() .' registros';
            echo '<table border=1 cellspacing=0 cellpadding=4 style="border-collapse: collapse;"><tr><td>ID</td><td>Status</td></tr>';
            foreach($model as $reg){
                    $data = $reg->getData();
                    for($i=1;$i<=($data['return_count']??0);$i++){
                        $reg->delData('process_start_'.$i);
                        $reg->delData('process_end_'.$i);
                        $reg->delData('error_msg_'.$i);
                    }
                    $reg->delData('return_count');
                    $reg->delData('process_start');
                    $reg->delData('process_end');
                    //$reg->delData('error_msg');//este campo não deve ser excluído
                    $reg->delData('error_code');
                    $reg->delData('login_use');
                    $reg->delData('file_data');
                    $reg->delData('file_text');
                    $reg->delData('process_block_names');
                    $reg->delData('apolice_num');
                    $reg->delData('segurado_nome');
                    $reg->delData('segurado_cpf');
                    
                    echo '<tr>'.
                            '<td>'. $reg->id .'</td>'.
                            '<td>ok</td>'.
                         '</tr>';
            }
            echo '</table>';
        }else{
            echo 'Nenhum registro encontrado';
        }
    }
    
    
    //importar tabelas
    private function infoImportTables(){
        Artisan::call('migrate');
        echo Artisan::output();
        exit;
    }
    
    //ajustar campos da tabela
    private function infoActionAdjustDB(){
        exit('<pre>Alterar manualmente no DB: '.chr(10).
            '    tabela process_robot_data  - meta_name         varchar(20) '.chr(10).
            '                               - meta_value        varchar(50) '.chr(10).
            '                                                               '.chr(10).
            '    tabela process_robot       - process_ctrl_id   varchar(20) '.chr(10).
            '                                                               '.chr(10).
            '    tabela process_robot       - remover os campos: file_ext, data_type'.chr(10).
            '</pre>');
    }
}
