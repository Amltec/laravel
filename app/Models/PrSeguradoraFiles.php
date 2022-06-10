<?php

namespace App\Models;
use App\Models\Base\ModelMultipleKeys;


/**
 * Classe utilizada o processo 'seguradora_files'. 
 * Controller \App\Http\Controllers\ProcessSeguradoraFilesController
 */
class PrSeguradoraFiles extends ModelMultipleKeys {
    protected $table = 'pr_seguradora_files';
    public $timestamp = false;
    const UPDATED_AT = null;//desabilita apenas o campo updated_at
    protected $fillable = ['process_id','quiver_id','process_rel_id','created_at','status','process_count'];
    
    protected $primaryKey = ['process_id', 'process_rel_id'];

    
    
    //********** relaciomentos ***********
    //com a tabela process_robot: um 'pr_seguradora_files' tem 1 'process_robot' - relacionamento (1-1)
    public function process_robot(){
        return $this->belongsTo(Base\ProcessRobot::class,'process_rel_id');
    }
    
}
