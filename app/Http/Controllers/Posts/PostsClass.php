<?php

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Account;
use App\Models\Posts\Post;
use App\Models\Posts\PostData;
use App\Models\Posts\PostHist;
use App\Models\Posts\PostFolder;
use Config;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\HTMLUtility;
use App\Services\TaxsService;
use App\Services\FilesService;
use App\Services\PostsService;

/*
 * Controller principal responsável pelo gerenciamento do cadastro de lista de posts.
 * Deve ser extendida a partir de um controller com um nome de posttype
 */
class PostsClass extends Controller{
    
    protected $Post,$postData,$PostHist;
    private $prefix;
    private $config;
    
    /**
     *Define se o acesso a esta classe é por uma rota pública / não autenticada
     */
    protected $public=false;
    
    
    /**
     * Nome do posttype a ser definido pela classe extendida.
     * É obrigatório ter o nome da classe pai
     * Tamanho máximo 20 caracteres
     */
    public $post_type='';
    
    /**
     * Nome da classe para vincular ao agrupamento por pastas
     * Ex: manuais (para ManuaisController)
     */
    public $post_folder=null;
    
    
    public $post_status=['0'=>'Rascunho','r'=>'Revisão','p'=>'Publicado','a'=>'Arquivado','c'=>'Cancelado'];
    public $post_content_type=['t'=>'Texto','h'=>'HTML','m'=>'Markdown','b'=>'Pagebuilder (desenvolvimento)'];
    public $post_visibility=['p'=>'Público','r'=>'Privado','s'=>'Protegido por Senha','u'=>'Restrito por Nível de Acesso'];
    public $post_visibility_opt=[
        'p'=>['icon'=>'fa-globe'],
        'r'=>['icon'=>'fa-lock'],
        's'=>['icon'=>'fa-unlock-alt'],
        'u'=>['icon'=>'fa-user'],
    ];
    
    public function __construct(){
        if(!$this->post_type)exit('Posttype não definido');
        $this->Post = new Post;
        $this->PostData = new PostData;
        $this->PostHist = new PostHist;
        $this->prefix = Config::adminPrefix();
    }
    
    
    /**
     * Carrega a configuração padrão do tipo do post
     * @param $opt - opções a serem mescladas
     */
    public function config($opt=[]){
        if(!$this->config)
        $this->config=[
            //rótulos de todas as áreas
            'labels'=>[
                '_p'=>'o',  //artigo
                'name'=>'Posts',
                'singular_name'=>'Post',
                'add_post'=>'Nov{_p} {singular_name}',
                'edit_post'=>'Editar {singular_name}'
            ],
            
            //nível do usuário que tem permissão para gerenciar o post (add,edit,del). Valores: dev,superadmin,admin,user. Caso não defindo default 'superadmin'. Obs: se definir ex user, todos os níveis acima tem acesso.
            'allow'=>'superadmin',
            
            //edição do post - habilita os recursos - sintaxe: name => (boolean|string|array option)
            'edit'=>[
                'template'=>null,           //nome do template padrão
                'name'=>true,               //postname (slug)
                'status'=>true,
                'visibility'=>true,
                'date'=>true,               //data da publicação
                'remove'=>true,
                'title'=>true,
                'resume'=>true,
                'content'=>true,
                'content_type'=>true,
                'content_filemanager'=>true,//configuração do gerenciador de arquivos quando aberto dentro do editor. True ativa na configuração padrão, False desativa, [] opções (veja em public/js/main.js->awFilemanager() ). 
                'content_params'=>[],       //(array) configuração adicional do editor. Se informado, estes valores devem estar compatíveis com a configuração de cada editor (veja + em templates.components.editor|editorcode (válido para content_type=t|h|m) )
                'taxs'=>true,               //(boolean ou array term_id) exibe as taxonomias
                'parent'=>true,
                'order'=>true,
                'version'=>true,
                'visibility'=>true,
                'user'=>true,              //usuário
                
                //filtros para as listas do campo select, informar apenas as chaves (vazio = todos). Obs: as opções abaixo servem para apenas filtrar os de ex $this->post_status, mas também é possível simplesmente sobrescrever esta propriedade na classe filha
                'filters'=>[
                    'status'=>[],
                    'content_type'=>[],
                    'visibility'=>[],
                ],
                
                //irá procurar os métodos get_metabox{Name} e save_metabox{Name}
                'metaboxs'=>['image','attach'], //,'version'
                
                //campos requeridos, valores: aceitos: title, content, resume, taxs_{term_id}, parent, user
                'required'=>['title','content'],
                
                //ação após salvar - valores: ajax, reload
                'action_save'=>'ajax',
            ],
            
            //opções padrões para adição/edição do post
            'edit_defaults'=>[
                'content_type'=>'t',        //tipo de conteúdo - Valores: t texto, h html, m markdown, b pagebuilder
                'visibility'=>'r',          //visibilidade - valores: p public, r private, s password, u user_level
            ],
            
            //taxonomias aceitas
            'taxs'=>[],                     //ids da tabela term.id permitidos //aceita array (ex [1,3]) ou function (ex: function($action,$folder){ return [1,2] || model terms }
            
            //opçoes da lista de posts
            'list'=>[
                'template'=>null,           //nome do template padrão
                'taxs'=>true,               //(boolean ou array term_id) exibe as taxonomias na lista
                'taxs_filter'=>true,        //(boolean ou array term_id) filtro das taxonomias
                'columns'=>null,            //array de colunas permitidas (visíveis na respectiva ordem). Default [] (todos)
                'columns_show'=>null,       //array relação de colunas visíveis por padrão. Default [].
                'columns_hide'=>null,       //array relação de colunas que não deve exibir. Default [].
                'row_links'=>true,          //false - não exibe os links padrão abaixo do título, true - exibe os links padrões, function - função para cada linha (parâmetros $reg)
                'auto_list'=>null,          //array para mesclagem dos parâmetros da view templates.ui.auto_list
                'bt_add'=>true,             //botão de adicionar post
                'search'=>true,             //campo de busca
                'regs'=>15,                 //registros por página
            ],
            
            //opções de visualização do post dentro da view padrão (posts.view|view-public.blade)
            'view'=>[
                //nome do template padrão
                'template'=>null,           //template padrão
                'template_public'=>null,    //automático '{template}-public'
                'template_pass'=>null,      //automático '{template}-password'
                
                //campos a exibir
                'title'=>true,
                'resume'=>true,
                'content'=>true,
                'is_edit'=>true,    //botão de edição do post (visível somente se houver permissão)
                //irá exibir os metaboxs padrões
                'metaboxs'=>['image','attach'],
                'taxs'=>true,           //(boolean ou array term_id) exibe as taxonomias na lista
                'tax_menu'=>false,       //(boolean) exibe o menu lateral com o diretório das taxonomias
            ],
            
            //opções de visualização da lista post de da visualização padrão, não considerando a edição de dados (posts.list-view
            'view_list'=>[
                'template'=>null,           //nome do template padrão
                'taxs'=>true,               //(boolean ou array term_id) exibe as taxonomias na lista
                'tax_menu'=>false,       //(boolean) exibe o menu lateral com o diretório das taxonomias
            ],
            
            
            //indica se os arquivos do gerenciador estarão fixados em uma pasta deste post. 
            //se true os arquivos serão gravados de forma individual na pasta do post atual e também removidos ao remover o post
            'files_saved_post'=>false,
        ];
        
        $c=$this->config;
        
        if($opt){
            $c=FormatUtility::array_merge_recursive_distinct($c,$opt,true);
            
            //abaixo verifica se alguma das configurações base não ficou vazia e neste caso seta o padrão
                foreach(['labels','allow','edit','edit_defaults','list','view'] as $x){
                    if(!isset($c[$x]) || is_null($c[$x]))$c[$x]=$this->config[$x];
                }
                
            //ajustes finais dos campos requeridos na edição
                $r=[];
                foreach($c['edit']['required'] as $q){
                    if($q && ($c['edit'][$q]??true)==false){//quer dizer que o campo setado requerido não está visível na edição
                        //nenhuma ação aqui
                    }else{
                        $r[]=$q;
                    }
                }
                $c['edit']['required']=$r;
                
            //atualiza a config da função
                $this->config = $c;
        }
        
        if($this->post_folder){//está ligado a uma pasta de posts
            //atualiza a configuração
            $folderConfig = $this->classPostFolder()->config();
            $c['labels']=FormatUtility::array_merge_recursive_distinct($c['labels'],$folderConfig['labels'],true);
            $c=FormatUtility::array_merge_recursive_distinct($c,$folderConfig['post_config'],true);
            //dd($folderConfig,$c);
        }
        return $c;
    }
    
    
    /**
     * Retorna ao caminha da pasta exclusiva desta aplicação
     */
    public function getDirPost($post=null){
        return 'posts' . DIRECTORY_SEPARATOR. $this->post_type . ($post ? DIRECTORY_SEPARATOR. $post->id : '');
    }
    
    
    /**
     * Configuração do gerenciador de arquivos (from \App\Http\Controllers\FileController@files__config)
     */
    public function files__config(){
        $classname=$this->thisClassName()['short_name'];
        return [
            'basename'=>$classname,//o mesmo nome da classe
            'access'=>'public',
            'folder_date'=>$this->getConfig('files_saved_post')==false,//se true não gera a divisão de pastas ano/mês
            'folders_list'=>[
                    'uploads'=>'Uploads',
                    $this->getDirPost() => $this->getConfig('labels.name'),    //salva na pasta app/posts/{post_type}
                ]
        ];
    }
    
    
    
    
    /**
     * Captura a configuração
     */
    public function getConfig(String $fields,$def=''){
        return array_get($this->config(),$fields,$def);
    }
    
    /**
     * Retorna ao label
     */
    public function getLabel($label_name){
        return PostsService::labels($this->config()['labels'],$label_name);
    }
    
    
    /**
     * Retorna a conta atual considerando se é uma conta superadmin ou não
     * Lógica: se superadmin retorna a null, caso contrário retorna ao id da conta
     */
    private static $account_id;
    private function getAccountId(){
        if(!self::$account_id)self::$account_id=$this->prefix=='super-admin' ? null : Config::accountID();
        return self::$account_id;
    }
    
    /**
     * Captura a classe da pasta
     */
    private $class_post_folder=null;
    public function classPostFolder(){
        if(!$this->post_folder)return;
        if($this->class_post_folder)return $this->class_post_folder;
        $b=base_path();
        $c='\\App\\Http\\Controllers\\';
        $a=studly_case($this->post_folder).'Controller';
        $p=$b.$c.$a;
        if(file_exists($p.'.php')){
            $c=$c.$a;
        }else{
            $x=ucfirst(explode('_',str_replace('-','_',$this->post_folder))[0]);
            $c=$c.$x.'\\'.$a;
        }
        //dd($c);
        $c=\App::make($c);
        $this->class_post_folder=$c;
        return $c;
    }
    
    
    public function index(Request $request){
        //redireciona para a lista
        return \Redirect::to( $request->url() .'/list' )->send();
    }
    
    
    /**
     * Página de lista de posts
     * @param $request - parâmetros esperados:
     *          area_name, area_id
     */
    public function get_list(Request $request,$post_folder_id){
        if(!$this->validPostFolderId($post_folder_id))return ['success'=>false,'msg'=>'Acesso negado (folder id)']; 
        
        $config = $this->config();
        $configList = $config['list'];
        
        $params=[
            'search'=>$request->input('search'),
            'paginate'=> (_GETNumber('regs')??$configList['regs']),
            'taxs'=>$request->input('taxs_id'),
            'is_trash'=>_GET('is_trash')=='s',
            'filter_visibility'=>true,
            'post_folder_id'=>$post_folder_id,
            //'hierarchy'=>true,
            //'merge_list'=>true,
        ];
        
        $posts = PostsService::getList($this->post_type,$params,'model');
        //dd($posts);
        //dump($posts->total());
        //dd($posts->pluck('post_title_level','id')->toArray());
        //dd(collect($posts)->pluck('post_title_level','id')->toArray());
        
        
        return $this->xview('list',[
            'post_type'=>$this->post_type,
            'post_folder_id'=>$post_folder_id,
            'posts'=>$posts,
            'thisClass'=>$this,
            'config'=>$config,
            'post_model'=>$this->Post,
            'area_name'=>null,
            'area_id'=>null,
            'prefix'=>$this->prefix,
        ]);
    }
    
    
    /**
     * Página de visualização do post
     */
    public function get_view($names){
        $post = PostsService::findByName($this->post_type, $names,$this->post_folder);
        if(!$post)return 'Post não encontrado';
        
        $postFolder = $post->post_folder_id ? $this->postFolder($post->post_folder_id) : null;
        
        $termsList = $this->getTermsList('view',$post->post_folder_id);
        if($termsList && $termsList->count()==0)$termsList=null;

        $config = $this->config();
        $configView = $config['view'];
        
        $params=[
            'post_type'=>$this->post_type,
            'post'=>$post,
            'postFolder'=>$postFolder,
            'thisClass'=>$this,
            'config'=>$config,
            'post_model'=>$this->Post,
            'area_name'=>null,
            'area_id'=>null,
            'prefix'=>$this->prefix,
            'title_type' => $this->getLabel('name'),
            'title_folder' => $postFolder ? $postFolder->folder_title : $this->getLabel('name'),
            'termsList'=>$termsList,
        ];
        
        $sxpublic=$this->public?'-public':'';
        
        if(!$this->allow($post,'view')){
            if($post->post_visibility=='s'){//protegido por senha
                return $this->xview('view',$params,'','-password'.$sxpublic);
            }else{
                return 'Acesso negado';
            }
        }
        
        return $this->xview('view',$params,'',$sxpublic);
    }
    
    
     /**
      * Página de lista de posts
      * @param $folder_name - nome da pasta. Se null, quer dizer que a lista é de um post sem post_folder associado
      */
    public function get_viewList(Request $request,$folder_name){
        if($this->post_folder){//existe um post_folder associado
            $postFolder = $this->postFolder($folder_name,'name');
            if(!$postFolder)return 'Post folder não encontrado';
            
        }else{//direto pela classe post sem post_folder
            if($folder_name)return 'Post folder não deve ser informado';
            $postFolder=null;
        }
        
        $taxs_id=$request->input('tx_id');
        
        $posts = PostsService::getList($this->post_type,[
            'post_folder_id'=>$postFolder?$postFolder->id:null,
            'paginate'=>15,
            'taxs'=>$taxs_id
        ],'model');
        
        //verifica se existe apenas 1 registro e neste caso redireciona para a visualização
        if($posts->count()==1){
            return \Redirect::to( $posts->get(0)->getUrl() )->send();
        }
        
        if($postFolder){
            $termsList = $this->getTermsList('view_list',$postFolder->id);
        }else{
            $termsList = $this->getTermsList('view_list');
        }
        
        
        if($termsList && $termsList->count()==0)$termsList=null;
        
        return $this->xview('list-view',[
            'post_type'=>$this->post_type,
            'postFolder'=>$postFolder,
            'posts'=>$posts,
            'thisClass'=>$this,
            'config'=>$this->config(),
            'prefix'=>$this->prefix,
            'title_folder' => $postFolder ? $postFolder->folder_title : $this->getLabel('name'),
            'termsList'=>$termsList,
            'taxs_select'=>$taxs_id,
        ]);
    }
    
    
    /**
     * Página para adicionar um novo post
     */
    public function get_add($post_folder_id){
        if($post_folder_id){
            return $this->get_edit($post_folder_id,null);
        }else{
            return $this->get_edit();
        }
    }
    
    /**
     * Página para atualizar um post
     * @params array - esperados: 0 $post_folder_id e 1 $id ou $id
     */
    public function get_edit(...$params){
        if(count($params)<=1){
            $post_folder_id=null;
            $id=$params[0]??null;
        }else{
            $post_folder_id=$params[0];
            $id=$params[1];
        }
        if(is_array($post_folder_id) || is_array($id))return 'Parâmetro inválido';
        if(!$this->validPostFolderId($post_folder_id))return ['success'=>false,'msg'=>'Acesso negado (folder id)']; 
        
        if($id){
            $post = $this->Post->find($id);
            if(!$post)return 'Registro não encontrado';
        }else{
            $post = null;
        }
        
        $config = $this->config();
        
        return $this->xview('edit',[
            'post_type'=>$this->post_type,
            'post_folder_id'=>$post_folder_id,
            'post'=>$post,
            'prefix'=>$this->prefix,
            'area_name'=>null,
            'area_id'=>null,
            'post_model'=>$this->Post,
            'thisClass'=>$this,
            'config'=>$config,
        ]);
    }
    
    
    /**
     * Salva o registro
     */
    private function save(Request $request,$post_folder_id,$id=null){//se definido o $id, então é atualização
        if(!$this->validPostFolderId($post_folder_id))return ['success'=>false,'msg'=>'Acesso negado (folder id)']; 
        $data = $request->all();
        $config = $this->config();
        $configEdit = $config['edit'];
        $configEditDef = $config['edit_defaults'];
        if($post_folder_id==='null')$post_folder_id=null;
        
        //taxonomias
        $taxs = $configEdit['taxs'] ? TaxsService::getFieldsByPost($data) : ['check'=>[],'uncheck'=>[]];
        
        if($id){
            $post = $this->Post->find($id);
            if(!$post)return ['success'=>false,'msg'=>'Erro ao localizar registro'];
        }else{
            $post = null;
        }
        
        //*** validação ***
            $param1 = [
                'post_type'=>'required|max:20',
                'post_title'=>'max:500',
                'post_name'=>'max:200',
                'post_resume'=>'max:1000',
                'area_name'=>'max:20',
            ];

            //seta os campos obrigatórios de acordo com a configuração
            if(in_array('title',$configEdit['required']))$param1['post_title'].='|required';
            if(in_array('resume',$configEdit['required']))$param1['post_resume'].='|required';
            if(in_array('content',$configEdit['required']))$param1['post_content']='required';
            if(in_array('parent',$configEdit['required']))$param1['post_parent']='required';
            if(in_array('user',$configEdit['required']))$param1['user_id']='required';
            if($configEdit['visibility']){
                $param1['post_visibility']='required';
                if($data['post_visibility']=='s'){
                    if(!$post->post_pass || $data['post_pass'])$param1['post_pass']='required|min:6|max:20';//a senha ainda não foi gravada no db, portanto é obrigatório
                    unset($param1['user_level']);
                }else if($data['post_visibility']=='u'){
                    $param1['user_level']='required';
                    unset($param1['post_pass']);
                }else{
                    unset($param1['post_pass'],$param1['user_level']);
                }
            }
            if($id){//edit
                if($configEdit['name'])$param1['post_name'].='|required';
                if($configEdit['status'])$param1['post_status']='required';
            }
            
            $validade = validator($data, $param1, \App\Utilities\FieldsValidatorUtility::getMessages());
            if($validade->fails())return ['success'=>false,'msg'=>$validade->errors()->messages()];
            if(in_array('title',$configEdit['required']) && $configEdit['content'] && HtmlUtility::isEmpty($data['post_content']))return ['success'=>false,'msg'=>['post_content'=>'Campo obrigatório']];
            
            if($post && $post->post_pass && !$data['post_pass'])unset($data['post_pass']);//retira o campo post_pass, pois não foi informado mas a senha já está gravada no db
            
            foreach($configEdit['required'] as $q){
                if(substr($q,0,4)=='taxs'){
                    $term_id=substr($q,5);
                    if(!($taxs['check'][$term_id]??false))return ['success'=>false,'msg'=>$taxs['term_names'][$term_id] .' inválido'];
                }
            }
            
            
        //dd('passou',$param1);
        
        if($post){//edit
            $data['published_at'].=':00';
            if(!ValidateUtility::isDate($data['published_at']))return ['success'=>false,'msg'=>['published_at'=>'Data incorreta']];
            unset($data['post_folder_id']);
            
        }else{//add
            //seta os valores padrões
            $data = array_merge([
                'post_order'=>'0',
                'post_status'=>'0',         //rascunho
                'post_content_type'=>$configEditDef['content_type'],
                'post_visibility'=>$configEditDef['visibility'],
                'published_at'=>date('Y-m-d H:i:s'),
                'user_id'=>\Auth::user()->id
            ],array_filter($data));//arrayfilter para ignorar a entrada null
            $data['post_folder_id']=$post_folder_id;
        }
        
        //*** post_name ***
        $post_name = $data['post_name']??'';
        if(!$post_name)$post_name = FormatUtility::sanitizeSlug($data['post_title']);
        
        //valida o postname para que seja gerado sem existir outro valor igual dentro do mesmo area_name e post_type
        $m = $this->Post->where(['area_name'=>$data['area_name']??null,'post_type'=>$data['post_type']]);
        if($post)$m->where('id','<>',$id);
        $pn=$post_name;
        while(true){
            if(!$m->where('post_name',$pn)->exists())break;
            $n = explode('-',$pn);
            $n = $n[count($n)-1];
            $n = is_numeric($n) ? (int)$n+1 : 1;
            $pn = $post_name.'-'.$n;
        }
        $data['post_name']=$pn;
        $data['account_id'] = $this->getAccountId();
        //dd('*',$data);
        
        //formata os valores para o db
        $data['published_at']=FormatUtility::convertDate($data['published_at'],true);
        
        //atualiza as urls que possam estar setadas dentro do db
        foreach(['post_resume','post_content'] as $f){
            if(isset($data[$f]))$data[$f] = HTMLUtility::urlDomainToCode($data[$f]);
            
        }
        
        //atualiza no db
        try{
            if($post){//edit
                $r=[
                    'success'=>true,
                    'msg' => 'Registro atualizado',
                    'action'=>'edit',
                    'id'=>$id,
                ];
                $post->update($data);
                
            }else{//add
                $post = $this->Post->create($data);
                $id = $post->id;
                $r=[
                    'success'=>true,
                    'msg' => 'Registro cadastrado',
                    'action'=>'add',
                    'id'=>$id,
                ];
            }
        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        
        if(!$r['success'])return $r;
        
        
        //*** taxonomias ***
        foreach($taxs['check'] as $term_id => $tax_ids){
            foreach($tax_ids as $tx_id){
                TaxsService::addRelation($tx_id, 'posts', $id);//obs: o area_name precisa ter o nome da tabela 'posts'
            }
        }
        foreach($taxs['uncheck'] as $term_id => $tax_ids){
            foreach($tax_ids as $tx_id){
                TaxsService::delRelation(['tax_id'=>$tx_id, 'area_name'=>'posts', 'area_id'=>$id]);//obs: o area_name precisa ter o nome da tabela 'posts'
            }
        }
        
        
        //salva os metaboxs
        $m=$configEdit['metaboxs']??false;
        if($m){
            foreach($m as $mb){
                $method='save_metabox'. studly_case($mb);
                if(method_exists($this,$method)){
                    $r = $this->$method($post,$data);
                    if($r && $r['success']==false)return $r;
                }
            }
        }
        
        return $r;
    }
    
    
    public function store(Request $request){
        return $this->save($request,$request->input('post_folder_id'),null);
    }
    public function post_update(Request $request,$id){
        return $this->save($request,$request->input('post_folder_id'),$id);
    }
    
    
    public function remove(Request $request){
        $data = $request->all();
        $post_folder_id = $data['post_folder_id']??null;
        if(!$this->validPostFolderId($post_folder_id))return ['success'=>false,'msg'=>'Acesso negado (folder id)']; 
        
        if($data['action']=='remove'){
            $id=$data['id'];
            $model = $this->Post->onlyTrashed()->find($id);
            if($model){
                
                if($this->getConfig('files_saved_post')){
                    //*** quer dizer que os diretórios são exclusivo deste post ***
                    $filesConfig = $this->files__config();
                    $Filesystem = new \Illuminate\Filesystem\Filesystem;
                    
                    //remove a relação de arquivos e os arquivos deste post
                    $r=FilesService::removeRelation(['area_name_prefix'=>'posts','area_id'=>$model->id]);
                    if(!$r['success'])return $r;
                    
                    foreach($filesConfig['folders_list'] as $f=>$v){
                        $f.=DIRECTORY_SEPARATOR . $model->id;
                        
                        //remove todos os arquivos deste diretório exclusivo
                        $files = FilesService::getModel()->where('folder',$f)->get();
                        if($files->count()>0){
                            foreach($files as $file){
                                $r=FilesService::removeFile($file);
                                if(!$r['success'])return $r;
                            }
                        }
                        
                        //remove os diretórios exclusivos
                        foreach([true,false] as $acccess){//procura na pasta pública e privada
                            $dir = FilesService::createStoragePath([
                                'private'=>$acccess,
                                'folder'=>$f,
                                'folder_date'=>$filesConfig['folder_date'],
                                'account_id'=>$model->account_id
                            ]);
                            $Filesystem->deleteDirectory($dir['basedir']);
                            if(file_exists($dir['basedir']))return ['success'=>false,'msg'=>'Falha ao remover diretório '.$dir];
                        }
                    }
                }else{
                    //*** remove apenas a relação de arquivos com este post ***
                    FilesService::removeRelation(['file_id'=>$id]);
                }
                //dd('****');
                
                TaxsService::delRelation(['area_name'=>$this->post_type,'area_id'=>$id]);
                $this->PostData->where('post_id',$id)->delete();
                $this->PostHist->where('post_id',$id)->delete();
                $model->forceDelete();//deleta o registro
                //$model->addLog('remove');
            }
            $r=['success'=>true,'msg' => 'Removido com sucesso'];
            
        }else if($data['action']=='restore'){
            $model=$this->Post->onlyTrashed()->find($data['id']);
            $model->restore();
            //$model->addLog('restore');
            $r=['success'=>true,'msg' => 'Registro Restaurado'];
            
        }else if($data['action']=='trash'){
            $model = $this->Post->find($data['id']);
            if($model){
                //verifica se tem registros filhos e limpa este campo
                $this->Post->where('post_parent',$model->id)->update(['post_parent'=>null]);
                
                //envia para a lixeira
                $model->delete();
                //$model->addLog('trash');
            }
            $r=['success'=>true,'msg' => 'Movido para a lixeira'];
        }
        return $r;
    }
    
    
    
    /**
     * Comandos padrões a serem executadas no início de cada função metabox
     * Espera pelo menos o parâmetro $opt['post']
     * @return $opt
     */
    private function metaboxsDefaultHead($opt){
        $opt = array_merge($opt,\Request::all());
        $p = $opt['post']??null;
        if($p && !is_object($p))$p = $this->Post->find($p);
        $opt['post']=$p;
        return $opt;
    }
    
    /**
     * Comandos padrões para o metabox de arquivos
     * @param $opt[files_opt]=array options
     */
    public function metaboxDefaultFiles($opt,$method_name,$area_name,$title){
        extract( $this->metaboxsDefaultHead($opt) );
        if(!$post->id){
            return view('templates.components.metabox',['title'=>$title,'content'=>'<br><span class="text-muted"><i class="fa fa-repeat margin-r-5"></i> Salve o registro para editar</span><br><br>','color'=>false]);
        }
        
        $classname=$this->thisClassName()['short_name'];
        $controller = (new \App\Http\Controllers\FilesController)->callController($classname);
        
        $list_id = 'list_mb_'.str_replace('.','-',$area_name);
        $params=[
            //'files'=>false,
            'controller'=>$controller,
            'files_opt'=>[
                'metabox'=>['color'=>false,'is_border'=>false,'title'=>$title],
                'bt_search'=>false,
                'mode_view'=>false,
                'upload_mode'=>'filemanager',
                'filemanager_opt'=>[
                    'controller'=>$classname,//aqui é informado o nome base da classe, pois estes dados são informados via querystring
                    'multiple'=>true,
                    'folders_show'=>true,
                    //'restrict_user'=>true,
                ],
                'onSelectFile'=>'@function(opt){fileRelation("'. $list_id .'",opt,"'. $area_name .'"); }',
                'folders_show'=>false,//por padrão, nesta listagem a seleção de pasta não é necessária, pois ficará apenas com o filemanager
                //'modeview_img'=>true,
                //'accept'=>'image/*',
                //'filetype'=>'image',
                //'thumbnails'=>false,
                //... demais opções no arquivo files_list.blade
            ],
            'auto_list'=>[
                'list_id'=>$list_id,
                'options'=>[
                    'regs'=>false,
                    'list_remove'=>false,
                    //'allow_trash'=>false
                ],
                'routes'=>[
                    'dblclick'=>function($reg){
                        return ([$reg->getUrl(), 'target'=>'_blank']);
                    },
                    'load'=>route($this->prefix.'.app.get',[$this->post_type,$method_name]) . '?post='.$post->id,
                    'remove'=>route($this->prefix.'.app.get',[$this->post_type,'file_remove_relation']) . '?post='.$post->id.'&area_name='.$area_name,
                ],
                'field_click'=>'file_title',
                
                //... demais opções no arquivo files_list.blade
            ]
        ];
        //dd($params);
        //indica se os arquivos do gerenciador estarão fixados em uma pasta deste post. 
        if($this->getConfig('files_saved_post')){
            $n = $this->getDirPost($post);
            array_set($params, 'files_filter.folder',  $n);                                     //adiciona pasta atual para ser filtrada
            array_set($params, 'files_opt.folders_list',  [$n=>'Pasta atual']);                 //adiciona na lista de pastas
            array_set($params, 'files_opt.filemanager_opt.folders_list', [$n=>'Pasta atual']);  //adiciona na lista de pastas do filemanager
            array_set($params, 'files_opt.filemanager_opt.files_filter.folder', $n);            //adiciona a pasta inicial a ser filtrada no filemanager
            //dd($params);
        }
        
        //captura a lista de arquivos que já deve vir carregada
        $files = FilesService::getList([
            'area_name'=>$area_name,
            'area_id'=>$post->id,
        ],$controller);
        
        $params['files']=$files;
        
        if(isset($opt['files_opt']))$params['files_opt'] = array_merge($params['files_opt'],$opt['files_opt']??[]);
        if(isset($opt['auto_list']))$params['auto_list'] = array_merge_recursive($params['auto_list'],$opt['auto_list']??[]);
        //dd($opt,$params);
        return view('templates.ui.files_list',$params);//obs: esta view files_list já está preparada para processar a requisição ajax
    }
    
    
    /**
     * Retorna ao metabox:imagens para ser carregado dentro da edição do post
     * @param array $opt - parâmetro obrigatório: (int|model) post
     */
    public function get_metaboxImage($opt=[]){
        $opt['files_opt']=[
            'modeview_img'=>true,
            'accept'=>'image/*',
            'filetype'=>'image',
            //'thumbnails'=>false,
        ];
        //$opt['auto_list']=[ 'options'=> ['allow_trash'=>false] ];
        return $this->metaboxDefaultFiles($opt,'metabox_image','posts.image','Imagens');
    }
    
    
    /**
     * Retorna ao metabox:imagens para ser carregado dentro da edição do post
     * @param array $opt - parâmetro obrigatório: (int|model) post
     */
    public function get_metaboxAttach($opt=[]){
        $opt['files_opt']=[
            'modeview_img'=>false,
            'accept'=>'application/pdf',
            'filetype'=>'pdf',
            'list_compact'=>true,
        ];
        return $this->metaboxDefaultFiles($opt,'metabox_attach','posts.attach','Anexos');
    }
    
    
    /**
     * Método padrão para customizar a ação de salvar dos metaboxs do formulário pelo método get_edit().
     * Obs: a criação destes métodos é opcional para os metaboxs. Sintaxe: save_metabox{Name}($post,$data)
     * Obs2: Se tiver algum retorno, irá interromper o processo retornando ao respectivo valor. Parão de retorno: [success:,msg:, ...] (mas é sempre executado após o salvar os dados principais)
     */
    /*public function save_metabox{Name}($post,$data){
        
    }*/
    
    
    /**
     * Controle de versões ou revisão???
     */
    public function get_metaboxVersion($opt=[]){
        return view('templates.components.metabox',['title'=>'Versões','content'=>'Em desenvolvimento...','color'=>false]);
    }
    
    
    /**
     * Adiciona o arquivo selecionado pelo gerenciador de arquivos na tabela files_relations
     * @param $request - esperados: action (add|del|_trash), files(id=>...,), post (id), area_name
     */
    public function post_fileRelation(Request $request){
        $files = $request->input('files');
        $area_name = $request->input('area_name');
        if(empty($files) || empty($area_name))return ['success'=>false,'msg'=>'Erro de parâmetro'];
        
        $post = $this->Post->find($request->input('post'));
        if(!$post)return ['success'=>false,'msg'=>'Erro ao localizar o post'];
        
        foreach($files as $file_id=>$file_opt){
            FilesService::addRelation($file_id,$area_name,$post->id);
        }
        
        return ['success'=>true];
    }
    
    /**
     * Adiciona o arquivo selecionado pelo gerenciador de arquivos na tabela files_relations
     * @param $request - esperados: action (add|del|_trash), files(id=>...,), post (id), area_name
     */
    public function post_fileRemoveRelation(Request $request){
        $id = $request->input('id');
        $area_name = $request->input('area_name');
        if(empty($id) || empty($area_name))return ['success'=>false,'msg'=>'Erro de parâmetro'];
        
        $post = $this->Post->find($request->input('post'));
        if(!$post)return ['success'=>false,'msg'=>'Erro ao localizar o post'];
        
        FilesService::removeRelation(['id'=>$id,'area_name'=>$area_name,'area_id'=>$post->id]);
        
        return ['success'=>true];
    }
    
    
    private $taxsList_cache=[];
    /**
     * Retorna a lista de termos e taxomonias relacionados ao post
     * @param $configName - valores: edit, view
     * @param $post
     * @param $ret - valores: model, array
     * @return null | array - [ term_id =>[ 'term'=>model, taxs_sel=>[ids], taxs=>[tax_id=>model,...] ], ... ]
     */
    public function taxsList($configName,$post,$ret='model',$cache=true){
        $config = $this->config();
        $c = $config[$configName]??null;
        if(!$c)return null;
        
        $cname=$configName.'.'.$post->id.'.'.$ret;
        if(($this->taxsList_cache[$cname]??false) && $cache)return $this->taxsList_cache[$cname];
        
        $taxs = is_array($c['taxs'])?$c['taxs']:$config['taxs'];
        $termsList = $this->getTermsList($configName,$post->post_folder_id);
        if($termsList && $termsList->count()==0)$termsList=null;
        $r=[];
        
        if($termsList){
            foreach($termsList as $term){
                $n = TaxsService::getTaxList($term->id,['merge_list'=>true,'level_space'=>''],'model');
                $r[$term->id] = [
                    'term'=>$ret='model'?$term:$term->term_title,
                    'taxs_sel'=>$post->getTaxsData($term->id,'ids'),
                    'taxs'=>$ret='model'?$n:$n->toArray(),
                ];
            }
        }
        
        $this->taxsList_cache[$cname] = $r;
        return $r;
    }
    
    
    /**
     * Retorna se o post pode ser editado / visualizado considerando as restrições de acesso
     * @param $action - edit|view
     */
    public function allow($post,$action){
        if($action=='edit'){
            return PostsService::allowEdit($post,$this);
        }else{//view
            return PostsService::allowView($post);
        }
    }
    
    
    /**
     * Faz o login para acessar um post restrito
     */
    public function post_postLogin(Request $request){
        $id = $request->input('id');
        $pass = $request->input('post_pass');
        if(empty($pass))return ['success'=>false,'msg'=>['post_pass'=>'Senha inválida']];
        
        $post = $this->Post->find($id);
        if(!$post)return ['success'=>false,'msg'=>['post_pass'=>'Dados inválidos']];
        
        if($post->post_pass!=$pass)return ['success'=>false,'msg'=>['post_pass'=>'Senha incorreta']];
        
        //armazena em sessão os logins, sintaxe array: [id1,id2,..]
        $session = session('posts_login',[]);
        $session[]=$id;
        session(['posts_login' => $session]);
        
        return ['success'=>true,'msg'=>'Senha confirmada'];
    }
    
    
    private $terms_list=null;
    /**
     * Retorna a relação de terms disponívels para este tipo de post
     * @param $action - nome da ação que está solicitando este acesso, valores: null|edit|list|view
     * @return null | 
     */
    public function getTermsList($action=null,$post_folder_id=null){
        if(!$this->terms_list){
            $taxs = $this->getConfig(($action?$action.'.':'').'taxs');
            if(($action && !$taxs) || $taxs===true)$taxs=$this->getConfig('taxs');
            if(!$taxs)return null;
            $taxs = callstr($taxs,[$action,$post_folder_id],true);
            if(is_object($taxs)){
                $n=$taxs;
            }else{
                $n = \App\Models\Term::whereIn('id',$taxs)->orderByRaw("FIELD(id,". join(',',$taxs) .")")->get();
            }
            $this->terms_list=$n;
        }
        return $this->terms_list;
    }
    
    
    private $model_post_folder=null;
    /**
     * Retorna aos dados do Post Folder
     * @param $field - id,name
     */
    public function postFolder($id,$field='id'){
        if(!$this->model_post_folder){
            $this->model_post_folder = $field=='name' ? PostFolder::where('folder_name',$id)->first() : PostFolder::find($id);
        }
        return $this->model_post_folder;
    }
    
    
    /**
     * Retorna ao template configurando os valores da configuração
     * @param $area - valores: edit, list, view
     * @param $opt - parâmetros adicionais da view
     * @param $view - nome da view (opcional). Se informado será apenas considerado este valor
     * @param $sufix_name - complemento no nome da view. Ex: 'password'...
     * @obs - irá sempre procurar as views na seguinte ordem:
         *    1) o nome conforme configurado em em $this->config[$area][template]...
         *    1) depois na pasta post_type.$area
         *    2) depois na pasta posts.$area
     */
    private function xview($area,$opt,$view='',$sufix_name=''){
        if(!$view){
            $view = $this->getConfig('posts.'.$area.'.template');//procura pela configuração personalizada
            if($view){
                $view.=$sufix_name;
            }else{
                $view = 'post_type.'.$this->post_type.'.'.$area.$sufix_name;//procura pelo configuração dentro da pasta post_type
            }
            if(!view()->exists($view))$view = 'posts.posts.'.$area.$sufix_name; //configuração padrão
        }
        return view($view,$opt);
    }
    
    
    /**
     * Valida se esta classe está aceitando um post_folder_id
     */
    private function validPostFolderId($post_folder_id){
        if($post_folder_id==='null')$post_folder_id=null;
        if($this->post_folder){//é obrigatório um $post_folder_id
            return $post_folder_id?true:false;
        }else{//não por existir um $post_folder_id
            return $post_folder_id?false:true;
        }
    }
    
    /**
     * Retorna ao nome base desta classe
     * Ex de 'App\Http\Controllers\PostController' para 'Post'
     */
    public function thisClassName(){
        $c=get_class($this);
        $n=str_replace('\\','/',$c);
        $n=substr($n,strrpos($n,'/')+1);
        $s=snake_case(str_replace('Controller','',$n));//de fooBar para foo_bar
        //dd(['class'=>$c,'name'=>$n,'short_name'=>$s]);
        return ['class'=>$c,'name'=>$n,'short_name'=>$s];
    }
    
    
    /**
     * Retorna aos links deste post para o controller linksController
     */
    public function get_links($q=null){
        $m = $this->Post->whereRaw('1=1');
        if($q)$m->whereSearch('post_title',$q);
        $m = $m->take(10)->get();
        $r=[];
        if($m->count()>0){
            foreach($m as $reg){
                $r[]=[
                    'code'=>'@post:'.$reg->id,
                    'title'=>$reg->post_title . ' <small style="margin-left:20px" class="label nostrong bg-gray text-muted">'. $reg->post_type .'</small>',
                    'url'=>'',
                ];
            }
        }
        return $r;
    }
    
}
