<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FilesService;
use App\Services\FilesDirectService;
use Config;
use App;
use App\Utilities\FormatUtility;


class FilesController extends Controller{
    
    /***** Função para ser chamada fora desta classe *********/
    //Configuração padrão para quando este controller for chamado com o nome $controller diretamente com o nome desta mesma classe 'files'
    //Esta configuração serve de exemplo para as demais classes que forem utilizar os serviços deste controlar.
    //Requer apenas informar o nome do controller ao incorar as classes e na respectiva classe ter o método files__config()
    public function files__config(){
        return [
            //Gerais
            'basename'=>'files',       //o mesmo nome base da classe atual
            'access'=>'all',           //tipo do acesso, valores: all, public, private
            'folder_default'=>null,    //nome da pasta padrão. Default null para a primeira pasta em folders_list
            'folder_date'=>true,       //se indica se irá gravar o arquivo no diretório ano/mês dentro da pasta
            //Pastas padrão a partir do endereço /storage/accounts/id/{$folder}/... ou /storage/app/{$folder}/...
            'folders_list'=>[
                'uploads'=>'Uploads',
                'files'=>'Arquivos',
                'system'=>'Sistema',
                'tmp'=>'Temp',
            ],
            
            //nomes de pastas não permitidos (ex: ['images','apps'...]
            'folders_not'=>[],
            
            //tipos não permitidos
            'mimetypes_not'=>[
                'application/x-msdownload',
            ],
        ];
    }
    
    
    
    
    /****** Funções deste classe *********/
    public function getConfig(String $field=null,$def=''){
        return $field ? array_get($this->filesConfig_cache,$field,$def) : $this->filesConfig_cache;
    }
    
    private $filesConfig_cache=null;
    /**
     * Carrega o método files__config do controller informado
     * Necessário ser chamado para cada função que for acessado por uma rota com o paramêtro controller (ex rota: /filemanager/{controller}/fileload... - chamar na função fileload(): $this->callController($controller));
     */
    public function callController($controller){
        //.. parei aqui.. tem que restringir os nomes dos $controller somente para valores vários
        if($this->filesConfig_cache)return $this;
        if($controller=='files'){
            $c = $this->files__config();
        }else{
            $controller = str_replace('/','\\',$controller);
            $c0=explode('_',snake_case($controller))[0];//captura a primeira palavra
            $b = base_path();
            $c = '\\App\\Http\\Controllers\\'. studly_case($controller) .'Controller';
            if(!file_exists($b.fpath($c.'.php')))$c = '\\App\\Http\\Controllers\\'.ucfirst($c0).'\\'. studly_case($controller) .'Controller';
            $m = 'files__config';
            
            if(method_exists($c,$m)){
                $c = App::call($c.'@'.$m);
                $d = $this->files__config();
                //$c=FormatUtility::array_merge_recursive_distinct($d,$c);
                $c=array_merge($d,$c);//obs: aqui tem que usar o array_merge() mesmo para que todos os valores das chaves possam ser substituídos (ao invés de apenas os existentes)
            }else{
                exit('Método '.$c.'@'.$m.' não existe');
            }
        }
        $this->filesConfig_cache=$c;
        //dd($c);
        return $this;
    }
    
    
    
   /**
     * Abre o visualizador de arquivos
     */
    public function index($controller,Request $request){
        $this->callController($controller);
        
        $prefix = Config::adminPrefix();
        
        //carrega a lista de arquivos
        $list_params = [
            'files_opt'=>[
                'uploadSuccess'=>'route_load', //dispara a rota 'load' ajax
                'fileszone'=>['maximize'=>'.content-wrapper'],//j_files_list_zone
                'metabox'=>false,
                //'metabox'=>['class'=>'j_files_list_zone']        //classe identificadora da zona de upload
                'accept'=>'*',
                'upload_opt'=>[],
                //'show_view'=>false,
                //'list_compact'=>true,
            ],
            'files_filter'=>[
                //'folder'=>'aaa',
            ],
            'auto_list'=>[
                'routes'=>[
                    'click'=>function($reg) use($prefix,$controller){
                        return route($prefix.'.file.view',[$controller,$reg->id,$reg->file_name_full,'view=modal&rd='.urlencode(\Request::fullUrl()) ]) ;
                    },
                    'load'=>route($prefix.'.file',$controller).'/?'. \App\Utilities\HtmlUtility::rqArr(['page'=>null]),
                    'remove'=>route($prefix.'.file.remove',$controller),
                ],
                'class'=>'j-filemanager-wrap',
            ],
            'click_open_ajax'=>true, //para abrir a lista via ajax
        ];
        
        //mescla esta lista com os parâmetros
        $list_params = array_replace_recursive($list_params,$this->base_params_list($controller,$request));
        //dump($list_params);
        if($request->ajax()){//load ajax by ui.files_list.blade
            return view('templates.ui.files_list',$list_params);
        }else{//return view
            return view('filemanager.index',['list_params'=>$list_params]);
        }
    }
    
    
    /**
     * Retorna a view de visualização do arquivo
     * @param int $file_id
     * @param $request - valores:
     *      view - (opcional) indica o tipo de carregamento. Valores: '' (padrão), panel ou modal
     */
    public function view($controller,Request $request,$file_id,$filename){
        $this->callController($controller);
        
        $file = FilesService::getModel()->find($file_id);
        $file->setControllerConfig($this);//seta o controller para uso na model
        
        $view = $request->input('view');
        if(!in_array($view,['modal','panel']))$view='';
        
        $param=['controller'=>$controller,'file'=>$file];
        if($request->ajax()){
            return view('filemanager.view'. ($view?'_'.$view:'') ,$param);
        }else{
            return view('filemanager.view',$param);
        }
    }
    
    
    //******************** manipulação direta com o arquivo ********************
    /**
     * Post de arquivos direto em diretórios. Utiliza a tabela 'files' com a classe 'App\Services\FilesDirectService'.
     * @param Request $request - valores esperados:
     *      string action  - nome da ação. Valores: upload, remove, info
     *      (object upload) file - arquivo de upload do campo input file (somente para action=upload)
     *      
     *      //para action: remove, info:
     *      string file - deve ser informado o nome.extensão do arquivo
     *      string folder - deve ser a pasta do arquivo a partir do diretório base, ex:
     *                      public folder   - '/storage/accounts/1/filesf'
     *                      private folder  - '\accounts\1\uploads\2019\12'
     *      string private - se =='s', então irá considerar a pasta privada. Default 'n'.
     *      string account_off - se =='s', será considerado o diretório /app ao invés do diretório accounts
     *      string account_id  - id da conta. Se não informado, irá capturar o valor de Auth::User()->getAuthAccount('id'). Válido apenas para account_off=false
     * 
     * Obs: para action=upload:
     *      - No request precisa ter o campo com o nome 'file' obrigatoriamente
     *      - Aceita o campo data-opt com os parâmetros adicionais do upload (formato JSON) - mais na classe FilesDirectService.php
     */
    public function postDirect(Request $request){
        $data = $request->all();
        switch($data['action']??null){
        case 'upload':
            $opt = $data['data-opt']??null;//opção ao postar o upload
            
            if(!empty($opt)){
                $opt = json_decode($opt,true);
                if(json_last_error() !== JSON_ERROR_NONE){
                    return ['success'=>false,'msg'=>'Parâmetro informado data-opt de upload inválido'];
                }
            }else{
                $opt=[];
            }
            if(!isset($data['file']))$data['file']=null;
            //dd($data,$opt);
            $r = FilesDirectService::uploadFile($data['file'],$opt);
            
            break;
        
        case 'remove'://remove definitivamente
            $r = FilesDirectService::removeFile([
                    'filename'=>basename($data['file']),
                    'folder'=>$data['folder'],
                    'private'=>array_get($data,'private')=='s',
                    'account_off'=>array_get($data,'account_off')=='s',
                    'account_id'=>$data['account_id']??null,
                ]);
            break;
         
        case 'info':
            $r = FilesDirectService::getInfo([
                    'filename'=>basename($data['file']),
                    'folder'=>$data['folder'],
                    'private'=>array_get($data,'private')=='s',
                    'account_off'=>array_get($data,'account_off')=='s',
                    'account_id'=>$data['account_id']??null,
                ],'json',$this);
            break;
            
        default:
            $r = ['success'=>false,'msg'=>'Erro de parâmetro action'];
        }
        
        return $r;
    }
    
    
    /**
     * Acesso direto pelo arquivo na pasta privada. Não utiliza a tabela 'files'.
     * @param string $data_serialize - serialize array php com os mesmos parâmetro de FilesDirectService::getInfo()
     * Exemplos de rotas:
     * Url file         - route('app.filedirect.load','....')
     */
    public function loadDirect($data_serialize){
        if(empty($data_serialize))exit('Erro de parãmetro');
        $opt=unserialize(base64_decode($data_serialize));
        $file = FilesDirectService::getInfo($opt,'array');
        
        if(!$file['success']){
            header('HTTP/1.0 404 Not Found');
            exit('Arquivo não localizado');
        }
        
        header('Content-type: '.$file['file_mimetype']);
        header("Content-Length: " . $file['file_size']);
        header('Content-Disposition: filename="'.$file['file_name_full'].'"');

        
        $myfile = fopen($file['file_path'], "r") or die("Unable to open file!");
        echo fread($myfile,$file['file_size']);
        fclose($myfile);
    }
    
    
    
    
    
    
    //******************** utiliza o db 'files' para registrar os arquivos ********************
    
    /**
     * Posts gerais de arquivos. Utiliza a tabela 'files' com a classe 'App\Services\FilesService'.
     * @param Request $request - valores esperados:
     *      string action  - nome da ação. Valores: upload, rename, remove, trash, restore, edit, info
     *      (object upload) file - arquivo de upload do campo input file (somente para action=upload)
     *      int file_id - para action: rename, remove trash, retore, edit
     *      string file_name - para action: rename
     *      string file_title - para action: edit
     *      array metadata - - para action: edit
     *      string account_id  - id da conta. Se não informado, irá capturar o valor de Auth::User()->getAuthAccount('id'). Válido apenas para account_off=false
     * 
     * Obs: para action=upload:
     *      - No request precisa ter o campo com o nome 'file' obrigatoriamente
     *      - Aceita o campo data-opt com os parâmetros adicionais do upload (formato JSON) - mais na classe FilesService.php
     * @return array[status,msg,...]
     */
    public function post($controller,Request $request){
        $data = $request->all();
        $this->callController($controller);
        
        if(isset($data['id'])){//lógica: caso venha um atributo 'id', altera para file_id
            $data['file_id'] = $data['id'];
            unset($data['id']);
        }
        
        switch($data['action']??null){
        case 'upload':
            //sleep(5);return ['success'=>false,'msg'=>'Erro de parâmetro'];//test
            $opt = $data['data-opt']??null;//opção ao postar o upload
            if(!empty($opt)){
                $opt = json_decode($opt,true);
                if(json_last_error() !== JSON_ERROR_NONE){
                    return ['success'=>false,'msg'=>'Parâmetro informado data-opt de upload inválido'];
                }
            }else{
                $opt=[];
            }
            //dd($data,$opt);
            $r = FilesService::uploadFile($data['file']??null,$opt,$this);
            if($r['success']){
                $r = FilesService::getInfo($r['id'],'json',$this);
            }
            
            //Obs: por default, retornará aos parâmetros: : created_at,deleted_at,file_ext,file_mimetype,file_name,file_size,file_size_format,file_title,folder,id,private,success,url_view,user_id
            break;
        
        case 'rename':
            $r = FilesService::renameFile($data['file_id'],$data['file_name']);
            break;
            
        case 'remove'://remove definitivamente
            $r = FilesService::removeFile($data['file_id'],$this);
            break;
            
        case 'trash': case 'restore'://adiciona ou restaura da lixeira
            $r = FilesService::trashFile($data['file_id'],$data['action'],$this);
            break;
            
        case 'edit':
            $r = FilesService::setData($data['file_id'],$data);
            break;
            
        case 'info':
            $r = FilesService::getInfo($data['file_id']);
            break;
            
        default:
            $r = ['success'=>false,'msg'=>'Erro de parâmetro action'];
        }
        
        return $r;
    }
    
    
    /**
     * Acesso direto pelo arquivo informado pelo ID (precisa estar logado)
     * Retorna ao carregamento do arquivo
     * Exemplos de rotas:
     * Url file         - route('app.file.load',[$controller,id,filename])
     * Url thumbnail    - route('app.file.load',[$controller,id,'thumbnail',filename_thumbnail])
     */
    public function load($controller,$file_id,$thumbnail,$filename=null){
        if(!\Auth::check())exit('Acesso negado');
        if(empty($controller) || empty($file_id) || empty($thumbnail))exit('Erro de parâmetro');
        
        $this->callController($controller);
        
        if(!$filename){$filename=$thumbnail;$thumbnail='';}
        $file = FilesService::getModel()->findOrFail($file_id);//caso não encontre o registro, dispara uma exceção
        $file->setControllerConfig($this);//seta o controller para uso na model
        
        if(!file_exists($file->getPath())){
            header('HTTP/1.0 404 Not Found');
            exit('Arquivo não localizado');
        }
        
        if($thumbnail){//miniatura
            $path='';
            foreach($file->getPathThumbnails() as $th_name => $th){
                if($thumbnail==$th_name || basename($th) == $filename){
                    $path=$th;break;
                }
            }
            //dd($controller,$file_id,$thumbnail,$filename,$path,$file->getPathThumbnails());
            if(!$path){
                header('HTTP/1.0 404 Not Found');
                exit('Arquivo não localizado(3)');
            }
            
        }else{//arquivi principal
            if(strtolower($file->file_name.'.'.$file->file_ext)!= strtolower($filename)){
                header('HTTP/1.0 404 Not Found');
                exit('Arquivo não localizado (2)');
            }
            $path = $file->getPath();
        }
        
        $size = filesize($path);
        
        header('Content-type: '.$file->file_mimetype);
        header("Content-Length: " . $size);
        header('Content-Disposition: filename="'.$filename.'"');

        
        $myfile = fopen($path, "r") or die("Unable to open file!");
        echo fread($myfile,$size);
        fclose($myfile);
    }
    
    
    
    /**
     * Janela modal de edição padrão do arquivo
     */
    public function get_editFile(Request $req,$id){
        //$controller = $req->controller;   //obs: nesta função, até o momento, não é usado as configurações de controller
        $controller='files';
        $file=FilesService::getInfo($id,'object');
        
        $f=$req->fields;
        
        //converte de string boolean para boolean
        $f['status']=$f['status']=='true';
        $f['title']=$f['title']=='true';
        $req->fields=$f;
        
        if($f['area_name']!='' && $f['area_id']!=''){
            //$file->area_name = $req->area_name;
            //$file->area_name = $req->area_name;
            $m=\App\Models\FileRelation::where(['file_id'=>$id,'area_name'=>$f['area_name'], 'area_id'=>$f['area_id']])->first();
            $file->relation=(object)[
                'area_name'=>$f['area_name'],
                'area_id'=>$f['area_id'],
                'status'=>$m->status,
            ];
        }
        
        return view('templates.ajax_load',[
            'view'=>'filemanager.edit_default',
            'data'=>[
                'file'=>$file,
                'fields'=>$req->fields,
                'controller'=>$controller,
                //'route_update'=>      //já será definido automaticamente
            ]
        ]);
    }
    
    
    
    /**
     * Retorna aos dados do registro
     * @param Request - POST id - para mais de um id, separar por virgula
     */
    public function getData($controller,Request $request){
        $this->callController($controller);
        
        $ids = $request->input('id');
        
        if(is_string($ids))$ids=explode(',',$ids);//converte em array
        $r=[];
        foreach($ids as $id){
            $r[$id]=FilesService::getInfo($id,'json',$this);
        }
        
        return $r;
    }
    
    
    /**
     * Visualização das miniaturas
     */
    public function get_thumbnails(Request $request,$file_id){
        if(!$file_id)return 'Não encontrado';
        $this->callController('files');
        
        $file = FilesService::getInfo($file_id,'json',$this);
        return view('filemanager.view_thumbnails',['file'=>$file]);
    }
    
    
    /**
     * Carregamento a lista de arquivos para uma janela 
     * Parâmetros $request:
     *      load_type   - indica que é o primeiro carregamento em janela modal (Obs: este parâmetro existe e é informado automaticamente pela função publicjs/main.js->awFilemanager() para o correto funcionamento dentro de uma janela modal em ajax)
     *      source      - origem da requisição. Valor atual aceito: 'ckeditor'. 
     *      ...         - demais parâmetros de filtros conforme definidos pela função $this->base_params_list()
     */
    public function indexModal($controller,Request $request){
        $this->callController($controller);
        $prefix = Config::adminPrefix();
        
        $list_params = [
            'files_opt'=>[
                'uploadSuccess'=>'route_load', //dispara a rota 'load' ajax
                'fileszone'=>['maximize'=>'.j_files_list_zone'],
                'metabox'=>[
                    'class'=>'j_files_list_zone no-margin'        //classe identificadora da zona de upload
                ],
                'modeview_img'=> _GET('modeview')=='',
                'mode_select'=>true,//altera para o modo de selecionar arquivos
                'file_view'=>'panel',//informa que terá um painel de visualização de arquivos
                'filetype'=> _GET('filetype'),
                //'accept'=> _GET('accept') ?? _GET('mimetype'),
                //'form_opt'=>['uploadProgress'=>false], //desativa o recurso awUploadProgress
            ],
            'auto_list'=>[
                'options'=>[
                    //'select_type'=>1,//somente 1 seleção por vez
                    'checkbox'=>false,
                ],
                'routes'=>[
                    'load'=>route($prefix.'.file.getmodal',$controller),
                    'click'=>function($reg) use($prefix,$controller){
                        return route($prefix.'.file.view',[$controller,$reg->id,$reg->file_name_full,'view=panel&rd='.urlencode(\Request::fullUrl()) ]) ;
                    },
                    'remove'=>route($prefix.'.file.remove',$controller),
                ],
                'class'=>'j-filemanager-wrap',
            ],
        ];
        if($request->input('load_type')=='modal'){//indica que é o primeiro carregamento em janela modal
            $list_params['auto_list']['is_ajax_load']=false; //=false para que carregue o template completo com todos os elementos UI
        }
        
        //mescla esta lista com os parâmetros
        $list_params = array_replace_recursive($list_params,$this->base_params_list($controller,$request));
        
        if($request->input('source')=='ckeditor' && !$request->ajax()){
            $list_params['files_opt']['metabox']=false;
            return view('filemanager.ckeditor',['list_params'=>$list_params]);
        }else{
            //utiliza a view templates.ajax_load para carregar os recursos javascript corretamente
            return view('templates.ajax_load',['view'=>'templates.ui.files_list','data'=>$list_params]);
        }
    }
    
    
    /**
     * Remove apenas a relação do arquivo (tabela files_relation)
     * @param Request $req esperados - parâmetros: id (file_id), area_name, area_id
     */
    public function post_removeRelation(Request $req){
        if(empty($req->id) || empty($req->area_name) || $req->id=='' || !in_array($req->action,['trash','remove']))return ['success'=>false,'msg'=>'Erro de parâmetro'];
        $r = FilesService::removeRelation(['file_id'=>$req->id, 'area_name'=>$req->area_name, 'area_id'=>$req->area_id]);
        return $r;
    }
    
    
    /**
     * Classe base de parâmetros para merclar com a os métodos de lista de arquivos: indexModal() e index()
     * @param array $opt:
     *      private:false,          //indica se o acesso será a uma pasta privada
     *      area_name:'',           //filtro por area_name
     *      area_id:'',             //filtro por area_id
     *      area_status:'',         //filtro por area_status
     *      metadata:'',            //filtro por metadata
     *      meta_name|meta_value:'',//...
     *      taxs:'',                //ids da taxonomia
     *      folder:'uploads',       //nome da pasta base (+ informações na documentação da tabela 'files')
     *      filetype:'',            //tipos de arquivos filtrados a serem exibidos. Valores: '' (todos), image, audio, video, pdf (para mais de um valor separar por virgula)
     *      mimetype:'',            //filtro por mimetype (para mais de um valor separar por virgula)
     *      q:'',                   //texto para pesquisa de arquivos
     *      multiple:false,         //se true permite a seleção de vários arquivos
     *      onSelectFile:null,      //callback ao selecionar as imagens
     *      show_folder:true,       //se false - irá ocultar o filtro de patas
     *      show_trash:true,        //se false - oculta a opção de exibir registros da lixeira
     *      show_regs:true,         //se false - oculta a opção de registros por página 
     *      show_view_img:true,     //se false - oculta a opção de registros por página 
     *      show_remove:true,       //se false - oculta a opção de remove 
     *      show_upload:true,       //se false - oculta a opção de upload
     * @return array
     */
    private function base_params_list($controller,$opt){
        $files_filter=[];
        $files_opt=[];
        $auto_list_opt=[];
        
        //filtro de dados
        if($opt['private']??false)$files_filter['private'] = self::xBool($opt['private']);
        if($opt['folder']??false)$files_filter['folder'] = $opt['folder'];
        if($opt['q']??false)$files_filter['search'] = $opt['q'];
        if($opt['filetype']??false)$files_filter['filetype'] = $opt['filetype'];
        if($opt['mimetype']??false)$files_filter['mimetype'] = $opt['mimetype'];
        if($opt['area_name']??false)$files_filter['area_name'] = $opt['area_name'];
        if($opt['area_id']??false)$files_filter['area_id'] = $opt['area_id'];
        if($opt['area_status']??false)$files_filter['area_status'] = $opt['area_status'];
        if($opt['metadata']??false)$files_filter['metadata'] = $opt['metadata'];
        if($opt['meta_name']??false)$files_filter['meta_name'] = $opt['meta_name'];
        if($opt['meta_value']??false)$files_filter['meta_value'] = $opt['meta_value'];
        if($opt['taxs']??false)$files_filter['taxs'] = $opt['taxs'];
        
        //opções da lista
        if(self::xBool($opt['show_folder']??true)==false){$files_opt['bt_folder']=false; $files_opt['bt_access']=false;}
        if(self::xBool($opt['show_trash']??true)==false)$auto_list_opt['list_remove']=false;
        if(self::xBool($opt['show_regs']??true)==false)$auto_list_opt['regs']=false;
        if(self::xBool($opt['show_view_img']??true)==false)$files_opt['mode_view']=false;
        if(self::xBool($opt['show_remove']??true)==false){
            $files_opt['bt_remove']=false;//botão de remover
            $auto_list_opt['list_remove']=false;//lista da lixeira
        }
        if(self::xBool($opt['show_upload']??true)==false){//upload
            $files_opt['bt_upload']=false;
        }
        if($opt['multiple']??false)$auto_list_opt['select_type'] = self::xBool($opt['multiple']) ? 2 : 1;//1 permite uma seleção, 2 permite várias seleções
        if($opt['allow_trash']??false)$auto_list_opt['allow_trash'] = self::xBool($opt['allow_trash']);
       
        //dd($files_filter,$files_opt,$auto_list_opt);
        
        return [
            'controller'=>$this,
            'files_filter'=>$files_filter,
            'files_opt'=>$files_opt,
            'auto_list'=>[
                'options'=>$auto_list_opt,
            ],
        ];
    }
    
    //Converte para boolean nos casos: "true", true, "s", caso contrário false
    private static function xBool($v){
        return $v=='true' || $v===true || $v=='s' ? true : false;
    }
    
    
    
}
