<?php

namespace App\Models\PrSeg;
use App\Models\Base\ModelMultipleKeys;

class PrSegParcelas__s extends ModelMultipleKeys{
    
    protected $fillable = ['ctrl','process_id','num','fpgto_datavenc','fpgto_valorparc'];
    protected $table = 'pr_seg_parcelas__s';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    protected $primaryKey = ['ctrl','process_id','num'];
}

