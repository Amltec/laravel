<?php

namespace App\Models\PrSeg;
use App\Models\Base\ModelMultipleKeys;

class PrSegEmpresarial__s extends ModelMultipleKeys{
    
    protected $fillable = ['ctrl','process_id','num','empresarial_endereco','empresarial_numero','empresarial_compl','empresarial_bairro','empresarial_cidade','empresarial_uf','empresarial_cep'];
    protected $table = 'pr_seg_empresarial__s';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    protected $primaryKey = ['ctrl','process_id','num'];
}
