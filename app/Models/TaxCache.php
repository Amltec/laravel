<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class de cache para as taxonomias.
 */
class TaxCache extends Model{
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    protected $table = 'tax_cache';
    protected $fillable = ['term_id','area_name','area_id','cache'];
    
}
