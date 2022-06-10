<?php
namespace App\Http\Controllers\Example;

use Illuminate\Http\Request;

/**
 * Classe de exemplo de Posts para serem usados pelas pastas
 */
class ExamplePostInController extends \App\Http\Controllers\Posts\PostsClass{
    
    public $post_type='example-post-in';
    public $post_folder='example-post-folder';
    
    
    //nova configuração
    public function config($opt=[]){//param $opt required
        
        return parent::config([
            'labels'=>[
                'name'=>'Manuais',
                'singular_name'=>'Manuais',
            ],
            'files_saved_post'=>true,
            'edit'=>[
                'content_params'=>[
                    'type'=>'editorcode',
                    'mention'=>['key'=>'@link2'], //'mention'=>true
                    'theme_dark'=>false,
                    'toolbar'=>[
                        true,
                        ['title'=>'Botão 1','color'=>'link','onclick'=>
                            'awBoxListShow({title:"Campos do sistema",search_onshow:true, ajax:{url:"'. route('super-admin.app.get',['example','mention_list']) .'",once:true}, template:function(data){ return data.title; }}, {pos:this});'
                        ],
                    ]
                ],
            ]
        ]);
    }
    
}
