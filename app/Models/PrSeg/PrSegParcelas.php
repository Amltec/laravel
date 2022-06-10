<?php

namespace App\Models\PrSeg;
use App\Models\Base\ModelMultipleKeys;

class PrSegParcelas extends ModelMultipleKeys{
    
    protected $fillable = ['process_id','num','fpgto_datavenc','fpgto_valorparc'];
    protected $table = 'pr_seg_parcelas';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    protected $primaryKey = ['process_id','num'];
    
}

