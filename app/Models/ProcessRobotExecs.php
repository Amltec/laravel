<?php

namespace App\Models;
use App\Models\Base\ModelMultipleKeys;
use App\Utilities\ValidateUtility;

class ProcessRobotExecs extends ModelMultipleKeys{
    
    protected $fillable = ['process_id','id','process_start','process_end','status_code'];
    protected $table = 'process_robot_execs';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    protected $primaryKey = ['id', 'process_id'];
    
    
    //******* funções ********
    /**
     * Captura e retorna aos dados php do arquivo .data associado este registro
     * @param $processModel - respectivo model ProcessRobot associado
     * @param $cache - indica se deve forçar a leitura ou pegar os dados do cache
     */
    private $get_text_cache=[];
    public function getText($processModel,$cache=true){
        if(!($this->get_text_cache && $cache)){
            $p = $processModel->baseDir()['dir_final'] . DIRECTORY_SEPARATOR . $this->attributes['process_id'].'_exec_'.$this->attributes['id'].'.data';
            $text = file_exists($p) ? file_get_contents($p) : null;
            $text = ValidateUtility::isSerialized($text) ? unserialize($text) : $text;
            $this->get_text_cache = $text;
        }
        return $this->get_text_cache;
    }
    
}

