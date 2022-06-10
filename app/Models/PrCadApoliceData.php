<?php

namespace App\Models;
use App\Models\Base\ModelMultipleKeys;
use App\Services\LogsService;

/**
 * Classe complementar de armazenamento de metadados para o processo 'cad_apolice' 
 * Classe complementar de \App\Services\PrCadApoliceService
 */
class PrCadApoliceData extends ModelMultipleKeys {
    protected $table = 'pr_cad_apolice_data';
    public $timestamp = false;
    const CREATED_AT = null;//desabilita apenas o campo created_at
    const UPDATED_AT = null;//desabilita apenas o campo updated_at
    protected $fillable = ['process_id','num','meta_name','meta_value'];
    protected $primaryKey = ['process_id','num','meta_name'];
    
}
