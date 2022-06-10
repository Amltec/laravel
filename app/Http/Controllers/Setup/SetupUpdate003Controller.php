<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Filesystem\Filesystem;
use App\Models\ProcessRobot;


/**
 * Classe que executa as possíveis atualizações pendentes.
 * Descrição: 
 *      Captura todos os registros metadados do texto extraído em PDF e coloca em arquivo txt no mesmo diretório do respectivo registro.
 *      Ações:
 *          1) Captura o texto do campo process_robot_data[meta_name=>'cad_apolice', meta_value=>'file_text']]
 *          2) Grava em arquivo em \automovel\2020\11\{id}\{id}_text.data       //{id} = process_robot.id
 *          3) Deleta o respectivo metadata
 * Ex de chamada desta classe:
 *      https://robo.aurlweb.com.br/super-admin/setup-update-003/index?page={n}
 * 
 * Status: aguardando execução online
 */
class SetupUpdate003Controller extends Controller{
    
    private static $update_ctrl = '2020-11-16 18:54';
    private $Filesystem=null;
    
    public function get_index(ProcessRobot $ProcessRobot){
        exit('Importante: executar somente depois da atualização SetupUpdate002Controller.php');
        
        $model = $ProcessRobot->withTrashed()->where('id','123')->paginate(100);
        if($model){
            echo 'Total de '. $model->count() .' registros';
            echo '<table border=1 cellspacing=0 cellpadding=4 style="border-collapse: collapse;"><tr><td>ID</td><td>Status</td></tr>';
            foreach($model as $reg){
                    echo '<tr>'.
                            '<td>'. $reg->id .'</td>'.
                            '<td>'. $this->updateTextFile($reg) .'</td>'.
                         '</tr>';
            }
            echo '</table>';
        }else{
            echo 'Nenhum registro encontrado';
        }
        
        return 'Atualização concluída ' . date('Y-m-d H:i:s');
    }
    
    
    public function updateTextFile($reg){
        $file_text = $reg->data_array['file_text']??'';
        if(empty($file_text))return 'erro ou vazio';
        $r=$reg->setText('text',$file_text);
        if($r){
            $reg->delData('file_text');
            return 'alterado';
        }else{
            return 'falha';
        }
    }
}
