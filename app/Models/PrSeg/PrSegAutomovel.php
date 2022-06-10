<?php

namespace App\Models\PrSeg;
use App\Models\Base\ModelMultipleKeys;

class PrSegAutomovel extends ModelMultipleKeys{
    
    protected $fillable = ['process_id','num','prop_nome','veiculo_fab_code','veiculo_modelo','veiculo_ano_fab','veiculo_ano_modelo','veiculo_chassi','veiculo_cod_fipe','veiculo_placa','veiculo_combustivel_code','veiculo_n_portas','veiculo_n_lotacao','veiculo_ci','veiculo_classe','veiculo_zero','veiculo_data_saida','veiculo_nf','segurado_pernoite_cep','veiculo_tipo'];
    protected $table = 'pr_seg_automovel';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    protected $primaryKey = ['process_id','num'];
}
