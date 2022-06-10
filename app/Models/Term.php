<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Metadata.
 *
 * @package namespace App\Models;
 */
class Term extends Model
{
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['term_title','term_singular_title','term_short_title','term_description','area_name','area_id'];
    
    //***** escopo *****/
    public function scopeCountTaxs($query){
        $account_id = \Config::accountID()??0;
        $query->select(\DB::raw("terms.*,(select count(taxs.id) from taxs where taxs.term_id=terms.id and taxs.account_id=".$account_id.") as taxs_count"));
        return $query;
    }
    
    
    //***** relacionamento *****/
    public function taxs() {
        return $this->hasMany(Tax::class);
    }
    
    
    
    //****** atributos  ******
    
    //total o total de taxonomias
    public function getTaxsCountAttribute(){
        return $this->taxs()->count();
    }
    
}
