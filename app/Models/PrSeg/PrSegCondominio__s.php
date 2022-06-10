<?php

namespace App\Models\PrSeg;
use App\Models\Base\ModelMultipleKeys;

class PrSegCondominio__s extends ModelMultipleKeys{
    
    protected $fillable = ['ctrl','process_id','num','condominio_endereco','condominio_numero','condominio_compl','condominio_bairro','condominio_cidade','condominio_uf','condominio_cep'];
    protected $table = 'pr_seg_condominio__s';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    protected $primaryKey = ['ctrl','process_id','num'];
}
