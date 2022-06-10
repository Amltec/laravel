<?php

namespace App\Models\PrSeg;
use App\Models\Base\ModelMultipleKeys;

class PrSegDados__s extends ModelMultipleKeys{
    
    
    protected $fillable = ['ctrl','process_id','data_type','proposta_num','apolice_num','apolice_num_quiver','data_emissao','apolice_re_num','inicio_vigencia','termino_vigencia','segurado_nome','segurado_doc','tipo_pessoa','fpgto_tipo_code','fpgto_n_prestacoes','fpgto_1_prestacao_valor','fpgto_1_prestacao_venc','fpgto_venc_dia_2parcela','fpgto_avista','fpgto_premio_total','fpgto_premio_liquido','fpgto_premio_liq_serv','fpgto_custo','fpgto_adicional','fpgto_iof','fpgto_juros','fpgto_juros_md','fpgto_desc','anexo_upl','comissao_premio'];
    protected $table = 'pr_seg_dados__s';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    protected $primaryKey = ['ctrl','process_id'];
    
    
}

