<?php
namespace App\Http\Controllers\Example;

use Illuminate\Http\Request;

/**
 * Classe de exemplo de Posts
 */
class ExamplePostController extends \App\Http\Controllers\Posts\PostsClass{
    public $post_type='example-post';
    
    //nova configuração
    public function config($opt=[]){//param $opt required
        
        return parent::config([
            'labels'=>[
                'name'=>'Artigos',
                'singular_name'=>'Artigo',
            ],
            'taxs'=>[1,2],
            'edit'=>[
                'resume'=>false,
                //'content'=>false,
                //'metaboxs'=>['image'],
            ],
            'files_saved_post'=>true,
            'view'=>[
                'tax_menu'=>true
            ],
            'view_list'=>[
                'tax_menu'=>true
            ],
        ]);
    }
    
    //Exemplo de nova rota para listagem de posts, ex: /post/meusposts
    public function get_meusposts(Request $request){
        return $this->get_list($request);
    }
}
