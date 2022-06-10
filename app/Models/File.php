<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Services\FilesService;
use App\Models\Traits\LogTrait;
use App\Models\Traits\MetadataTrait;
use App\Models\Traits\TaxTrait;

/**
 * Classe de gerenciamento de arquivos
 */
class File extends Model{
    use MetadataTrait,LogTrait,TaxTrait;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['file_title','file_name','file_size','file_mimetype','file_path','file_ext','private','folder','user_id','account_id','deleted_at','file_thumbnails'];
  
    private static $folder_thumbnails='_thumbnails';//nome da pasta onde serão gerados as miniaturas das imagens
    
    //Adiciona a classe FileController para captura de configurações - $config
    private $controllerConfig=null;
    public function setControllerConfig($controller_name){
        $c=$controller_name;
        $this->controllerConfig = is_string($c) ? (new \App\Http\Controllers\FilesController)->callController($c) : $c;
        return $this;
    }
    
    //*********** Escopos **************
    /*
     * Filtro por area_name e area_id pela tabela files_relations.
     * Param array $area_data - [area_name=>area_id]. Condição OR para múltiplos parâmetros.
     *              $area_id - string ou array (se array = pesquisa com whereIn). Aceita também mais de string 1 id separador por virgula.
     * Param string $area_status - Valores: null, 'a', 'c'
     * Obs: se area_id == null, então será filtrado apenas por area_name
     */
    public function scopeWhereFileRelation($query,$area_data,$area_status=null){
        //$query->leftjoin('files_relations as fr','fr.fileid', '=', 'crime_reports.crime_type_id')
        $query->leftjoin('files_relations as fr','fr.file_id', '=', 'files.id');
        foreach($area_data as $area_name=>$area_id){
            if(strpos($area_name,'[]')!==false || strpos($area_id,',')!==false){
                $area_name=str_replace('[]','',$area_name);
                if($area_id)$area_id=explode(',',$area_id);
            }
            
            $query->where('fr.area_name',$area_name);
            if(is_array($area_id)){
                $query->whereIn('fr.area_id',$area_id);
            }else if($area_id){
                $query->where('fr.area_id',$area_id);
            }
        }
        if($area_status)$query->where('fr.status',$area_status);
        return $query;
    }
    
    
    //***** relacionamento *****
    
    //com a tabela de usuários: uma 'relação de file' tem 1 'usuário' - relacionamento (1-1)
    public function users(){
        return $this->belongsTo(User::class,'user_id');
    }
    
    //com a tabela de relations: um 'arquivo' tem muitas 'relações' - relacionamento (1-N)
    public function relations(){
        return $this->hasMany(FileRelation::class,'file_id','id');
    }
    
    
    //***** funções *****/
    
    
    //Retorna ao registro da tabela relation (apenas 1 registro a partir do area_name|id)
    public function relationByArea($area_name,$area_id){
        $m = FileRelation::select('id','status')->where(['file_id'=>$this->attributes['id'],'area_name'=>$area_name,'area_id'=>$area_id])->first();
        if($m)$m->status_label = ['a'=>'Visível','0'=>'Oculto','c'=>'Cancelado'][$m->status];
        return $m;
    }
    
    //Retorna ao caminho do arquivo principal
    public function getPath(){
        $p = $this->attributes['file_path'] .DIRECTORY_SEPARATOR. $this->attributes['file_name'] .'.'. $this->attributes['file_ext'];
        if($this->attributes['private']){//pasta privada
            $p=storage_path($p);//ja vem na pasta storage
        }else{//pasta pública
            $p=public_path($p);
        }
        return $p;
    }
    
    
    //Retorna a url do arquivo principal
    public function getUrl(){
        $controller_name = $this->controllerConfig ? $this->controllerConfig->getConfig('basename') : 'files';
        
        $n=$this->attributes['file_name'] .'.'. $this->attributes['file_ext'];
        if($this->attributes['private']){//pasta privada
            $u=route('app.file.load',[$controller_name,$this->attributes['id'],$n]);
            //dd($u,$this->attributes);
        }else{//pasta pública
            $u = $this->attributes['file_path'] .DIRECTORY_SEPARATOR. $n;
            $u = str_replace(DIRECTORY_SEPARATOR,'/',$u);
            $u = url($u);
        }
        return $u;
    }
    
    
    /**
     * Retorna a url de visualização da imagem (miniatura se for imagem ou ícone padrão para demais arquivos)
     * @param $thumbnail - small(default)|medium|medium_large|large|full
     * @param $retDefNotExists - booelan, se true irá retornar a uma imagem padrão caso não encontre a miniatura, se false return null.
     * @return array - índices:
     *      0 - url da miniatura disponível
     *      1 - largura da miniatura
     *      2 - altura da miniatura
     */
    public function getUrlThumbnail($thumbnail='small',$retDefNotExists=true){
        $th_thumb = $this->attributes['file_thumbnails']; //sintaxe: {size}-{width}x{height}|
        
        if($thumbnail=='full' || empty($th_thumb)){
            //nenhuma ação
        }else{
            $sizes_current=[];
            foreach(explode('|',$th_thumb) as $size){
                $tmp = explode('-',$size);//0 name, 1 sizes
                $sizes_current[$tmp[0]]=$tmp[1];
            }
        }
        
        if($thumbnail=='full' || empty($th_thumb)){
            $th_name='full';
            
        }else{
            $sizes_index=['small'=>0,'medium'=>1,'medium_large'=>2,'large'=>3,'full'=>4];
            $th_name='';
            
            if(!isset($sizes_index[$thumbnail])){//o valor informado não é válido dentre os registrados
                $thumbnail='small';//registra com o primeiro nome
            }
            
            if(isset($sizes_current[$thumbnail])){//achou dentre os tamanhos existentes
                $th_name=$thumbnail;
                
            }else{//procura o mais próximo na ordem: small > medium > large > full
                
                foreach($sizes_index as $s => $i){
                    if($i>=$sizes_index[$thumbnail]){
                        if(strpos($th_thumb,$s)!==false){//verifica se existe a opção solicitada $s dentre as disponíveis em $th_thumb
                            $th_name=$s;break;
                        }
                    }
                }
                if($th_name=='')$th_name='full';
            }
        }
        
        //caso não exista a miniatura solicitada, retorna a thumbnail name máximo dentro de $sizes_current
        if(isset($sizes_current) && !isset($sizes_current[$th_name])){
            $th_name = $this->getMaxCurrentSizes($sizes_current,$th_name,'name');
            //dd($sizes_current,$th_name);
        }
        
        //dump([$th_name,$th_thumb]);
        $controller_name = $this->controllerConfig ? $this->controllerConfig->getConfig('basename') : 'files';
        if(!$th_thumb && $this->getIsImageAttribute()){//não tem miniatura, mas é uma imagem
            $th_thumb='full';//exibe a imagem completa neste caso
        }
        
        $size=null;
        $u_p=null;
        
        //url da miniatura
        if($this->attributes['private']){//pasta privada
            if(!$th_thumb){//não tem miniatrura
                if(!$retDefNotExists)return null;
                $u = FilesService::getIconExt($this->attributes['file_ext'])['image'];//imagem de miniatura padrão
                $size = FilesService::$iconsSizes;//[w,h]
            }else{
                if($th_name=='full'){
                    $filename = $this->attributes['file_name'].'.'. $this->attributes['file_ext'];
                    $u=route('app.file.load',[$controller_name,$this->attributes['id'],$filename]);
                }else{
                    $filename = $this->attributes['file_name'].'-'. $sizes_current[$th_name] .'.'. $this->attributes['file_ext'];
                    $u=route('app.file.load',[$controller_name,$this->attributes['id'],$th_name,$filename]);
                    $filename = self::$folder_thumbnails . DIRECTORY_SEPARATOR . $filename;//comando usado no bloco da captura das dimensões da miniatura
                }
                $u_p = storage_path($this->attributes['file_path'] .DIRECTORY_SEPARATOR. $filename);
            }
            
        }else{//pasta pública
            //dd($th_thumb,$th_name,$thumbnail,$this->attributes['file_name']);
            if(empty($th_thumb) && $thumbnail!='full'){//não tem miniatura ou não é imagem completa
                if(!$retDefNotExists)return null;
                $u = FilesService::getIconExt($this->attributes['file_ext'])['image'];//imagem de miniatura padrão
                $size = FilesService::$iconsSizes;//[w,h]
                
            }else{//tem miniatura
                if($th_name=='full'){//imagem principal
                    $filename = $this->attributes['file_name'] .'.'. $this->attributes['file_ext'];
                }else{//miniatura
                    $filename = self::$folder_thumbnails . DIRECTORY_SEPARATOR . $this->attributes['file_name'] .'-'. $this->getMaxCurrentSizes($sizes_current,$th_name) .'.'. $this->attributes['file_ext'];
                }
                $u = $this->attributes['file_path'] .DIRECTORY_SEPARATOR. $filename;
                $u = str_replace(DIRECTORY_SEPARATOR,'/',$u);
                if(file_exists($u)){
                    $u_p = public_path($u);
                    $u = url($u);
                }else{
                    if(!$retDefNotExists)return null;
                    $u = FilesService::getIconExt(null)['image'];//imagem de miniatura padrão
                    $u_p = $u;
                }
            }
        }
        $u_p = str_replace('/',DIRECTORY_SEPARATOR,$u_p);
        
        if(!$size){
            $w=0;$h=0;
            //captura as dimensões do arquivo

            if(!empty($th_thumb) && $th_name!='full'){//tem miniatura gravada, portanto captura a partir do nome da miniatura o tamanho para gerar menos processamento
                //extrai as dimensões ex de: filename-100x100.jpg 
                $n=substr($filename,strrpos($filename,'-')+1,strlen($filename));
                $n=substr($n,0,strpos($n,'.'));
                list($w,$h)=explode('x',$n);
                $w=(int)$w;$h=(int)$h;
            }
            if($w==0 || $h==0){
                $size=getimagesize($u_p);
                $w=$size[0];$h=$size[1];
                //dd($size);
            }
        }
        
        return [$u,$w,$h];
    }
    
    
    /**
     * Retorna a miniatural máxima disponível considerando o nome informado em $th_name (ex: se informado medium mas existir apenas small, então retoraná a small)
     * @ret - size|name
     * @return string|null
     */
    private function getMaxCurrentSizes($sizes_current,$th_name,$ret='size'){
        if($th_name=='full'){
            $arr = ['full','large','medium_large','medium','small'];
        }elseif($th_name=='large'){
            $arr = ['large','medium_large','medium','small'];
        }elseif($th_name=='medium_large'){
            $arr = ['medium_large','medium','small'];
        }elseif($th_name=='medium'){
            $arr = ['medium','small'];
        }elseif($th_name=='small'){
            $arr = ['small'];
        }else{
            return null;
        }
        
        $th=null;
        foreach($arr as $n){
            if(isset($sizes_current[$n])){
                $th=$n;break;
            }
        }
        return $th ? ($ret=='name'?$th:$sizes_current[$th]) : null;
    }
    
    
     /**
     * Retorna a um array com os valores de getUrlThumbnail() com todas as miniaturas disponíveis
     */
    public function getUrlThumbnailAll(){
        $r=[];
        foreach(['small','medium','medium_large','large','full'] as $thumb){
            $n=$this->getUrlThumbnail($thumb,false);
            if($n)$r[$thumb]=$n;
        }
        return $r;
    }
    
    
    
    //Retorna ao caminho de todas as miniaturas disponíveis de imagens
    //Return array paths or false
    public function getPathThumbnails(){
        $th = $this->attributes['file_thumbnails']; //sintaxe: {size}-{width}x{height}|
        $r=[];
        if(!empty($th)){
            foreach(explode('|',$th) as $size){
                $tmp = explode('-',$size);//0 name, 1 sizes
                $th_name=$tmp[0];
                $p = $this->attributes['file_path'] . DIRECTORY_SEPARATOR . 
                        self::$folder_thumbnails . DIRECTORY_SEPARATOR . 
                        $this->attributes['file_name'] .'-'. $tmp[1] . '.'. $this->attributes['file_ext'];
                if($this->attributes['private']){//pasta privada
                    $p=storage_path($p);//ja vem na pasta storage
                }else{//pasta pública
                    $p=public_path($p);
                }
                $r[$th_name]=$p;
            }
        }
        return empty($r)?null:$r;
    }
    
    
    //Retorna ao diretório deste arquivo
    public function getDirectory(){
        $p = $this->attributes['file_path'];
        if($this->attributes['private']){//pasta privada
            $p=storage_path($p);//ja vem na pasta storage
        }else{//pasta pública
            $p=public_path($p);
        }
        return $p;
    }
    
    
    //Retorna a um ícone
    public function getIcon(){
        return FilesService::getIconExt($this->attributes['file_ext']);
    }
    
    //Retorna se o arquivo principal existe no diretório
    public function fileExists(){
        $u = $this->attributes['file_path'] .DIRECTORY_SEPARATOR. $this->attributes['file_name'] .'.'. $this->attributes['file_ext'];
        $u = $this->attributes['private']?storage_path($u):public_path($u);
        $u = str_replace(DIRECTORY_SEPARATOR,'/',$u);
        return file_exists($u);
    }
    
    
    
    //****** atributos do registro da model (valores para a view) ******
    //Retorna a true se o registro estiver deletado / na lixeira
    public function getIsDeletedAttribute(){
        return $this->attributes['deleted_at']!==null;
    }
    
    //Retorna a true se o registro dor de uma imagem
    //É usado para definir se será incluído dentro de um atributo html <img src=''>
    public function getIsImageAttribute(){
        return in_array($this->attributes['file_ext'],['jpg','jpeg','gif','bmp','png','bmp','svg']);
    }
    
    //Retorna o nome completo do arquivoi
    public function getFileNameFullAttribute(){
        return $this->attributes['file_name'].'.'.$this->attributes['file_ext'];
    }
    
    
    //****** html functions *******
    
    //retorna ao código da imagem 
    public function htmlImg($opt=[]){
        if(!$this->fileExists()) return '';
        
        $opt = array_merge([
            'thumbnail'=>'small',
            'attr'=>[], //tag A
            'attr_img'=>[],
        ],$opt);
        
        
        $attr = \App\Utilities\HtmlUtility::buildAttributes($opt['attr']);
        $attr_img = \App\Utilities\HtmlUtility::buildAttributes($opt['attr_img']);
        
        $imgx = $this->getUrlThumbnail($opt['thumbnail']);
        $imgz = $this->getUrlThumbnailAll()['full'];
        
        return '<a href="'. $this->getUrl() .'" data-width="'. $imgz[1] .'" data-height="'. $imgz[2] .'" data-size="'. $imgz[1].'x'.$imgz[2] .'" '.$attr .'><img src="'. $imgx[0] .'" alt="'. $this->attributes['file_title'] .'" '.$attr_img.'></a>';
    }
    
    //retorna ao código do link do arquivo como anexo
    public function htmlFile($opt=[]){
        $opt = array_merge([
            'attr'=>[],
            'icon'=>null,
        ],$opt);
        
        $attr = \App\Utilities\HtmlUtility::buildAttributes($opt['attr']);
        return '<a href="'. $this->getUrl() .'" '.$attr.'>'. 
                    ($opt['icon']?'<i class="fa '.$opt['icon'].' margin-r-5"></i>':'') .
                    $this->attributes['file_title'] .
                '</a>';
    }
}
