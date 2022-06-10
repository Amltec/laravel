<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Builder;

/**
 * Personalização da model para o ProcessRobo para 'seguradora_files'
 */
class ProcessRobot_SeguradoraFiles extends Base\ProcessRobot{
    private static $basename='seguradora_files';
    //private $process_prod='down_apo';
    
    //**** global scope ****
    protected static function boot(){
        parent::boot();
        static::addGlobalScope('basename', function (Builder $builder){
            $builder->where('process_robot.process_name',self::$basename);
        });
    }
    
    //***** atributos *****
    
    /*//Retorna ao link para download do arquivo zip
    public function getLinkFileDownloadAttribute(){
        return $this->getPaths()['url'];
    }*/
    
    //retorna aos totais de registros por status
    private $get_count_status=null;
    private function get_countStatusMK(){
        if(!$this->get_count_status){
            $regs=$this->prSeguradoraFiles()->selectRaw('count(1) as total,status')->groupBy('status')->get()->pluck('total','status')->toArray();
            $r=[];
            foreach($this->getProcessClass()::$status_pr as $s => $v){
                $r[$s]=$regs[$s]??0;
            }
            $this->get_count_status=$r;
        }
        return $this->get_count_status;
    }
    
    //label mark_done
    public function getStatusMarkDoneAttribute(){
        $s=$this->attributes['process_status'];
        $s_label = $this->getProcessClass()::$status_pr[$this->attributes['process_status']]??'';if($s_label)$s_label=$s_label['text'];
        
        $st=$this->get_countStatusMK();
        $total=0;
        foreach($st as $k => $v){
            $total+=$v;
            $st[(string)$k]=$v>0;//save um resultado booleano
        };
        //dump($total,$st);
        if($total>0 && ($st['0'] || $st['a'] || $st['b'])){//existem registros para marcar/marcados no quiver
            return $st['0'] || $st['a'] || $st['b']?'Pendente':'Finalizado';
        }else{
            return in_array($s,['f','e']) ? $s_label : 'Ag. baixa de arquivos';
        }
    }
    
    //label count_status
    public function getCountStatusMarkDoneAttribute(){
        return $this->get_countStatusMK();
    }
    
    
    //**** Funções *****
    //retorna ao caminho da pasta e nome do arquivo
    public function getPaths($data=null){
        $p = $this->baseDir();
        
        $n = isset($data['filename_tmp']) ? $data['filename_tmp'] : $this->getData('filename_tmp');
        $id = $this->attributes['id'];
        $p['dir'].=DIRECTORY_SEPARATOR . $id;
        
        $robot_upload = 'upload_robo' . DIRECTORY_SEPARATOR . $p['relative_dir'] . DIRECTORY_SEPARATOR . $id;
        
        $prefix = \Config::adminPrefix();
        $url = in_array($prefix,['admin','super-admin']) ? route($prefix.'.app.get',['process_seguradora_files','download',$id]) : '';
        
        return [
            'filename'=>$n,
            'dir'=>$p['dir'],
            'file'=>$p['dir'] . DIRECTORY_SEPARATOR . $n,
            'url'=>$url,
            //'dir_tmp'=>$p['dir_tmp'],
            //'relative_dir_tmp'=>$p['relative_dir_tmp'],
            
            //caminho do upload por ftp do app autoit
            'upload_robo'=>storage_path() . DIRECTORY_SEPARATOR . $robot_upload,
            'relative_upload_robo'=>$robot_upload,
        ];
    }
    
    //extende a função de ProcessRobot->baseDir()
    public function baseDir($folder_date=false){//$folder_date - inicializa false, para não gerar a separação de diretórios de datas pela função baseDir()
        return parent::baseDir($folder_date);
    }
    
    
     
    //********** relaciomentos ***********
    
    //com a tabela pr_seguradora_files: uma relação de 'processo' tem muitos 'prSeguradoraFiles' - relacionamento (1-N)
    public function prSeguradoraFiles(){
        return $this->hasMany(PrSeguradoraFiles::class,'process_id','id');
    }
    
}
    