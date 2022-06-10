<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Metadata.
 */
class FileRelation extends Model{
    
    protected $table  = 'files_relations';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    protected $fillable = ['file_id','area_name','area_id','status'];
    
}
