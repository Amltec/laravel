<?php
/*


*** Posts ***
Links dos exemplos 
    http://localhost/robo-gc/robo-gc-v03/public/super-admin/example_post_folder/list
    http://localhost/robo-gc/robo-gc-v03/public/super-admin/example_post/list


Sintaxe das rotas / links
    Post
        /{prefix}/{post_type}/list
        /{prefix}/{controller}/add
        /{prefix}/{controller}/edit/{post_id}
        /{prefix}/{controller}/view/{post_name}
        /{prefix}/{controller}/view-list

    PostFolder Posts
        /{prefix}/{post_type}/{folder_id}/list
        /{prefix}/{controller}/{folder_id}/add
        /{prefix}/{controller}/{folder_id}/edit/{post_id}
        /{prefix}/{controller}/view/{folder_name}/{post_name}/
        /{prefix}/{controller}/view-list/{folder_name}



**** Exemplo de Controller: PostsClass.php ****
    //Classe de exemplo de Posts
    namespace App\Http\Controllers;
    use Illuminate\Http\Request;
    
    class PostController extends Classes\Posts\PostsClass{
        public $post_type='post';   //mesmo nomebase da classe

        //nova configuração
        public function config($opt=[]){//param $opt required
            return parent::config([
                'labels'=>[
                    'name'=>'Artigo',
                    'singular_name'=>'Artigo',
                ],
            ]);
        }

        //Exemplo de nova rota para listagem de posts, ex: /post/meusposts
        public function get_meusposts(Request $request){
            return $this->get_list($request);
        }
    }



**** Exemplo de Controller: PostsFolderClass.php ****
    //Classe de exemplo de Posts
    namespace App\Http\Controllers;
    use Illuminate\Http\Request;
    
    class PostFolderOkController extends Classes\Posts\PostsFolderClass{
        public $post_type='post_folder_ok';       //mesmo nomebase da classe
        public $post_type_post='post_ok';         //mesmo valor da classe PostOkController
        
        public function config($opt=[]){//param $opt required
            return parent::config([
                'labels'=>[
                    '_p'=>'o',
                    'name'=>'Manuais',
                    'singular_name'=>'manual',
                ],
            ]);
        }
        ....
    }

    //ex de vinculo a classe de posts
    class PostOkController extends Classes\Posts\PostsClass{
        public $post_type='post_folder_ok';   //mesmo valor da classe PostFolderOkController
        public $post_type_post='post_ok';
    }