<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\MetadataTrait;
use App\Models\Traits\AccountTrait;

/**
 * Class Metadata.
 *
 * @package namespace App\Entities;
 */
class Tax extends Model{
    use MetadataTrait,AccountTrait;
    
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    protected $table  = 'taxs';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['term_id','tax_title','tax_description','tax_id_parent','tax_opt','account_id','tax_hide','tax_order','tax_level'];
    
    
     //*** inicialização da model ****
    protected static function boot(){
        parent::boot();
        //filtra pelo id da conta quando 'admin', e no 'superadmin' exibe apenas os account_id=null
        self::whereAccountUnique();
    }
    public function create(array $attributes){
        //adiciona account_id ou null no respectivo campo se estiver no superadmin
        return $this->createUnique($attributes);
    }
    public function updateOrCreate(array $attributes, array $values = []){
        //filtra pelo account_id ou null no respectivo campo se estiver no superadmin
        return $this->updateOrCreateUnique($attributes,$values);
    }
    
    
    
    //****** atributos ****/
    //opções da taxonomia - converte a string json em array
    public function getTaxOptionsAttribute(){
        $opt = $this->attributes['tax_opt'];
        if(empty($opt)){
            $opt=['color'=>null,'icon'=>null];
        }else{
            if(!is_array($opt))$opt=json_decode($opt,true);
        }
        return $opt;
    }
    
    
    
    //********** relaciomentos ***********
    //com a tabela de relações da taxonomia: uma 'taxonomia' tem muitas 'relações' - relacionamento (1-N)
    public function relations() {
        return $this->hasMany(TaxRelation::class);
    }
    
    //com a tabela de termos: uma 'taxonomia' tem/percente a um 'termo'
    public function term() {
        return $this->belongsTo(Term::class);
    }
    
}
