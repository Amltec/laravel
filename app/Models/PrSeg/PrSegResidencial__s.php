<?php

namespace App\Models\PrSeg;
use App\Models\Base\ModelMultipleKeys;

class PrSegResidencial__s extends ModelMultipleKeys{
    
    protected $fillable = ['ctrl','process_id','num','residencial_endereco','residencial_numero','residencial_compl','residencial_bairro','residencial_cidade','residencial_uf','residencial_cep'];
    protected $table = 'pr_seg_residencial__s';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    protected $primaryKey = ['ctrl','process_id','num'];
}
