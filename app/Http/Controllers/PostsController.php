<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Account;
use App\Models\Posts\Post;
use App\Models\Posts\PostData;
use App\Models\Posts\PostHist;
use Config;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\HTMLUtility;
use App\Services\TaxsService;
use App\Services\FilesService;
use App\Services\PostsService;


/*
 * Controller responsável pelo gerenciamento do cadastro de lista de posts.
 * Orientações gerais:
 *      
 */
class PostsController extends Controller{
    
    private $Post,$postData,$PostHist;
    private $prefix;
    private static $config;
    
    public static $post_type='post';    //nome do posttype
    
    public static $post_status=['0'=>'Rascunho','r'=>'Revisão','p'=>'Publicado','a'=>'Arquivado','c'=>'Cancelado'];
    public static $post_content_type=['t'=>'Texto','h'=>'HTML','m'=>'Markdown','b'=>'Pagebuilder'];
    public static $post_visibility=['p'=>'Público','r'=>'Privado','s'=>'Protegido por Senha','u'=>'Restrito por Nível de Acesso'];
    
    
    public function __construct(Post $Post, PostData $PostData, PostHist $PostHist){
        $this->Post = $Post;
        $this->PostData = $PostData;
        $this->PostHist = $PostHist;
        $this->prefix = Config::adminPrefix();
    }
    
    
    
    
    
    /**
     * Carrega a configuração padrão do tipo do post
     * @param $opt - opções a serem mescladas
     */
    public static function config($opt=[]){
        if(!self::$config)
        self::$config=[
            //rótulos de todas as áreas
            'labels'=>[
                'name'=>'Posts',
                'singular_name'=>'Post',
                'add_post'=>'Novo {singular_name}',
                'edit_post'=>'Dados do {singular_name}'
            ],
            
            //edição do post - habilita os recursos - sintaxe: name => (boolean|string|array option)
            'edit'=>[
                'name'=>true,               //postname (slug)
                'status'=>true,
                'visibility'=>true,
                'date'=>true,               //data da publicação
                'remove'=>true,
                'title'=>true,
                'resume'=>true,
                'content'=>true,
                'content_type'=>true,
                'taxs'=>true,               //(boolean ou array term_id) exibe as taxonomias
                'parent'=>true,
                'order'=>true,
                'version'=>true,
                'visibility'=>true,
                'user'=>true,              //usuário
                
                //irá procurar os métodos get_metabox{Name} e save_metabox{Name}
                'metaboxs'=>['image','attach','version'],
                
                //campos requeridos, valores: aceitos: title, content, resume, taxs_{term_id}, parent, user
                'required'=>['title','content'],
            ],
            //opções padrões da edição do post
            'edit_defaults'=>[
                'content_type'=>'t',        //tipo de conteúdo - Valores: t texto, h html, m markdown, b pagebuilder
                'visibility'=>'r',          //visibilidade - valores: p public, r private, s password, u user_level
                //'taxs'=>{term_id},        //caso queira setar um id padrão. Default primeiro índice de allow
            ],
            
            //taxonomias aceitas
            'taxs'=>[2,1],      //ids da tabela term.id permitidos
            
            //opçoes da lista de posts
            'list'=>[
                'taxs'=>true,               //(boolean ou array term_id) exibe as taxonomias
                ... parei aqui...
                'columns'=>[],              //relação de colunas permitidas (visíveis na respectiva ordem)
                'columns_show'=>[],         //relação de colunas visíveis pode padrão
                
                'remove'=>true,
                'edit'=>true,
                'view'=>true,               //link de visualização
                'autolist'=>null,           //array para mesclagem dos parâmetros da view templates.ui.auto_list
                'add'=>true,                //adiciona novo post
            ],
            
            //opçoes da visualização do post
            'view'=>[
                
            ],
        ];
        
        $c=self::$config;
        
        if($opt){
            $c=FormatUtility::array_merge_recursive_distinct($c,$opt);
            //abaixo verifica se alguma das configurações base não ficou vazia e neste caso seta o padrão
            foreach(['labels','edit','edit_defaults'] as $x){
                if(!isset($c[$x]) || is_null($c[$x]))$c[$x]=self::$config[$x];
            }
            self::$config = $c;
        }
        return $c;
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
     * Página de lista de posts
     * @param $request - parâmetros esperados:
     *          area_name, area_id
     */
    public function get_list(Request $request){
        $config = self::config();
        
        $posts = PostsService::getList(self::$post_type,[
            'paginate'=>15,
        ],'model');
        
        return view('posts.list',[
            'post_type'=>self::$post_type,
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
     * Página para adicionar um novo post
     */
    public function get_add(){
        return $this->get_edit(null);
    }
    
    /**
     * Página para atualizar um post
     */
    public function get_edit($id){
        if($id){
            $post = $this->Post->find($id);
            if(!$post)return 'Registro não encontrado';
        }else{
            $post = null;
        }
        
        $config = self::config();
        
        return view('posts.add_edit',[
            'post_type'=>self::$post_type,
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
    public function store(Request $request,$id=null){//se definido o $id, então é atualização
        $data = $request->all();
        $config = self::config();
        $configEdit = $config['edit'];
        $configEditDef = $config['edit_defaults'];
        
        //taxonomias
        $taxs = $configEdit['taxs'] ? TaxsService::getFieldsByPost($data) : ['check'=>[]];
        
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
                    $param1['post_pass']='required|min:6|max:20';
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
            if(in_array('title',$configEdit['required']) && HtmlUtility::isEmpty($data['post_content']))return ['success'=>false,'msg'=>['post_content'=>'Campo obrigatório']];
            
            foreach($configEdit['required'] as $q){
                if(substr($q,0,4)=='taxs'){
                    $term_id=substr($q,5);
                    if(!($taxs['check'][$term_id]??false))return ['success'=>false,'msg'=>$taxs['term_names'][$term_id] .' inválido'];
                }
            }
            
            
        //dd('passou',$param1);
        
        if($id){//edit
            $data['published_at'].=':00';
            if(!ValidateUtility::isDate($data['published_at']))return ['success'=>false,'msg'=>['published_at'=>'Data incorreta']];
            
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
        }
        
        //*** post_name ***
        $post_name = $data['post_name']??'';
        if(!$post_name)$post_name = FormatUtility::sanitizeSlug($data['post_title']);
        
        //valida o postname para que seja gerado sem existir outro valor igual dentro do mesmo area_name e post_type
        $m = $this->Post->where(['area_name'=>$data['area_name']??null,'post_type'=>$data['post_type']]);
        if($id)$m->where('id','<>',$id);
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
        
        //atualiza no db
        try{
            if($id){//edit
                $r=[
                    'success'=>true,
                    'msg' => 'Registro atualizado',
                    'action'=>'edit',
                    'id'=>$id,
                ];
                $post = $this->Post->find($id);
                if(!$post)return ['success'=>false,'msg'=>'Erro ao localizar registro'];
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
                TaxsService::addRelation($tx_id, 'posts', $id);
            }
        }
        foreach($taxs['uncheck'] as $term_id => $tax_ids){
            foreach($tax_ids as $tx_id){
                TaxsService::delRelation(['tax_id'=>$tx_id, 'area_name'=>'posts', 'area_id'=>$id]);
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
    
    public function post_update(Request $request,$id){
        return $this->store($request,$id);
    }
    
    
    public function remove(Request $request){
        $data = $request->all();
        return $r=['success'=>false,'msg' => 'ok, mas falta testar','data'=>$data];
        if($data['action']=='remove'){
            $id=$data['id'];
            $model = $this->Post->onlyTrashed()->find($id);
            if($model){
                TaxsService::delRelation(['area_name'=>'posts','area_id'=>$id]);
                FilesService::removeRelation(['file_id'=>$id]);
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
                $model->delete();//irá mandar para a lixeira
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
        
        
        $list_id = 'list_'.$area_name;
        
        $params=[
            //'files'=>false,
            'files_opt'=>[
                'metabox'=>['color'=>false,'is_border'=>false,'title'=>$title],
                'bt_search'=>false,
                'bt_folder'=>false,
                'mode_view'=>false,
                'upload_mode'=>'filemanager',
                'filemanager_opt'=>[
                    'multiple'=>false,
                ],
                'onSelectFile'=>'@function(opt){fileRelation("'. $list_id .'",opt,"'. $area_name .'");                        console.log("select file ajax",opt); }',
            ],
            'files_filter'=>[
                'area_name'=>$area_name,
                'area_id'=>$post->id,                
            ],
            'auto_list'=>[
                'list_id'=>$list_id,
                'options'=>[
                    'regs'=>false,
                    'list_remove'=>false,
                ],
                'routes'=>[
                    'dblclick'=>function($reg){
                        return [$reg->getUrl(), 'target'=>'_blank'];
                    },
                    'load'=>route($this->prefix.'.app.get',['posts',$method_name]) . '?post='.$post->id,
                    'remove'=>route($this->prefix.'.app.get',['posts','file_remove_relation']) . '?post='.$post->id.'&area_name='.$area_name,
                ],
                'field_click'=>'file_title'
            ]
        ];
        if(isset($opt['files_opt']))$params['files_opt'] = array_merge($params['files_opt'],$opt['files_opt']??[]);
        
        return view('templates.ui.files_list',$params);//obs: esta view files_list já está preparada para processar a requisição ajax
    }
    
    
    /**
     * Retorna ao metabox:imagens para ser carregado dentro da edição do post
     * @param array $opt - parâmetro obrigatório: (int|model) post
     */
    public function get_metaboxImage($opt=[]){
        $opt['files_opt']=[
            'modeview_img'=>true,
        ];
        return $this->metaboxDefaultFiles($opt,'metabox_images','posts.image','Imagens');
    }
    
    
    /**
     * Retorna ao metabox:imagens para ser carregado dentro da edição do post
     * @param array $opt - parâmetro obrigatório: (int|model) post
     */
    public function get_metaboxAttach($opt=[]){
        $opt['files_opt']=[
            'modeview_img'=>false,
        ];
        return $this->metaboxDefaultFiles($opt,'metabox_attach','posts.attach','Anexos');
    }
    
    /**
     * Método padrão para customizar a ação de salvar dos metaboxs do formulário pelo método get_edit().
     * Obs: a criação destes métodos é opcional para os metaboxs. Sintaxe: save_metabox{Name}($post,$data)
     * Obs2: Se tiver algum retorno, irá interromper o processo retornando ao respectivo valor. Parão de retorno: [success:,msg:, ...] (mas é sempre executado após o salvar os dados principais)
     */
    //public function save_metaboxName($post,$data){...}
    
    
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
    
    
    
}