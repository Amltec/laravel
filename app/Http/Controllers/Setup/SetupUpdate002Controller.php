<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Filesystem\Filesystem;
use App\Models\ProcessRobot;


/**
 * Classe que executa as possíveis atualizações pendentes.
 * Descrição: 
 *      Processa todos os registros da tabela process_robot, pegando o arquivo pdf da apólice e movendo para uma pasta com o id do processo.
 *      Ex: de ...\automovel\2020\11\123.pdf para ...\automovel\2020\11\123\123.pdf
 * Ex de chamada desta classe:
 *      https://robo.aurlweb.com.br/super-admin/setup-update-002/index?page={n}
 * 
 * Status: aguardando execução online
 */
class SetupUpdate002Controller extends Controller{
    
    private static $update_ctrl = '2020-11-16 18:54';
    private $Filesystem=null;
    
    public function get_index(ProcessRobot $ProcessRobot){
        $model = $ProcessRobot->where('process_name','cad_apolice')->withTrashed()->paginate(1000);
        if($model){
            echo 'Total de '. $model->count() .' registros';
            echo '<table border=1 cellspacing=0 cellpadding=4 style="border-collapse: collapse;"><tr><td>ID</td><td>Status</td></tr>';
            foreach($model as $reg){
                    echo '<tr>'.
                            '<td>'. $reg->id .'</td>'.
                            '<td>'. $this->updateFolder($reg) .'</td>'.
                         '</tr>';
            }
            echo '</table>';
        }else{
            echo 'Nenhum registro encontrado';
        }
        
        return 'Atualização concluída ' . date('Y-m-d H:i:s');
    }
    
    
    public function updateFolder($reg){
        $path   = $reg->baseDir();
        $folder = $path['dir'].'/'.$path['date_dir'];
        
        $from   = $folder .'/'. $reg->id .'.pdf';
        $to     = $folder .'/'. $reg->id ;
        
        if(file_exists($to.'/'. $reg->id .'.pdf')){
            return 'ok';
            
        }else if(!file_exists($from)){
            return 'nao encontrado';
        
        }else{//precisa mover
            if(!$this->Filesystem)$this->Filesystem = new Filesystem;
            if(!file_exists($to))$this->Filesystem->makeDirectory($to);
            $to.='/'.$path['folder_id'].'.pdf';
            $this->Filesystem->move($from,$to);
            return 'alterado';
        }
    }
}
