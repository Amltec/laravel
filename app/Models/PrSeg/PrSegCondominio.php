<?php

namespace App\Models\PrSeg;
use App\Models\Base\ModelMultipleKeys;

class PrSegCondominio extends ModelMultipleKeys{
    
    protected $fillable = ['process_id','num','condominio_endereco','condominio_numero','condominio_compl','condominio_bairro','condominio_cidade','condominio_uf','condominio_cep'];
    protected $table = 'pr_seg_condominio';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    protected $primaryKey = ['process_id','num'];
}
