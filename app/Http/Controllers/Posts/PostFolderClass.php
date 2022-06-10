<?php

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Posts\PostFolder;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Services\PostsService;
use App\Services\TaxService;
use App\Services\TermService;
use Config;
use Auth;

/*
 * Controller principal responsável pelo gerenciamento do cadastro de lista de posts.
 * Deve ser extendida a partir de um controller com um nome de posttype
 */
class PostFolderClass extends Controller{
    
    protected $PostFolder;
    public $prefix;
    private $config;
    
    
    /**
     * Nome do posttype a ser definido pela classe extendida.
     * É obrigatório ter o nome da classe pai
     * Tamanho máximo 20 caracteres
     */
    public $post_type='';
    
    /**
     * Nome do posttype da classe relacionado (o mesmo valor da extendida de PostClass).
     */
    public $post_type_post='';
    
    
    
    public function __construct(){
        if(!$this->post_type)exit('Posttype não definido');
        if(!$this->post_type_post)exit('Posttypepost não definido');
        $this->PostFolder = new PostFolder;
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
                '_p'=>'a',  //artigo
                'name'=>'Pastas',
                'singular_name'=>'Pasta',
                'add_folder'=>'Nov{_p} {singular_name}',
                'edit_folder'=>'Editar {singular_name}'
            ],
            
            //nível do usuário que tem permissão para gerenciar o post (add,edit,del). Valores: dev,superadmin,admin,user. Caso não defindo default 'superadmin'. Obs: se definir ex user, todos os níveis acima tem acesso.
            'allow'=>'superadmin',
            
            //configurações da classe relacionada de posts (são os mesmos valores de PostClass()->config()
            'post_config'=>[
                'view'=>[
                    'tax_menu'=>true,
                ],
                'view_list'=>[
                    'tax_menu'=>true,
                ]
            ]
        ];
        
        $c=$this->config;
        
        if($opt){
            $c=FormatUtility::array_merge_recursive_distinct($c,$opt,true);
            //abaixo verifica se alguma das configurações base não ficou vazia e neste caso seta o padrão
            foreach(['labels','allow'] as $x){
                if(!isset($c[$x]) || is_null($c[$x]))$c[$x]=$this->config[$x];
            }
            $this->config = $c;
        }
        return $c;
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
     * Página de lista de pastas
     * @param $request - parâmetros esperados:
     *          area_name, is_trash
     */
    public function get_list(Request $request){
        $list = $this->PostFolder->where('post_type',$this->post_type);
        if(_GET('is_trash')=='s')$list->onlyTrashed();
        if(_GET('area_name'))$list->where('area_name',_GET('area_name'));
        $list=$list->orderBy('id','desc')->paginate(15);
        
        return view('templates.pages.page', [
            'title'=>$this->getLabel('name'),
            'toolbar'=>function(){
                echo view('templates.components.button',['title'=> 'Nov'.$this->getLabel('_p').' '.$this->getLabel('singular_name'),'color'=>'primary','href'=>route($this->prefix.'.app.create',$this->post_type)]);
            },
            'content'=> function() use ($list){
                $param = [
                    'data'=>$list,
                    'columns'=>[
                        //'id'=>'ID',
                        'folder_title'=>['Título','value'=>function($v,$reg){ return '<strong>'.$v.'</strong><br><small title="'. html_entity_decode($reg->folder_resume) .'">'. str_limit($reg->folder_resume,100).'</small>'; }],
                        'folder_version'=>'Versão',
                        'status_label'=>'Status',
                        'created_at'=>['Cadastro','value'=>function($v){ return FormatUtility::dateFormat($v,'date'); }],
                        'area_name'=>'Ref.',
                        'Autor'=>['Usuário','value'=>function($v,$reg){ return $reg->user->user_name;}],
                    ],
                    'options'=>[
                        'checkbox'=>true,
                        'select_type'=>2,
                        'pagin'=>true,
                        'confirm_remove'=>true,
                        'toolbar'=>true,
                        'regs'=>false,
                        'search'=>false
                    ],
                    'routes'=>[
                        'click'=>function($reg){return route($this->prefix.'.app.edit',[$this->post_type,$reg->id]) .'?rd='. urlencode(\Request::fullUrl());},
                        'remove'=>route($this->prefix.'.app.remove',$this->post_type),
                    ],
                    //'field_click'=>'folder_title',
                    'row_opt'=>[
                        'class'=>function($reg){return $reg->folder_status=='c'?'row-deleted':'';}
                    ],
                    'metabox'=>true,
                ];
                
                echo view('templates.ui.auto_list',$param);
            },
        ]);
    }
    
    
    /**
     * Página para adicionar uma nova pasta
     */
    public function create(Request $request){
        return $this->edit($request,null);
    }
    
    /**
     * Página para atualizar uma pasta
     */
    public function edit(Request $request,$id){
        if($id){
            $folder = $this->PostFolder->find($id);
            if(!$folder)return 'Registro não encontrado';
        }else{
            $folder = null;
        }
        return view('posts.post_folder.edit',['folder'=>$folder,'thisClass'=>$this]);
    }
    
    
    /**
     * Salva o registro
     */
    public function store(Request $request,$id=null){//se definido o $id, então é atualização
        $data = $request->all();
        
        $userLogged=Auth::user();
        $data = $request->all();
        
        $param1 = [
            'folder_title'=>'required|max:500',
            'folder_resume'=>'max:1000',
            'folder_version'=>'required|max:10',
            'folder_status'=>'required',
            'folder_name'=>'max:50',
        ];
        if($id){//edit
            $param1['folder_name'].='|required';
        }

        $validade = validator($data, $param1, \App\Utilities\FieldsValidatorUtility::getMessages());
        if($validade->fails()){
            return ['success'=>false,'msg'=>$validade->errors()->messages()];
        }
        
        //*** folder_name ***
        $folder_name = $data['folder_name']??'';
        if(!$folder_name)$folder_name = $data['folder_title'];
        $folder_name = FormatUtility::sanitizeSlug($folder_name);
        
        //valida o postname para que seja gerado sem existir outro valor igual dentro do mesmo area_name e post_type
        $m = $this->PostFolder->where(['post_type'=>$this->post_type]);
        if($id)$m->where('id','<>',$id);
        $pn=$folder_name;
        while(true){
            if(!$m->where('folder_name',$pn)->exists())break;
            $n = explode('-',$pn);
            $n = $n[count($n)-1];
            $n = is_numeric($n) ? (int)$n+1 : 1;
            $pn = $folder_name.'-'.$n;
        }
        $data['folder_name']= substr($pn,0,50);//limite no db
        
        
        $action=null;
        try{
            if($id){
                $r=['success'=>true,'msg' => 'Registro atualizado','action'=>'edit','id'=>$id];
                $folder=$this->PostFolder->find($id)->update($data);
                $action='edit';
            }else{//add
                $data['user_id'] = $userLogged->id;
                $data['account_id'] = $this->getAccountId();
                $data['post_type'] = $this->post_type;
                $folder=$this->PostFolder->create($data);
                $id = $folder->id;
                $action='add';
                $r=['success'=>true,'msg' => 'Registro cadastrado','action'=>'add','id'=>$id];
            }
        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        
        if($action=='add'){
            //Adiciona o termo
            TermService::add([
                'term_title'=>'Categorias',
                'term_singular_title'=>'Categoria',
                'term_short_title'=>'Categoria',
                'area_name'=>'post_folder',
                'area_id'=>$id,
            ]);
        }
        
        return $r;
    }
    
    public function post_update(Request $request,$id){
        return $this->store($request,$id);
    }
    
    
    public function remove(Request $request){
        $data = $request->all();
        if($data['action']=='remove'){
            $id=$data['id'];
            $model = $this->PostFolder->onlyTrashed()->find($id);
            if($model){
                //verifica se existem posts relacionados
                if(\App\Models\Posts\Post::where('post_folder_id',$model->id)->exists()){
                    return ['success'=>true,'msg' => 'Não é possível excluir. Motivo: registros relacionados.'];
                }
                
                //deleta as categorias de termos
                $terms = TermService::find(['area_name'=>'post_folder','area_id'=>$model->id]);
                if($terms){
                    foreach($terms as $term){
                        TermService::del($term->id);
                    }
                }
                
                //remove os metadados
                \App\Services\MetadataService::del('post_folder', $model->id);
                
                //deleta o registro
                $model->forceDelete();
                //$model->addLog('remove');
            }
            $r=['success'=>true,'msg' => 'Removido com sucesso'];
            
        }else if($data['action']=='restore'){
            $model=$this->PostFolder->onlyTrashed()->find($data['id']);
            $model->restore();
            //$model->addLog('restore');
            $r=['success'=>true,'msg' => 'Registro Restaurado'];
            
        }else if($data['action']=='trash'){
            //verifica se existem posts relacionados
            if(\App\Models\Posts\Post::where('post_folder_id',$model->id)->exists()){
                return ['success'=>true,'msg' => 'Não é possível excluir. Motivo: registros relacionados.'];
            }
            
            $model = $this->PostFolder->find($data['id']);
            if($model){
                //envia para a lixeira
                $model->delete();
                //$model->addLog('trash');
            }
            $r=['success'=>true,'msg' => 'Movido para a lixeira'];
        }
        return $r;
    }
    
    /**
     * Retorna a todos os termos cadastrados para esta pasta
     * @return model || array ids
     */
    public function getTermsIds($folder_id,$ret_ids=false){
        $terms=TermService::find(['area_name'=>'post_folder','area_id'=>$folder_id]);
        if($terms){
            if($ret_ids){
                return $terms->pluck('id')->toArray();
            }else{
                return $terms;
            }
        }else{
            return null;
        }
    }
    
    
    /**
     * Adiciona um novo termo da taxonomia
     */
    public function post_addTerm(Request $request){
        $id = $request->input('id');
        TermService::add([
            'term_title'=>'Categorias',
            'term_singular_title'=>'Categoria',
            'term_short_title'=>'Categoria',
            'area_name'=>'post_folder',
            'area_id'=>$id,
        ]);
        return ['success'=>true];
    }
    
    /**
     * Retorna as urls das publicações
     */
    public function getPostUrls($folder){
        if($folder && $folder->id){
            $r=[
                'list'=>route($this->prefix.'.app.gets',[$this->post_type_post,$folder->id,'list']),
                'add'=>route($this->prefix.'.app.gets',[$this->post_type_post,$folder->id,'add']),
            ];
        }else{
            $r=['list'=>null,'add'=>null];
        }
        return $r;
    }
    
    
}