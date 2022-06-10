<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\MetadataTrait;
use App\Models\Traits\LogTrait;
use Illuminate\Support\Facades\Cache;

/**
 * Class Account.
 *
 * @package namespace App\Entities;
 */
class Account extends Model{
    use SoftDeletes, MetadataTrait, LogTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['account_name','account_status','account_email','account_login','account_key','process_mark','process_single'];



    //***** relacionamento *****
    //com a tabela de relações de usuário que esta conta pode ter: uma 'conta' pode ter muitas relações de usuários' - relacionamento (1-N)
    public function userRelations() {
        return $this->hasMany(UserAccountRelation::class);
    }




    //retorna a pasta publica de armazenamento de arquivo
    public function getStoragePath(){
        return public_path('storage'.DIRECTORY_SEPARATOR.'accounts'.DIRECTORY_SEPARATOR.$this->account_key);
    }
    public function getStorageUrl($filename=''){
        return url('storage/accounts/'.$this->account_key . ($filename!=''?'/'.$filename:'') );
    }

    //****** ajustes de valores para a view ******

    //label de status
    public function getStatusLabelAttribute(){
        $status=[
            'a'=>'Normal',
            'c'=>'Cancelado'
        ];
        return $status[$this->attributes['account_status']];
    }

    //retorna se o registro está cancelado
    public function getIsCancelAttribute(){
        return $this->attributes['account_status']=='c';
    }



    //********** funções ***********

    /**
     * Captura uma configuração
     * @param boolean $force - se true, força a leitura do db e regrava o cache
     */
    public function getConfig($name='',$force=false){
        $config = $this->getData('config', $force);
        return $name ? ($config[$name]??null) : $config;
    }


    /**
     * Captura um metadado
     * @param boolean $force - se true, força a leitura do db e regrava o cache
     */
    public function getData($name='',$force=false){
        $cache_name = 'account_config_' . $this->attributes['id'];
        if(Cache::has($cache_name) && $force==false){
            $r = Cache::get($cache_name);
        }else{
            $r = $this->getMetaData();
            $this->createCacheData($r);
        }
        return $name ? $r[$name]??'' : $r;
    }

    /**
     * Seta metadado
     * @param string|int|array $value
     * Return array[success,msg]
     */
    public function setData($name,$value){
        $r=$this->setMetadata($name, $value);
        if($r===true){
            $this->createCacheData();
            return ['success'=>true,'msg' => 'Dado cadastrado'];
        }else{
            return ['success'=>false,'msg'=>$r];
        }
    }

    /**
     * Deleta um metadado
     * Return array[success,msg]
     */
    public function delData($name){
        $r=$this->delMetadata($name);
        if($r===true){
            $this->createCacheData();
            return ['success'=>true,'msg' => 'Dado excluído'];
        }else{
            return ['success'=>false,'msg'=>$r];
        }
    }

    //Grava o cache de configura
    public function createCacheData($cache_data=null){
        if(!$cache_data)$cache_data=$this->getMetaData();
        $cache_name = 'account_config_' . $this->attributes['id'];
        Cache::forget($cache_name);//limpa primeiro
        Cache::forever($cache_name,$cache_data);//armazena em cache
    }

}
