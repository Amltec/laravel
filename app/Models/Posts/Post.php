<?php

namespace App\Models\Posts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;
use App\Models\Traits\LogTrait;
use App\Models\Traits\TaxTrait;
use App\Models\Traits\Sql\WhereSql;

use App\Services\FilesService;
use App\Services\PostsService;
use App\Utilities\HtmlUtility;

class Post extends Model{
    use SoftDeletes, TaxTrait, LogTrait, WhereSql;
    
    protected $fillable = ['post_type','post_title','post_resume','post_content','post_content_type','post_status','post_visibility','post_name','post_parent','post_order','post_pass','post_version','user_level','created_at','updated_at','deleted_at','published_at','account_id','user_id','area_name','area_id','post_folder_id'];
    protected $table = 'posts';
    
    
    //*********** Escopos **************
    /**
     * Filtro por Metadato da tabela post_data.
     * Mais informações na função $this->whereTableData()
     */
    public function scopeWhereData($query,$meta_data){
        $query->whereTableData('post_data','post_id',$meta_data);
    }
    
    /**
     * Scopo de select sem o campo post_content (motivo: este campo é muito pesado)
     */
    public function scopeSelectSlim($query,$tbl_name=null){
        if($tbl_name){
            if($tbl_name===true)$tbl_name='posts';
            $tbl_name=trim($tbl_name,'.');
        }
        $a=[];
        array_unshift($this->fillable, 'id');//add id in first position array
        foreach($this->fillable as $f){
            if(!in_array($f,['post_content']))$a[]= ($tbl_name?$tbl_name.'.':'') . $f;
        }
        return $query->select($a);
    }
    
    
    //******* atributos ********
    
    /**
     * Retorna ao campo post_content mesmo que não exista no select() padrão da model
     */
    public function getPostContentAttribute(){
        $r=$this->attributes['post_content']??null;
        if(!$r)$this->select('post_content')->find($this->attributes['id'])->value('post_content');
        return $r;
    }
    
    /**
     * Retorna ao campo post_content com a url já formatada
     */
    public function getPostContentFormatAttribute(){
        return HtmlUtility::urlCodeToDomin($this->post_content);
    }
    
    /**
     * Retorna ao campo post_resume com a url já formatada
     */
    public function getPostResumeFormatAttribute(){
        return HtmlUtility::urlCodeToDomin($this->post_resume);
    }
    
    
    //******* funções ********
    
    private $post_url=null;
    /**
     * Retorna a url do arquivo
     * @param $ret - url (default), list, slug, null|'all' (array all)
     */
    public function getUrl($ret='url'){
        if(!$this->post_url)$this->post_url = PostsService::getUrl($this);
        return !$ret || $ret=='all' ? $this->post_url : ($this->post_url[$ret]??null);
    }
    
    /**
     * Retorna ao caminho da pasta para arquivos (válido caso exista a configuração config()[files_saved_post]=true)
     */
    public function getPathFiles(){
        if(!$this->post_url)$this->post_url = PostsService::getUrl($this);
        return !$ret || $ret=='all' ? $this->post_url : ($this->post_url[$ret]??null);
    }
    
    
    /**
     * Captura um metadado
     */
    private $cache_data=true;
    public function getData($name=''){
        if($this->cache_data===false){//obs: só será ==false, quando outros métodos desta função atualizem estes dados e precisar forçar a leitura
            $this->load('data');//força a leitura dos dados da tabela no relacionamento
            $this->cache_data=true;
        }
        $data=$this->data;
        $r=[];
        if($data){
            foreach($data as $f=>$v){
                $r[$v->meta_name]= ValidateUtility::isSerialized($v->meta_value) ? unserialize($v->meta_value) : $v->meta_value;
            }
        }
        if($name)$r=array_get($r,$name);
        return $r;
    }
    
    
    /**
     * Seta metadado
     * @param string|int|array $value
     * Return array[success,msg]
     */
    public function setData($name,$value){
        if(is_array($value))$value = serialize($value);
        $value = substr($value,0,50);
        try{
            PostData::updateOrInsert(['post_id'=>$this->attributes['id'],'meta_name'=>$name],['meta_value'=>$value??'']);
            $r=['success'=>true,'msg' => 'Dado cadastrado'];
            $this->cache_data=false;//limpa o cache (precisa ser =false para forçar a leitura do cache)
        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        return $r;
    }
    
    
    /**
     * Deleta um metadado
     * Return array[success,msg]
     */
    public function delData($name,$all=false){
        try{
            $model = PostData::where('post_id',$this->attributes['id']);
            if($all===false)$model = $model->where('meta_name',$name);
            $model->delete();
            $this->cache_data=false;//limpa o cache (precisa ser =false para forçar a leitura do cache)
            $r=['success'=>true,'msg' => 'Dado excluído'];
        } catch (Exception $e) {
            $r=['success'=>false,'msg' => $e->getMessage()];
        }
        return $r;
    }
    
    
    
    /**
     * Retorna aos arquivos associados
     * @param $area_name - ex de valores: image, attach, ...
     * @return null || model list $files
     */
    public function getFiles($area_name,$opts=[]){
        $opts=array_merge([
            'area_name'=>'posts.'.$area_name,
            'area_id'=>$this->attributes['id'],
            'private'=>false,
        ],$opts);
        $m = FilesService::getList($opts)['files'];
        return $m->count()>0 ? $m : null;
    }
    
    
    //********** relaciomentos ***********
    //com a tabela de posts: um 'post' tem muitos 'dados' - relacionamento (1-N)
    public function data(){
        return $this->hasMany(PostData::class,'post_id','id');
    }
    
    //com a tabela de histórico: um 'post' tem muitos 'histórico' - relacionamento (1-N)
    public function hist(){
        return $this->hasMany(PostHist::class,'post_id','id');
    }
    
    //com a tabela de contas: uma relação de 'post' tem 1 'account' - relacionamento (1-1)
    public function account(){
        return $this->belongsTo(\App\Models\Account::class);
    }
    
    //com a tabela de usuários: uma relação de 'processo' tem 1 'user' - relacionamento (1-1)
    public function user(){
        return $this->belongsTo(\App\Models\User::class);
    }
    
    //com a tabela post_folder: um 'post' tem 1 'post_folder' - relacionamento (1-1)
    public function postFolder(){
        return $this->belongsTo(\App\Models\Posts\PostFolder::class);
    }
    
}
