<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\App;
use Exception;

/**
 * Carregamento de controllers dinâmicos a partir de qualquer nome de rota informada $routename
 * A classe da rota de estar dentro da pasta \App\Http\Controllers
 * Obs: todas estas rotas são autenticadas
 */
class AppController extends Controller {
    protected $folder_app='';
    
    public function index($routename){
       return $this->call($routename,'index');
    }
    public function create($routename) {
       return $this->call($routename,'create');
    }
    public function store($routename){
        return $this->call($routename,'store');
    }
    public function show($routename,$id){
        return $this->call($routename,'show',$id);
    }
    public function edit($routename,$id){
        return $this->call($routename,'edit',$id);
    }
    public function update($routename,$id){
        return $this->call($routename,'update',$id);
    }
    public function remove($routename){
        return $this->call($routename,'remove');
    }
    //post geral, ex de chamada de rota: route('admin.app.post',['robot','generateKey']),
    public function post($routename,$methodname,$param=null){ 
        return $this->call($routename,'post_'.$methodname,$param,true);
    }
    //post geral, ex de chamada de rota: route('admin.app.post',['robot','generateKey']),
    /*public function posts($routename,$methodname,...$params){ 
        return $this->call($routename,'post_'.$methodname,$params,true);
    }*/
    //post geral, ex de chamada de rota: route('admin.app.get',['robot','generateKey']),
    public function get($routename,$methodname,$param=null){
        //dd($routename,$methodname,$param);
        return $this->call($routename,'get_'.$methodname,$param,true);
    }
    //post geral, ex de chamada de rota: route('admin.app.get',['robot','generateKey']),
    public function gets($routename,$methodname,...$params){
        return $this->call($routename,'get_'.$methodname,$params,true);
    }
    //acesso geral a uma view, ex de chamada de rota: route('admin.app.view','viewname'),
    //$viewname - pode vir com barra, ex: a/b/c - equivale a a.b.c.blade
    public function view($viewname){
        $route_prefix = \Config::adminPrefix();
        return view($route_prefix.'.view.'.str_replace('/','.',$viewname));
    }
    
    
    private function call($class,$method,$param=null,$isGetPost=false){
        //verifica e captura um parent_id na url (sintaxe esperada /{class}{parent_id}/method/...)
        $id_parent=null;
        $n=str_replace(['get_','post_'],'',$method);
        if($param && is_numeric($n)){
            //se o método for um número e tiver pelo menos um parâmetro seguinte, quer dizer que a url está montada assim: /class/{parent_id}/method/...
            if(!is_array($param))$param=[$param];
            $method=explode('_',$method)[0] .'_'. $param[0];
            unset($param[0]);
            $id_parent=$n;
        }
        //dd($id_parent,$class,$method,$param);
        $route_prefix = \Config::adminPrefix();
        
        //A classe da rota de estar dentro da pasta \App\Http\Controllers
        try {
            $class=str_replace('-','_',kebab_case($class));//troca '-' por '_' para rodar direito o código abaixo e de 'fooBar' para 'foo-bar'
            if(substr($class,0,1)=='_')$class=preg_replace('/_/','#UNDER#',$class,1);//ajuste para o primeiro caractere ficar com '_'
            
            $n=explode('_',$class);
            $folder1=ucwords($n[0]);
            $folder2=ucwords($n[1]??'');
            
            $class=str_replace(' ','', ucwords(str_replace(['-','_'],[' ',' '],$class)));//altera de 'class-name' para 'ClassName'
            $class=str_replace('#UNDER#','_',$class);
            
            //ajusta de ex: get_my_func para get_myFunc
            if($isGetPost){
                $n=explode('_',$method);
                if($n[0]=='get' || $n[0]=='post'){
                    $a=$n[0];
                    unset($n[0]);
                    $method = $a.'_'.camel_case(join('_',$n));//de foo_bar para fooBar
                }
            }
            $b=base_path();
            $a='\\App';
            $p='\\Http\\Controllers\\'. ($this->folder_app?$this->folder_app.'\\':'') ;
            $f=$class.'Controller';
            //dd($f);
            //verifica se existe o arquivo dentro da subpasta em \App\Http\Controllers\...
            //e caso não encontre procura na pasta do $route_prefix
            foreach([studly_case($route_prefix).'\\',''] as $x0){//faz um loop nas pastas base internas para depois procurar na raiz do controller (ex primeiro /controller/super-admin e depois /controller)
                $class='';
                if(file_exists(fpath($b.strtolower($a).$p.$x0.$folder1.'\\'.$f.'.php'))){//existe dentro da pasta (ex UsersController = \Controllers\Users\UsersController.php)
                    $class=$a.$p.$x0.$folder1.'\\'.$f.'@'.$method;
                }else if(file_exists(fpath($b.strtolower($a).$p.$folder1.$x0.'\\'.$folder2.'\\'.$f.'.php'))){//existe dentro da pasta com nome composto (ex UsersTopController = \Controllers\UsersTop\UsersTopController.php)
                    $class=$a.$p.$folder1.'\\'.$folder2.$x0.'\\'.$f.'@'.$method;
                //}else{//procura direto do diretório app (ex UsersController = \Controllers\UsersController.php)
                }else if(file_exists(fpath($b.strtolower($a).$p.$x0.$f.'.php'))){//procura direto do diretório app (ex UsersController = \Controllers\UsersController.php)
                    $class=$a.$p.$x0.$f.'@'.$method;
                }
                if($class)break;
            }
            $param=$id_parent?['_parent_id'=>$id_parent] + $param:[$param];
            //dd([studly_case($route_prefix), $class,$param, \Config::adminPrefix()]);
            if($class){
                return App::call($class,$param);
            }else{
                $user=\Auth::user();
                if($user && $user->user_level=='dev'){
                    dd('Classe ou método não encontrado',$class,$param);
                }else{
                    return ['success'=>false,'msg'=>'Caminho não encontrado'];
                }
            }
            
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if(stripos($msg,'does not exist')!==false){
                $msg='Controler ou método não existe';
            }
            $user=\Auth::user();
            if($user && $user->user_level=='dev'){
                dd($e);
            }else{
                return ['success'=>false,'msg'=>$msg];
            }
        }
    }
   
    
}
