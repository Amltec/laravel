<?php
namespace App\Http\Controllers\Example;

use Illuminate\Http\Request;

/**
 * Classe de exemplo de Pasta de Posts
 * Url de Acesso /public/super-admin/postexample_folder/
 */
class ExamplePostFolderController extends \App\Http\Controllers\Posts\PostFolderClass{
    
    public $post_type='example-post-folder';
    public $post_type_post='example-post-in';
    
    //nova configuração
    public function config($opt=[]){//param $opt required
        
        return parent::config([
            'labels'=>[
                '_p'=>'o',
                'name'=>'Manuais',
                'singular_name'=>'manual',
            ],
            'post_config'=>[
                'taxs'=>function($action,$folder){ return $this->getTermsIds($folder); },   //será chamado em ExemplePostController()->getTermsList()
            ]
        ]);
    }
    
}
