<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\DBService;

/**
 * Class Metadata.
 *
 * @package namespace App\Models;
 */
class TaxRelation extends Model
{
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['tax_id','area_name','area_id'];
    
    
    
    //********** relaciomentos ***********
    //com a tabela de taxonomia: uma 'relação de taxonomia' tem 1 'taxonomia' - relacionamento (1-1)
    public function tax(){
        //dd($this->belongsTo(Tax::class)->toSql(), $this->belongsTo(Tax::class)->getBindings());
        return $this->belongsTo(Tax::class);//,'id','tax_id');
    }
    
    /**
     * Retorna aos dados da área do qual se refere o area_name e area_id vinculado a esta relação de taxonomia
     * Obs: esta classe é um método e precisa ser chamado ex: $obj->getArea();
     */
    public function getAreaData(){
        return DBService::getAreaData($this->area_name, $this->area_id);
    }
    
    
}
