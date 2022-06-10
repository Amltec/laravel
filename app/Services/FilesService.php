<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileRelation;
use App\Utilities\FormatUtility;
use App\Utilities\ImageUtility;
use App\Services\MetadataService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Auth;
use Config;

class FilesService{
    
    private static $fileModel;
    private static $fileModelRelation;
    private static $folder_thumbnails='_thumbnails';//nome da pasta onde serão gerados as miniaturas das imagens
    
    
    //Tamanhos de miniaturas registradas. Sintaxe: thumbnailName=>[max-width,max-height]
    private static $thumbnailsList=[
        'small'=>[150,150],
        'medium'=>[400,400],
        'medium_large'=>[1024,1024],
        'large'=>[1900,1900],
    ];
    
    /**
     * Retorna ao tamanho padrão dos ícones
     */
    public static $iconsSizes=[48,48];
    

    
    /**
     * Retorna ao objeto da Model Files
     */
    public static function getModel(){
        if(!self::$fileModel)self::$fileModel = new File;
        return self::$fileModel;
    }
    
    /**
     * Retorna ao objeto da Model File Relations
     */
    public static function getModelRelation(){
        if(!self::$fileModelRelation)self::$fileModelRelation = new FileRelation;
        return self::$fileModelRelation;
    }
    
    
    
    /**
     * Faz o upload o arquivo
     * @param $file - deve conter o objeto request file, ex: $request->file('field_upload_name'); 
     * @param $opt - (opcional) valores:
     *          booelan private  
     *          boolean account_off - se true, considerado o diretório /app ao invés do diretório accounts
     *          string folder
     *          string area_name e area_id - se informado, gera o vínculo automático com a tabela files_relations
     *          boolean|array thumbnails - true|false cria ou não todas as miniaturas, array(small,medium...) cria somente as miniaturas especificadas.
     *          bollean folder_date - se false, irá gravar o arquivo diretamente no diretório informado, se true irá gravar com o ano/mês. Default true
     *          string filetitle - se informado, irá gravar o arquivo com este título no campo file_title
     *          string filename - se informado, irá gravar o arquivo com este nome e caso exista será sobrescrito. Sintaxe: 'filename.ext'
     *                               //obs: se informado, será procurado se o arquivo já existe no DB e atualizado o registro, pois tem o mesmo nome e extensão
     *          array metadata - sintaxe: [area_name=>,area_id,meta_name,meta_value]    //obs: todos os 4 parâmetros precisam serem informados
     *          accept      - (array) mimetypes aceitos. Caso não passe na validação retornará a um erro. Opcional.
     * 
     *          //somente se o upload for uma imagem
     *          max_width   - (int) default false
     *          max_height  - (int) default false
     *          image_fit   - (boolean) se false corta a imagem se necessário para manter as dimensões exatas, se true ajusta as dimensões da imagem para para caber dentro do tamanho informado
     * @param array $controllerClass - classe do controller de configuração (classe \App\Http\Controllers\FilesController)
     **/
    public static function uploadFile($file,$opts=[],$controllerClass=null){
        if(empty($file))return ['success'=>false,'msg'=>'Erro no upload. Campo vazio.'];
        
        $opt = array_merge([
            'private'=>false,
            'account_off'=>false,
            'account_id'=>null,
            'folder'=>'uploads',
            'area_name'=>'',
            'area_id'=>0,
            'thumbnails'=>true, //cria as miniaturas da imagem 
            'folder_date'=>true,
            'filetitle'=>'',
            'filename'=>'',
            'metadata'=>false,
            'max_width'=>false,
            'max_height'=>false,
            'image_fit'=>true,
            'accept'=>[],
        ],FormatUtility::array_ignore_null($opts));
        
        //converte string boolean to boolean
        $opt = array_map(function($v){ return FormatUtility::cBool($v);   }, $opt);
        
        if($controllerClass){
            if(!self::folderInFolder(array_keys($controllerClass->getConfig('folders_list')),$opt['folder'])){//a pasta informada não está na lista de pastas autorizadas
                return ['success'=>false,'msg'=>'Pasta '.$opt['folder']. ' não permitido'];
            }
            if(self::folderInFolder($controllerClass->getConfig('folders_not'),$opt['folder'])){//a pasta informada está na lista de pastas não autorizadas
                return ['success'=>false,'msg'=>'Pasta '.$opt['folder']. ' não permitido'];
            }
            $opt['folder_date'] = $controllerClass->getConfig('folder_date');
        }
        
        //ajusta a var mimetype
        if(!empty($opt['accept']) && !is_array($opt['accept']))$opt['accept']=[$opt['accept']];
        if($opt['accept']){
            foreach($opt['accept'] as $i => $m){
                if($m=='*'){//quer quizer que todos os tipos são aceitos, e portanto
                    $opt['accept']='*';//redefine a var
                    break;
                }elseif($m=='image/*'){
                    unset($opt['accept'][$i]);
                    $opt['accept']+=['image/jpeg','image/gif','image/png'];
                }
            }
        }
        
        $path = self::createStoragePath([
            'private'=>$opt['private'],
            'folder'=>$opt['folder'],
            'folder_date'=>$opt['folder_date'],
            'account_off'=>$opt['account_off'],
            'account_id'=>$opt['account_id'],
        ]);
        
        $post_max_size = FormatUtility::bytesVal(ini_get('upload_max_filesize'));//retorna ao valor em bytes
        if(empty($post_max_size))$post_max_size=50*1000*1024;//em bytes 50MB;
        $size = $file->getSize();
        
        //tamanho máximo do arquivo
        if(empty($size) || $size>$post_max_size){
            return ['success'=>false,'msg'=>'Tamanho de arquivo não permitido, maior que '. FormatUtility::bytesFormat($post_max_size)];
        }
        
        if(!$file->isValid()){
            return ['success'=>false,'msg'=>'Ocorreu algum erro ao carregar o arquivo. Tente novamente'];
        }
        
        //$file = $request->file($uploadname);// já capturado acima
        $originalname = $file->getClientOriginalName();
        $minetype = $file->getMimeType();
        //mime types negados
        if($controllerClass && in_array($minetype, $controllerClass->getConfig('mimetypes_not')))return ['success'=>false,'msg'=>'Tipo de arquivo não permitido'];
        //mime types aceitos
        if($opt['accept']!='*' && !empty($opt['accept']) && !in_array($minetype, $opt['accept']))return ['success'=>false,'msg'=>'Tipo de arquivo não permitido - Formatos aceitos: '. join(', ',$opt['accept']) .'.'];
        
        $is_image = in_array($minetype,['image/jpeg','image/gif','image/png']);//se true, é uma imagem
        
        //inicia o upload
        try{
            if($opt['filename']){//nome já definido, sobrescreve se necessário
                $filename = $opt['filename'];
                $ext = strtolower(pathinfo($filename,PATHINFO_EXTENSION));//extensão do arquivo
                $filename = basename($opt['filename'],'.'.$ext);//tira a extensão
                
            }else{
                //verifica se o arquivo já existe com este nome e caso exista, renomeia-o
                $ok=false;
                $ext = strtolower(pathinfo($originalname,PATHINFO_EXTENSION));//extensão do arquivo
                $filename = str_slug(substr($originalname,0,strlen($originalname)-strlen($ext)-1));//somente o nome do arquivo sem extensão
                if(empty($filename) || empty($ext)){
                    return ['success'=>false,'msg'=>'Nome do arquivo ou extensão inválido'];
                }

                $filename_copy = $filename;
                for($i=1;$i<=1000;$i++){//tenta 1000x
                    if(file_exists($path['basedir'] .DIRECTORY_SEPARATOR . $filename.'.'.$ext)){
                        $filename = $filename_copy.'-'.$i;//renomeia o arquivo para tentar novamente
                    }else{//arquivo não existe
                        $ok=true;break;
                    }
                }
                if(!$ok){
                    return ['success'=>false,'msg'=>'Erro ao gravar o arquivo (code: TryNameRepeat). Tente novamente alterando o nome do arquivo.'];
                }
            }
            
            //grava o arquivo no diretório
            $file->move($path['basedir'], $filename.'.'. $ext);
            $file_path_end = $path['basedir'] . DIRECTORY_SEPARATOR . $filename.'.'. $ext;
            
            
            if($opt['filetitle']){
                $file_title = $opt['filetitle'];
            }else{//nome automático
                $file_title = empty($opt['filename'])!==true ? $filename : substr($originalname,0,strlen($originalname)-strlen($ext)-1);
            }
            
            //insere o registro no db
            $files_save = self::storeFile([
                'is_upd_file'=> empty($opt['filename'])!==true,   //lógica: se true, irá atualizar o arquivo basendo-se nome e diretório
                'file' => $path['basedir'] .DIRECTORY_SEPARATOR . $filename . '.' . $ext,
                'file_name' => $filename,
                'file_size' => $size,
                'file_mimetype' => $minetype,
                'file_path' => $path['dir'],//diretório relativo
                'file_title' => $file_title,
                'file_ext' => $ext,
                'folder' => $opt['folder'],
                'private' => $opt['private'],
                'area_name' => $opt['area_name'],
                'area_id' => $opt['area_id'],
            ]);
            $r = $files_save;
            
        } catch (\Exception $e) {
            return ['success'=>false,'msg'=>$e->getMessage()];
        }
        
        //verifica se o arquivo é uma imagem, e redimenciona conforme parâmetro
        if($is_image && is_numeric($opt['max_width']) && is_numeric($opt['max_height'])){
            $w=(int)$opt['max_width'];
            $h=(int)$opt['max_height'];
            if($w>0 && $h>0){
                //dd($w,$h,$r['file_path'], $path['basedir'], $opt['image_fit']);
                ImageUtility::resizeThumbnail($w, $h, $file_path_end, $file_path_end, $opt['image_fit']);
            }
        }
        
        //miniaturas
        if($opt['thumbnails']){
            self::create_thumbnails($files_save['id'],$opt['thumbnails'],$opt['image_fit']);
        }
        
        //grava os dados metadados
        if($opt['metadata']){//sintaxe: [area_name=>,area_id,meta_name,meta_value]
            $m=$opt['metadata'];
            if(isset($m['meta_name']) && isset($m['meta_value']))MetadataService::set('files', $files_save['id'], $m['meta_name'], $m['meta_value']);
        }
        
        return $r;
    }
    
    
    
    /**
     * Salva no banco de dados o registro do arquivo.
     * Obs: o arquivo já deve estar no diretório /storage/ (público ou privado) e a função abaixo apenas irá registrar no banco de dados.
     * @param array $data: 
     *      campos requeridos: caminho completo do arquivo no servidor (deve incluir nome do arquivo)
     *      campos opcionais (capturados automaticamente caso não informado): file_title file_name, file_size, file_mimetype, file_ext, (booelan) private, folder
     *                  string area_name e area_id - se informado, gera o vínculo automático com a tabela files_relations
     */
    public static function storeFile($data){
        if(empty($data['file'])){
            return ['success'=>false,'msg'=>'Erro: arquivo não informado'];
        }
        if(!file_exists($data['file'])){
            return ['success'=>false,'msg'=>'Erro: arquivo não encontrado no diretório ('.$data['file'].').'];
        }
        if(empty($data['file_name'])){
            $data['file_name']=pathinfo($data['file']);
        }
        if(empty($data['file_title'])){
            $data['file_title']=basename($data['file']);
        }
        if(empty($data['file_ext'])){
            $data['file_ext']=pathinfo($data['file'],PATHINFO_EXTENSION);
        }
        if(empty($data['file_size'])){
            $data['file_size']=filesize($data['file']);//valor em bytes
        }
        if(empty($data['file_mimetype'])){
            $data['file_mimetype']=mime_content_type($data['file']);
        }
        if(empty($data['file_path'])){
            $data['file_path']= dirname($file);
        }
        if(empty($data['folder'])){
            $data['folder']= explode( DIRECTORY_SEPARATOR ,dirname(dirname($data['file_path'])))[0];//pula o mes e ano e pega o nome da pasta
        }
        if(!isset($data['private'])){
            $data['private']= strpos($data['file_path'], DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR)===false?false:true;
        }
        
        $is_upd_file = isset($data['is_upd_file']) && $data['is_upd_file']===true?true:false;
        
        $prefix = Config::adminPrefix();
        if(empty($data['account_id']))$data['account_id']=$prefix=='super-admin' ? null : Auth::User()->getAuthAccount('id');
        $data['user_id']=Auth::User()->id;
        
        try{
            if($is_upd_file){//verifica se já existe o arquivo baseando-se no nome e diretório
                $files = self::getModel()
                        ->where('file_name',$data['file_name'])
                        ->where('file_ext',$data['file_ext'])
                        ->where('file_path',$data['file_path'])
                        ->orderBy('id','desc')->first();
                if($files){
                   $files->update([
                       'file_size'=>$data['file_size'],
                       'file_title'=>$data['file_title'],
                       'updated_at'=>date('Y-m-d G:i:s')
                   ]);
                }else{//não achou o registro, portanto será adicionado abaixo
                    $is_upd_file=false;
                }
            }
            
            if(!$is_upd_file){
                $files = self::getModel()->create($data);
            }
            $data['id']=$files->id;
            $r=[
                'success'=>true,
                'msg' => 'Arquivo inserido com sucesso',
                'action'=>$is_upd_file?'edit':'add',
                'id'=>$files->id,
                'data'=>$data
            ];
            
            if(!empty($data['area_name']) && !empty($data['area_id'])){
                $r2=self::addRelation($files->id,$data['area_name'],$data['area_id']);
                if($r2['success']==false){
                    $r['msg']='Inserido com sucesso, mas ocorreu um erro ao vincular arquivo com a área.';
                    $r['alert']=true;//este alerta é para forçar a exibição da mensagem
                }
            }
            
        } catch (\Exception $e) {
            (new Filesystem)->delete($data['file']);
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        
        return $r;
    }
    
    
    
    /**
     * Retorna a lista de arquivos
     * @param array $opt:
     *      boolean is_trash - indica se deve lista arquivos. Default false (requerido).
     *      integer account_id - id da conta. Default a conta logada atual (requerido).
     *      int regs - total de registros por página. Defaul 15 (requerido).
     *      boolean private - indica a pasta é privada.
     *      boolean|integer user_id - id do usuário, valores: false|null - todos, true - somente o usuário logado, int > 0 - id do usuário para filtro
     *      string folder - nome padrão da pasta. Default 'uploads'.
     *      string|array mimetype - filtro por tipo de arquivo. Default '' (se string e para mais de um valor separar por virgula)
     *      string|array filetype - filtro por tipo de arquivo. Valores '' (todos), image, audio, video, pdf (se string e para mais de um valor separar por virgula)
     *      int id - filtro por id. Default null. Obs: esta opção, sobrepõe todos os demais critérios de busca.
     *      string area_name|area_id|area_status - filtro por vínculo com a tabela files_relations. Obs: o campo area_id e area_status são opcionais
     *                               Aceita o campo area_name e area_id com array, ex: 'area_name[]'=>'1,2,3' ou 'area_name'=>'1,2,3' //valores array de valores separados por virgula
     *      array metadata    - filtro por metadata - sintaxe:  [meta_name=>meta_value,...]       //mais informações em \App\Models\Traits\MetadataTrait.php
     *      string meta_name|meta_value - o mesmo filtro acima, mas filta apenas 1 metaname e 1 metadata 
     *      array taxs      - filtro por metadata - sintaxes ex: 1  '1'    [1,2,...]    '1,2,...'
     *      string search - parâmetros de busca, pode ser combinado vários valores, ex: 'year:2019 meu-texto' .Valores:
     *          year:YYYY
     *          month:mm
     *          date:yyyy-mm-aa
     *          ext:extencaoDoArquivo
     *          demais caracteres são procurados por nome e título do arquivo
     *      array taxs_id - array de taxonomias para fitlro de dados. Se string, separar os valores por virgula, ex: 1,2,...
     * @param array $controllerClass - classe do controller de configuração (classe \App\Http\Controllers\FilesController)
     * @return array:
     *      files - (array object) lista de arquivos
     *      opt - (array) os mesmos parâmetros de busca
     */
    public static function getList($opts=[],$controllerClass=null){
        $opt = array_merge([
            'private'=>false,
            'folder'=>null,
            'mimetype'=>'',
            'filetype'=>'',
            'account_id'=>null,
            'user_id'=>false,
            'is_trash'=>false,
            'id'=>null,
            'search'=>'',
            'regs'=>15,
            'taxs_id'=>null,
            'area_name'=>null,
            'area_id'=>null,
            'area_status'=>null,
            'metadata'=>null,
            'meta_name'=>null,
            'meta_value'=>null,
            'taxs'=>null,
            'search_in'=>'access'
        ],$opts);
        
        //dd($opts);
        if($controllerClass){
            if($opt['folder'] && !self::folderInFolder(array_keys($controllerClass->getConfig('folders_list')),$opt['folder'])){//a pasta informada não está na lista de pastas autorizadas
                return ['files'=>[],'opt'=>$opt];//retorna a dados vazios
            }
        }
        
        if(!is_numeric($opt['regs'])){$opt['regs']=15;}else{$opt['regs']=(int)$opt['regs'];if($opt['regs']>1000)$opt['regs']=1000;}
        if($opt['regs']<3){$opt['regs']=3;}
        
        $prefix = Config::adminPrefix();
        if($prefix!='super-admin'){
            $u=Auth::User();
            $opt['account_id'] = $u?$u->getAuthAccount('id'):false;
            if($opt['account_id']===false)$opt['account_id']=null;//seta null pois o acesso é fora da área autenticada e por enquanto, apenas files do superadmin pode ser acessados
        }
        
        $files = self::getModel()->select('files.*');
        if(!$opt['account_id']){
            $files->whereNull('account_id');
        }else{
            $files->where('account_id',$opt['account_id']);
        }
        
        $search_in = $opt['search'] ? $opt['search_in'] : '';//se vazio, quer dizer que não tem busca
        
        if($opt['id']){
            $files->where('id',$opt['id']);
            
        }else{
            if(in_array($search_in,['','all','access'])){
                $files->where('private',$opt['private']);
            }
            
            if($opt['area_name']){
                //if(in_array($search_in,['','all','area'])){
                    $files->whereFileRelation([$opt['area_name']=>$opt['area_id']],$opt['area_status']);
                //}
            }elseif($opt['folder']){
                if(in_array($search_in,['','all','folder'])){
                    $f=$opt['folder'];
                    if(is_string($f) && strpos($f,',')!==false )$f=explode(',',$f);
                    if(is_array($f)){
                        $files->where(function($q) use($f){
                            foreach($f as $n){
                                $n=str_replace('\\','/',$n);
                                $q->orWhereRaw('replace(folder,"\\\\","/") like ?',$n.'%');
                            }
                            return $q;
                        });
                    }else{
                        $f=str_replace('\\','/',$f);
                        $files->whereRaw('replace(folder,"\\\\","/") like ?',$f.'%');
                    }
                }
            }
            
            if($opt['user_id']){
                $n=$opt['user_id'];
                $files->where('user_id', ($n===true? (Auth::user()->id??'0') :$n) );    //seta '0' para anular o sql
            }
            
            if(!trim($opt['search'])){//mão existe uma busca
                    if($opt['mimetype']){
                        if(is_string($opt['mimetype']))$opt['mimetype']=explode(',',$opt['mimetype']);
                        $files->whereIn('file_mimetype',$opt['mimetype']);
                    }
                    if($opt['filetype']){
                        if(is_string($opt['filetype']))$opt['filetype']=explode(',',$opt['filetype']);
                        $w='';
                        foreach($opt['filetype'] as $filetype){
                            switch($filetype){
                            case 'image'    : $w='image/%'; break;
                            case 'audio'    : $w='audio/%'; break;
                            case 'video'    : $w='video/%'; break;
                            case 'pdf'      : $w='application/pdf'; break;
                            }//default 'todos'
                        }
                        if($w)$files->where('file_mimetype','LIKE',$w);
                    }
                    
                    if($opt['is_trash']){$files->whereNotNull('deleted_at');}else{$files->whereNull('deleted_at');}
                    if(!empty($opt['taxs_id']))$files->whereTax($opt['taxs_id']);

                    if($opt['metadata'])$files->whereMetadata($opt['metadata']);

                    if($opt['meta_name'] && $opt['meta_value'])$files->whereMetadata([$opt['meta_name']=>$opt['meta_value']]);

                    if($opt['taxs'])$files->whereTax($opt['taxs']);
                
                
            }else{//if(trim($opt['search'])){//existe uma busca
                    $str_original=strtolower($opt['search']);

                    foreach(explode(' ',$str_original) as $q){
                        $v='';$q2='';
                        if(substr($q,0,5)=='year:'){
                            $v = str_replace('year:', '', $q);
                            if($v)$files = $files->whereYear('created_at', '=', $v);
                            $q2=$q;

                        }else if(substr($q,0,6)=='month:'){
                            $v = str_replace('month:', '', $q);
                            if($v)$files = $files->whereMonth('created_at', '=', $v);
                            $q2=$q;

                        }else if(substr($q,0,5)=='date:'){
                            $v = str_replace('date:', '', $q);
                            if($v)$files = $files->whereDate('created_at', '=', $v);
                            $q2=$q;

                        }else if(substr($q,0,4)=='ext:'){
                            $v = str_replace('ext:', '', $q);
                            if($v)$files = $files->where('file_ext',$v);
                            $q2=$q;
                        }

                        if($q2){
                            $str_original = str_replace($q, '', $str_original);//limpa a string com os termus de busca capturados
                        }
                    }

                    $str_original=trim($str_original);

                    if(!empty($str_original)){//busca normal por string
                        $files->where(function($query) use($str_original){//está em função anônima para adicionar parênteses
                             $query->where('file_title', 'like', '%'.$str_original.'%')
                                     ->orWhere('file_name', 'like', '%'.$str_original.'%')
                                     ->orWhere('file_ext', $str_original);
                        });
                    }
            }

            $files->orderBy('created_at', 'desc');
        }
        //dd(\App\Services\DBService::getSqlWithBindings($files));
        //dd($files->toSql(), $files->getBindings());
        //dd($files->paginate($opt['regs']));
        
        $files = $files->paginate($opt['regs']);
        if($controllerClass){
            foreach($files as &$file){
                $file->setControllerConfig($controllerClass);//seta o controller para uso na model
            }
        }
        return ['files'=>$files,'opt'=>$opt];
    }
    
    
    
    /**
     * Atualiza dados do arquivo. 
     * @param array $data - valores aceitos: 
     *      title - título do arquivo
     *      metadata - array(key=>value) de valores metadados. (opcional)
     *      area_name|area_id - opcionais
     *      status  - valor para a tabela files_relations.status. Válido apenas se area_name e area_id forem informados
     */
    public static function setData($id,$data) {
        if(empty($id))return ['success'=>false,'msg'=>'Erro ao setar dados para o arquivo. Parâmetro id inválido.'];
        if(empty($data))return ['success'=>false,'msg'=>'Erro ao setar dados para o arquivo. Parâmetro data inválido.'];
        
        $file = self::getModel()->find($id);
        if(!$file)return ['success'=>false,'msg'=>'Erro ao setar dados para o arquivo. Registro não localizado.'];
        
        try{
           if(!empty($data['title'])){
               $file->update(['file_title'=>$data['title']]);
           }
           if(!empty($data['metadata']) && is_array($data['metadata'])){
               //insere/atualiza como metadado
               foreach($data['metadata'] as $n => $v){
                   //'files' = nome da tabela para o padrão area_name do metadata
                   MetadataService::set('files', $id, $n, $v);
               }
           }
           if($data['area_name']!='' && $data['area_id']!='' && $data['status']!=''){
               $m = self::getModelRelation()->where(['file_id'=>$id,'area_name'=>$data['area_name'], 'area_id'=>$data['area_id']])->first();
               if($m)$m->update(['status'=>$data['status']]);
           }
            
           $r=['success'=>true,'msg' => 'Arquivo atualizado','action'=>'edit'];
           
        } catch (\Exception $e) {
           $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        return $r;
        
    }
    
    
    
    /**
     * Retorna as informações do arquivo (para ser usado na view)
     * @param $file_id  - int|model
     * @param $return   - json|object|array (return $file)
     * @param array $controllerClass - classe do controller de configuração (classe \App\Http\Controllers\FilesController)
     * @return json|object|array (return $file)
     */
    public static function getInfo($file_id,$return='json',$controllerConfig=null){
        $file = is_object($file_id) ? $file_id : self::getModel()->find($file_id);
        if(!$file){
            $r=['success'=>false,'msg'=>'Erro capturar dados do arquivo.'];
            if($return=='json'){
                return json_decode(json_encode($r),true);
            }else if($return=='object'){
                return null;
            }else{//array
                return $r;
            }
        }
        if(is_string($controllerConfig)){
            $controllerConfig=(new \App\Http\Controllers\FilesController)->callController($controllerConfig);
        }
        $file->setControllerConfig($controllerConfig);//seta o controller para uso na model
        
        //personaliza os campos
        $file->success = true;
        $file->file_url = $file->url_view = $file->getUrl();//captura da função da model
        $file->file_size_format = FormatUtility::bytesFormat($file->file_size);
        $file->file_thumbnail_all = $file->getUrlThumbnailAll();
        $file->cache = strtotime($file->updated_at);
        $file->is_image = $file->is_image;
                
        //unset($file->file_path,$file->account_id);
        
        if($return=='json'){
            return json_decode(json_encode($file),true);
        }else if($return=='object'){
            return $file;
        }else{//array
            return ['success'=>true]+$file->toArray();
        }
    }
    
    
     /**
     * Renomeia o nome o arquivo.
     * @param $name - informar somente o nome do arquivo sem extensão
     */
    public static function renameFile($id,$name){
        if(empty($id))return ['success'=>false,'msg'=>'Erro ao setar dados para o arquivo. Parâmetro id inválido.'];
        $name = str_slug($name);
        if(empty($name))return ['success'=>false,'msg'=>'Erro ao renomear arquivo. Parâmetro name inválido.'];
        
        $file = self::getModel()->find($id);
        if(!$file)return ['success'=>false,'msg'=>'Erro ao renomear arquivo. Registro não localizado.'];
        
        //verifica se o arquivo já existe
        if(file_exists($file->getDirectory() . DIRECTORY_SEPARATOR . $name .'.'. $file->file_ext))return ['success'=>false,'msg'=>'Já existe um arquivo com este novo nome. Tente novamente com outro valor.'];
            
        $is_thumbs=false;
        if(!empty($file->file_thumbnails)){
            //deleta as miniaturas
            $is_thumbs=true;
            if(self::delete_thumbnails($file)===false){
                return ['success'=>false,'msg'=>'Erro ao excluir miniaturas antes de renomear o arquivo. Tente novamente.'];
            }
        }
        
        try{
            //renomeia
            $p=dirname($file->getPath()) . DIRECTORY_SEPARATOR . $name .'.'. $file->file_ext;//caminho com arquivo de nome alterado
            (new Filesystem)->move($file->getPath(), $p);
            $file->update(['file_name'=>$name]);
            $r=['success'=>true,'msg' => 'Arquivo renomeado','action'=>'edit'];
        } catch (\Exception $e) {
           $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        
        if($r['success'] && $is_thumbs){
            //recria as miniaturas
            self::create_thumbnails($id);
        }
        
        return $r;
    }
    
    
    /**
     * Deleta o arquivo
     * @param int $id
     * @param array $controllerClass - classe do controller de configuração (classe \App\Http\Controllers\FilesController)
     */
    public static function removeFile($id,$controllerClass=null){
        if(empty($id))return ['success'=>false,'msg'=>'Erro ao remover arquivo. Parâmetro id inválido.'];
        $file = is_object($id) ? $id : self::getModel()->find($id);
        if(!$file){
            return ['success'=>false,'msg'=>'Erro ao localizar registro em "files"'];
        }
        
        if($controllerClass){
            //verifica se a pasta informada está na autorizada
            if(!self::folderInFolder(array_keys($controllerClass->getConfig('folders_list')),$file->folder)){
                return ['success'=>false,'msg'=>'Pasta '.$file->folder. ' não permitido'];
            }
        }
        
        try{
            //deleta o arquivo principal
            $p=$file->getPath();
            if(file_exists($p))(new Filesystem)->delete($p);
            if(file_exists($p))return ['success'=>false,'msg'=>'Falha ao remover arquivo do diretório'];
            
            //deleta as miniaturas
            self::delete_thumbnails($file);
            
            //deleta as associações do arquivo
            self::removeRelation(['file_id'=>$id]);
            
            //deleta os todos os metadados
            //'files' = nome da tabela para o padrão area_name do metadata
            $r = MetadataService::del('files', $id);
            
            if($r===true){
                //deleta o registro
                $file->delete();
                $r=['success'=>true,'msg' => 'Arquivo deletado'];
            }else{
                $r=['success'=>false,'msg'=> 'Erro a remover arquivo. '.$r];
            }
            
        } catch (\Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        return $r;
    }
    
    
    /**
     * Move para a lixeira ou restaura o arquivo
     * @param int $id
     * @param striong $action - valores: 'trash' (default), 'restore' tira da lixiera.
     * @param array $controllerClass - classe do controller de configuração (classe \App\Http\Controllers\FilesController)
     */
    public static function trashFile($id,$action='trash',$controllerClass=null) {
        if(empty($id))return ['success'=>false,'msg'=>'Erro mover arquivo para a lixeira. Parâmetro id inválido.'];
        if($controllerClass){
            //...
        }
        try{
           $files = self::getModel()->find($id)->update(['deleted_at'=> ($action=='restore'?null:date("Y-m-d H:i:s")) ]);
           $r=['success'=>true,'msg' => 'Arquivo '.($action=='restore'?'restaurado':'excluído'),'action'=>$action];
        } catch (\Exception $e) {
           $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        return $r;
    }
    
    
    /**
     * Adiciona um relacionamento do arquivo com outras tabelas.
     */
    public static function addRelation($file_id,$area_name,$area_id) {
        $data = ['file_id'=>$file_id,'area_name'=>$area_name,'area_id'=>$area_id,'status'=>'a'];//status = a - registro normal
        try{
            $files = self::getModelRelation()->create($data);
            $r=[
                'success'=>true,
                'msg' => 'Relação de arquivo adicionado',
                'action'=>'add',
                'id'=>$files->id
            ];
        } catch (\Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        return $r;
    }
    
    
    /**
     * Cancela ou remove o relacionamento do arquivo com outras tabelas.
     * @param array $opt:
     *      //informar apenas um dos parâmetros abaixo (file_id OU area_name area_id)
     *      int id - id único da tabela files_relations (opcional)
     *      int file_id - id do arquivo
     *      string,int area_name, area_id - nome e id da área correspondentes
     *      string area_name_prefix - o nomes de area_name, mas utiliza o operador like iniciando sempre com o valor informado. Ex: area_name_prefix='post' // corresponde a area_name like 'post%'
     */
    public static function removeRelation($opts){
        $opt = array_merge([
            'id'=>null,
            'file_id'=>null,
            'area_name'=>null,
            'area_id'=>0,
            'area_name_prefix'=>null,
        ],$opts);
        if($opt['id'] || $opt['file_id'] || (($opt['area_name'] || $opt['area_name_prefix']) && $opt['area_id'])){
            //contém os parâmetros requeridos
        }else{
            return ['success'=>false,'msg'=>'Parâmetros inválidos'];
        }
        
        $files = self::getModelRelation()->whereRaw('1=1');
        if($opt['id'])$files->where('id',$opt['id']);
        if($opt['file_id'])$files->where('file_id',$opt['file_id']);
        if($opt['area_name'])$files->where('area_name',$opt['area_name']);
        if($opt['area_id'])$files->where('area_id',$opt['area_id']);
        if($opt['area_name_prefix'])$files->where('area_name','like',$opt['area_name_prefix'].'%');
        
        foreach($files->get() as $file){
            try{
               $file->delete();
            } catch (\Exception $e) {
               return ['success'=>false,'msg'=>$e->getMessage()];
            }
        }
        return ['success'=>true,'msg' => 'Relação de arquivo removido'];
    }
  
 
    /**
     * Cria o caminho da pasta e retorna ao endereço do diretório
     * @param array $opt (opcionais):
     *  boolean private
     *  int account_id 
     *  string folder 
     *  string date 
     *  string filename 
     * @return array - dir, basedir
     */
    public static function createStoragePath($opts) {
        $opt = array_merge([
            'private'=>false,
            'account_id'=>null,
            'folder'=>'uploads',
            'date'=>date('Y-m-d'),//data de referência para localização da pasta
            'filename'=>'',//se informado, retorna na função o nome do arquivo ao caminho apenas
            'folder_date'=>true, //se false, irá gravar o arquivo diretamente no diretório informado, se true irá gravar com o ano/mês. Default true
            'account_off'=>false   //se true, considerado o diretório /app ao invés do diretório accounts
        ],$opts);
        
        $prefix = Config::adminPrefix();
        if($prefix=='super-admin'){
            if($opt['account_id']==null){
                $opt['account_off']=true;
            }
        }
        
        //separado pela pasta account
        if($opt['account_off']===true){//sem a pasta account
            $path = ($opt['private']?'app':'storage'.DIRECTORY_SEPARATOR.'app').
                    DIRECTORY_SEPARATOR . $opt['folder'];
            
        }else{//separado pela pasta account
            if(empty($opt['account_id']))$opt['account_id']=Auth::User()->getAuthAccount('id');
            $path = ($opt['private']?'accounts':'storage'.DIRECTORY_SEPARATOR.'accounts').
                    DIRECTORY_SEPARATOR . $opt['account_id'].
                    DIRECTORY_SEPARATOR . $opt['folder'];
        }
        
        //separado por data
        if($opt['folder_date']){
            $oDate= \DateTime::createFromFormat('Y-m-d', $opt['date']);
            $path.= DIRECTORY_SEPARATOR . $oDate->format('Y').
                    DIRECTORY_SEPARATOR . $oDate->format('m');
        }
        
        
        //ajusta o diretório para a pasta pública ou privada
        $path_all = $opt['private']?storage_path($path):public_path($path);
        //dd($path,$path_all);
        //cria o diretório
        if(!file_exists($path_all)){
            (new Filesystem)->makeDirectory($path_all, $mode = 0777, true, true);
        }
        
        
        $r=[
            'dir'=>$path,
            'basedir'=>$path_all,
        ];
        if($opt['filename']){
            $r['dir'].=DIRECTORY_SEPARATOR.$opt['filename'];
            $r['basedir'].=DIRECTORY_SEPARATOR.$opt['filename'];
        }
        return $r;
        
    }
    
    
    
    /**
     * Retorna ao ícone a partir da extenção - ex: pdf, jpg
     * @param string $ext - extension|filename
     * @return array - image (path), class
     */
    public static function getIconExt($ext) {
        $i='default.png';
        $c='';
        $ext = strtolower($ext);
        
        if(empty($ext)){//não definido ou não encontrado
            return ['class'=>'fa-close','image'=>url('storage/images/files/notfound3.png')];
        }
        
        if(strpos($ext,'.')!==false)$ext = pathinfo($ext,PATHINFO_EXTENSION);//extensão do arquivo
        
        if(in_array($ext,['jpg','jpeg','gif','bmp','png','bmp'])){
            $c='fa fa-file-image-o';
            
        }else if(in_array($ext,['doc','docx'])){
            $c='fa fa-file-word-o';
            $i='document.png';
            
        }else if(in_array($ext,['xls','xlsx'])){
            $c='fa fa-file-excel-o';
            $i='spreadsheet.png';
            
        }else if(in_array($ext,['ppt','pptx'])){
            $c='fa fa-file-powerpoint-o';
            
        }else if(in_array($ext,['txt','csv'])){
            $c='fa fa-file-text-o';
            $i='text.png';
            
        }else if(in_array($ext,['zip','rar','gzip'])){
            $c='fa fa-file-zip-o';
            $i='archive.png';
            
        }else if(in_array($ext,['mp3','wma'])){
            $c='fa fa-file-audio-o';
            $i='audio.png';
            
        }else if(in_array($ext,['mp4','wmv','mpg','mpeg','mp4','mkv'])){
            $c='fa fa-file-movie-o';
            $i='video.png';
            
        }else if(in_array($ext,['pdf'])){
            $c='fa fa-file-pdf-o';
            $i='document.png';
            
        }else{
            $c='fa fa-file-o';
        }
        
        return ['class'=>$c,'image'=>url('storage/images/files/'.$i)];
    }
    
    /**
     * Gera a miniatura do arquivo se for imagem.
     * @param int|model $file_id - (int) ID from table 'files', (model) File
     * @param boolean|array $thumbnails - se true cria todas as miniaturas de $thumbnailsList, se array(small,medium...) cria somente as miniaturas especificadas. Default true.
     * @param boolean $force - se true, regrava os nomes das miniaturas no banco de dados (tabela files.file_thumbnails).
     * @param boolean $fit - se false corta a imagem se necessário para manter as dimensões exatas, se true ajusta as dimensões da imagem para para caber dentro do tamanho informado
     * Return void
     */
    public static function create_thumbnails($file_id,$thumbnails=true,$force=false,$fit=true){
        $data = is_numeric($file_id) ? self::getModel()->find($file_id) : $file_id;
        if(!$data)return;
        $path = dirname($data->getPath());//diretório onde será gravado
        //dump($data);
        
        if(is_string($thumbnails))$thumbnails=[$thumbnails];
        if(is_array($thumbnails)){
            $thumbnails=array_intersect_key(self::$thumbnailsList, array_flip($thumbnails));
        }elseif($thumbnails===true){
            $thumbnails=self::$thumbnailsList;
        }else{
            $thumbnails=[];
        }
        
        if(!in_array($data->file_mimetype,['image/gif','image/png','image/jpeg']))return;
        if($force)$data->file_thumbnails='';//reseta o campo para gravar todos os valores novamente
        
        $path.= DIRECTORY_SEPARATOR . self::$folder_thumbnails;
        if(!file_exists($path))(new Filesystem)->makeDirectory($path, 0777, true, true);
        
        $val_th=$data->file_thumbnails;
        foreach($thumbnails as $thumb_name=>$sizes){
            $p = $path . DIRECTORY_SEPARATOR . $data->file_name .'-{width_new}x{height_new}.'. $data->file_ext;
            
            $sizes_new = ImageUtility::resizeThumbnail($sizes[0], $sizes[1], $data->getPath(), $p,$fit);
            if($sizes_new===false)continue;
            $name_tmp = $thumb_name.'-'.$sizes_new[0].'x'.$sizes_new[1];
            
            if(strpos($val_th,$name_tmp)===false){//o registro desta miniatura não está no db
                $val_th.= ($val_th?'|':''). $name_tmp;
                $val_th = substr($val_th,0,200);//limite de caracteres
            }
        }
        //dd($val_th);
        $data->update(['file_thumbnails'=>$val_th]);
    }
    
    
    /**
     * Remove as miniaturas do arquivo.
     * @param File $fileModel
     * Return boolean - se ==false, não conseguiu excluir uma ou mais imagens
     */
    public static function delete_thumbnails($fileModel) {
        if(!empty($fileModel->file_thumbnails)){
            $list=$fileModel->getPathThumbnails();
            if($list){
                $oFS = (new Filesystem);
                foreach($list as $file){
                    if(file_exists($file)){
                        if($oFS->delete($file)===false){//erro ao excluir
                            return false;
                        }
                    }
                }
            }
        }
        
        return true;
    }
    
    
    /**
     * Verifica se uma pasta está dentro da outra. 
     * Ex: verifica se 'a/b/c' está dentro da lista ['a/b','b/c']. Neste caso retorna a true, pois existe um índice a/b em a/b/c 
     * @param $folders_list - [name=>title,...]
     * @param $compare - nome da pasta
     * @return boolean
     */
    private static function folderInFolder($folders_list,$compare){
        foreach($folders_list as $f){
            if(substr($compare,0,strlen($f))==$f){//está dentro do diretório, ex: 'a/b/ existe em 'a/b/c'
                return true;
            }
        }
        return false;
    }
}