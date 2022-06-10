<?php

namespace App\Services;

use DB;
use Auth;

/**
 * Classe de serviço de posts
 */
class PostsService{
    
    private static $model=[];
    
    /**
     * Retorna a model
     * @param $model - post, post_data, post_hist, post_folder
     * @return model
     */
    public static function getModel($model='post') {
        if(!isset(self::$model[$model])){
            $c = '\\App\\Models\\Posts\\'.studly_case($model);
            self::$model[$model] = new $c();
        }
        
        return self::$model[$model];
    }
    
    private static $user_logged=null;
    public static function userLogged(){
        if(!self::$user_logged)self::$user_logged=Auth::user();
        return self::$user_logged;
    }
    
    
    /**
     * Retorna a uma lista de posts
     * @param $ret - valores: array|model - se array, o parâmetro $opt paginate será sempre false
     * @param $all - uso interno da função
     * @return array
     */
    public static function getList($post_type,$opt=[],$ret='array',&$all=[]){
        $opt = array_merge([
            'search'=>null,             //search by post_title
            'area_name'=>null,          //
            'area_id'=>null,            //
            'hierarchy'=>false,         //true - retorna a lista em formato hierárquico
            'parent'=>null,             //id do post pai
            'exclude'=>null,            //(int|array) id do post para ingorar
            'level'=>null,              //quantidades níveis que serão exibidos - null = todos
            'paginate'=>false,          //(int) paginação
            'merge_list'=>false,        //true - irá mesclar todos os resultados em um único object /array,  false (default) - irá gerar um campo 'post_sub' para cada subnivel. Obs1: existe apenas para o caso hierarchy=true. Obs2: se true será gerado automaticamente os campos: post_title_original e post_level
            'level_space'=>' —',
            'row_format'=>null,         //função a ser executada para o objecto de cada linha na lista. Recebe o parâmetro model do item da lista. Ex: function($reg){ $reg->field_name=...;}
            'taxs'=>null,               //string|array ids das taxonomias separadas por virgula. Obs: como o id é exclusivo, pode conter de vários termos que será filtrado. Ex: 1,2... ou [1,2,..]
            'is_trash'=>false,          //filtra os registros excluídos
            'filter_visibility'=>false, //indica se deve filtrar a lista por nível de acesso. Ex: se posts.visibility='u' então irá considerar o nível do usuário logado
            'post_folder_id'=>null,     //
            //parâmetros interno
            '_nlevel'=>0,
        ],$opt);
        
        
        if(!$opt['hierarchy'])$opt['merge_list']=false;
        if($ret=='array')$opt['paginate']=false;
        
        $nlevel=$opt['_nlevel'];
        
        $m = self::getModel('post')
                ->selectSlim(true)//seta o nome da tabela posts
                ->where('post_type',$post_type);
        
        if($opt['post_folder_id']){$m->where('post_folder_id',$opt['post_folder_id']);}else{$m->whereNull('post_folder_id');}
        
        if($opt['area_name'])$m->where('area_name',$opt['area_name']);
        if($opt['area_id'])$m->where('area_id',$opt['area_id']);
        if(!$opt['hierarchy'] && $opt['parent'])$m->where('post_parent',$opt['parent']);
        if($opt['exclude']){
            if(is_array($opt['exclude'])){$m->whereNotIn('id',$opt['exclude']);} else{$m->where('id','<>',$opt['exclude']);}
        }
        
        if($opt['search'])$m->whereSearch('post_title',$opt['search']);
        $m=$m->orderBy('post_parent','asc')->orderBy('id','desc');
        
        if($opt['taxs'])$m->whereTax($opt['taxs']);
        
        if($opt['is_trash'])$m->onlyTrashed();
        
        
        //filtra a lista pelo nível de acesso
        if($opt['filter_visibility']){//valores: p Público, r Privado, s Protegido por Senha, u Restrito por Nível de Acesso
            $ul=self::userLogged()->user_level;
            if($ul){//está em um ambiente logado
                if($ul!='dev'){//dev tem todas as permissões
                    $lv_list = [ 'superadmin'=>['superadmin','admin','user'], 'admin'=>['admin','user'], 'user'=>['user'] ][$ul];
                    $m->whereRaw('(post_visibility<>? or (post_visibility=? and user_level in ('. trim(str_repeat('?,',count($lv_list)),',')  .')))', ['u','u', $lv_list ]);
                }
            }else{//ambiente sem login
                $m->whereIn('post_visibility',['p','s']);
            }
        }
            
        
        //finaliza a query
            if($opt['hierarchy']){
                if($opt['parent']){$m->where('post_parent',$opt['parent']);}else{$m->whereNull('post_parent');}//precisa ser a última condição
                $m = $m->get();//não tem paginaçã na query
            }else{
                //dd([$m->toSql(),$m->getBindings()]);
                $m=$opt['paginate'] ? $m->paginate($opt['paginate']) : $m->get();
                if($ret=='model')return $m;//como não tem hierarquia, retorna a model padrão neste caso
            }
        
        
        
        $r=[];
        
        //lógica abaixo: var $all - usado para merge_list=true, $list - usado para merge_list=false
        $list=[];
        $c=$opt['row_format'];
        
        foreach($m as &$reg){
            $space=$nlevel ? str_repeat($opt['level_space'], $nlevel).' ' : '';
            
            if($opt['hierarchy']){
                $reg->post_title_original = $reg->post_title;
                $reg->post_title = $space . $reg->post_title;
                $reg->post_level = $nlevel;
            }
            
            if($c)callstr($c,[$reg],true);
            
            if($opt['merge_list']){
                $all[$reg->id]=$ret=='model' ? $reg : $reg->toArray();   
            }else{
                $list[$reg->id]=$ret=='model' ? $reg : $reg->toArray();
            }
            
            if($opt['hierarchy']){
                if($opt['level']<=$nlevel){
                    $n = self::getList($post_type, array_merge($opt,['parent'=>$reg->id,'_nlevel'=>$nlevel+1]), $ret, $all);
                    
                    if(!$opt['merge_list']){
                        $list[$reg->id]['post_sub']=$n;
                    }
                }
            }
        }
        
        if($opt['merge_list']){
            if($ret=='model' && !$nlevel){//último nível
                $all = (new \App\Utilities\CollectionUtility($all));
                if($opt['paginate'])$all=$all->paginate($opt['paginate']);
            }
            return $all;
        }else{
            return $list;
        }
    }
    
    
    /**
     * Monta o padrão da lista para campos select no padrão: [value=>text,...]
     * @param $post_type,$opt - os mesmos de self::getList()
     * @param $list_field - nome do campo que será exibido como valor da lista.
     * @return array
     */
    public static function getListSelect($post_type,$opt,$list_field='post_title'){
        $opt['merge_list']=true;
        $list = self::getList($post_type,$opt,'model');
        if($list->count()>0){
            $list = $list->pluck($list_field,'id')->toArray();
        }else{
            $list = [];
        }
        return $list;
    }
    
    
    
    /**
     * Encontra o post a partir do post_name. Ex: 'news/home/title-from-post'
     * @param $post_names - (string) name ou name1/name2/..., (array) [name] ou [name1,name,....]
     * @return null | model Post
     */
    public static function findByName($post_type,$post_names,$folder_post_type=null,$area_name=null){
        if(!is_array($post_names))$post_names = explode('/',$post_names);
        
        if($folder_post_type){//está ligado a uma pasta de posts, portanto o primeiro índice de $names deve corresponder ao nome da pasta
            //neste caso, $post_names[0] contém o valor do campo post_folder.folder_name
            $postFolder = self::getModel('post_folder')->where('folder_name',$post_names[0])->first();
            if(!$postFolder)return;
            //retira pois na tabela posts, não está armazenado o nome da pasta
            unset($post_names[0]);
        }
        
        $m = self::getModel('post')->select('posts.*')->where('posts.post_type',$post_type);
        if($area_name)$m->where('posts.area_name',$area_name);
        if(!$post_names)return null;
        $s='';
        $v=[];
        foreach(array_reverse($post_names) as $i => $n){
            $tmp='';
            if($i>0)$tmp.= 'p'.($i-1).'.post_parent = (select p'.$i.'.id from posts p'.$i.' where p'.$i.'.id=p'.($i-1).'.post_parent and ';
            $tmp.= 'p'.$i.'.post_name=? #sql_and# ';
            if($i>0)$tmp.=')';
            $s= strpos($s,'#sql_and#')!==false ? str_replace('#sql_and#','and '.$tmp,$s) : $tmp;
            $v[]=$n;
        }
        $s=str_replace(['#sql_and#','p0.'],['','posts.'],$s);
        
        $m->whereRaw($s,$v);
        //dd($m->toSql(),$m->getBindings());
        return $m->first();
    }
    
    
    private static $post_folder_regs=[];
    /**
     * Retorna ao post_type da pasta
     */
    private static function getPostTypeFolder($post){
        if(!$post->post_folder_id)return null;
        if(!isset(self::$post_folder_regs[$post->post_folder_id])){
            $m = self::$post_folder_regs[$post->post_folder_id] = \App\Models\Posts\PostFolder::find($post->post_folder_id);
        }else{
            $m = self::$post_folder_regs[$post->post_folder_id];
        }
        return $m->post_type ?? null;
    }
    
    /**
     * Monta a url completa a partir dos post_names considerando o campo os posts ascendentes
     * @param int|model $post
     * @param $ret - url , list, slug, null|'all' (array all)
     * @return null | array: list array, slug, url, url_parent, url_private, url_public
     */
    public static function getUrl($post,$ret=null){
        $post = self::gp($post);
        if(!$post)return null;
        
        //$post_folder_slug = self::getPostTypeFolder($post);
        $post_folder = $post->postFolder;
        $post_folder_slug = $post_folder ? $post_folder->folder_name : null;
        
        $prefix = \Config::adminPrefix();
        
        $s= 'SELECT post_name, (@id:=post_parent ) as post_parent, id '.
            'FROM '.
                '(SELECT id, post_name, post_parent FROM posts  ORDER BY post_parent DESC) AS aux_table, (select @id:=?) initialisation '.
            'WHERE id = @id ORDER BY post_parent;';
        $m=DB::select($s,[$post->id]);
        $r=[];//$post->id=>$post->post_name
        if($m){
            foreach($m as $reg){
                $r[$reg->id]=$reg->post_name;
            }
        }
        
        $u = join('/',$r);
        
        if($post_folder_slug){
            $u=$post_folder_slug.'/'.$u;
            $uprivate = route($prefix.'.app.gets',[$post->post_type,'view',$u]);//url para acesso admin
            $upublic = route('site.app.gets',[$post->post_type,'view',$u]);//url para acesso público
        }else{
            $uprivate = route($prefix.'.app.get',[$post->post_type,'view',$u]);//url para acesso admin
            $upublic = route('site.app.get',[$post->post_type,'view',$u]);//url para acesso público
        }
        $url= in_array($prefix,['admin','super-admin']) ? $uprivate : $upublic;
        //dd($url);
        $r =[
            'list'  => $r,
            'slug'  => $u,
            'url'   => $url,
            'url_parent'=>substr($url,0,strlen($url)-strlen($u)),
            'url_private'=>$uprivate,
            'url_public'=>$upublic,
        ];
        
        return !$ret || $ret=='all' ? $r : ($r[$ret]??null);
    }
    
    
    /**
     * Atualiza os dados da configuração do label
     */
    public static function labels($labels,$label_name){
        return str_replace([
                '{_p}',
                '{name}',
                '{singular_name}'
            ],[
                $labels['_p'],
                $labels['name'],
                $labels['singular_name']
            ],
            ($labels[$label_name]??'')
        );
    }
    
    
    
    /**
     * Retorna se o post pode ser editado
     * @return boolean
     */
    public static function allowEdit($post,$classController){
        $post = self::gp($post);
        if(!$post)return false;
        $ul = self::userLogged()->user_level;
        if($ul=='dev')return true;
        
        $allow_level = $classController->getConfig('allow');
        
        if($post->user_id==self::userLogged()->id)return true;  //é o dono do post
        
        if(self::userLogged()->checkAllowLevel($allow_level,true))return true;//tem o mesmo nível de permissão
        
        return false;
    }
    
    /**
     * Retorna se o post pode visualizado considerando as restrições de acesso
     * @return boolean
     */
    public static function allowView($post){
        $post = self::gp($post); 
        if(!$post)return false;
        $ul = self::userLogged()->user_level??null;
        if($ul=='dev')return true;
        
        $pv = $post->post_visibility;
        
        if(!in_array(\Config::adminPrefix(),['admin','super-admin']) && in_array($pv,['r','u']))return false; //não está na área de admin e o acesso é privado
        
        if($pv=='s' && !$ul)return false;
        if($pv=='s' && in_array($ul,['superadmin','admin','user'])){//protegido por senha
            if($post->user_id==self::userLogged()->id){
                return true;//o dono do post é o próprio usuário logado
            }elseif(in_array($post->id, session('posts_login',[]))){//já fez login
                return true;
            }else{
                return false;
            }
        }
        
        if($pv=='u'){//o acesso é por nível de usuário
            if(!self::userLogged()->checkAllowLevel($post->user_level,true))return false;
        }
        
        //liberado ou acesso público
        return true;
    }
    
    
    /**
     * Retorna ao post considerando se a var $post é um objeto ou id
     */
    private static function gp($post){ return is_object($post) ? $post : self::getModel('post')->find($post); }
}
    