<?php

namespace App\Http\Controllers\AdminClass;
use App\ProcessRobot\VarsProcessRobot;


class SuperAdminClass extends BaseClass{
    
    public static function getMenus(){
        $user_logged=\Auth::user();
        
        $menu = [
            'dashboard' => ['title' => 'Painel', 'link' => route('super-admin.index'), 'icon'=>'home'],
        ];
        
        //monta a lista de menus de processos a partir dos nomes registrados no controller ProcessRobotController
        foreach(VarsProcessRobot::$configProcessNames as $name=>$opt){
            $class='\\App\\Http\\Controllers\\Process\\Process'.studly_case($name).'Controller';
            
            if($class::$menu_superadmin===false)continue;
            
            if($class::$menu_superadmin){
                $menu[$name]=[
                    'title' => $class::$menu_superadmin['title'] ?? $opt['title'],
                    'link' => self::formatLink($class::$menu_superadmin['link'] ?? route('admin.app.index','process_'.$name)), 
                    'icon' => 'dot-circle-o'
                ];
                
            }else{
                $menu[$name]=[
                    'title' => $opt['title'].'', 
                    'link' => self::formatLink(route('super-admin.app.index','process_'.$name)), 
                    'icon' => 'dot-circle-o'
                ];
            }
            if($class::$submenus_superadmin){
                $submenus=[];
                if($class::$submenus_superadmin){
                    foreach($class::$submenus_superadmin as $menu_name=>$menu_opt){
                        $link = is_array($menu_opt['link']) ? route('super-admin.app.get',$menu_opt['link']) : $menu_opt['link'];
                        $submenus[$menu_name]=[
                            'title'=>$menu_opt['title'], 'link'=>$link,'icon'=>'files-o', 
                        ];
                    }
                }
                $menu[$name]['sub']=$submenus;
            }
        }
        $menu['files_extract'] = ['title' => 'Extração de Arquivos', 'link' => route('super-admin.app.index','files_extract'),'icon'=>'dot-circle-o'];
        $menu['reports'] = ['title' => 'Relatórios','icon'=>'file-text-o',
            'sub'=>[
                'general' => ['title' => 'Totais de Processamento', 'link' => route('super-admin.app.get',['reports','process-totals'])],
            ]
        ];
        $menu['account'] = ['title' => 'Contas', 'link' => route('super-admin.app.index','accounts'),'icon'=>'star'];
        $menu['insurers'] = ['title' => 'Seguradoras', 'link' => route('super-admin.app.index','insurers'), 'icon'=>'bank','can'=>'dev'];
        $menu['robots'] = ['title' => 'Robôs', 'link' => route('super-admin.app.index','robots'), 'icon'=>'android'];
        
        $menu['config'] = [
            'title' => 'Configurações', 'link'=>'#', 'icon'=>'gear', 'can'=>'admin',
            'sub'=>[
                'general' => ['title' => 'Gerais', 'link' => route('super-admin.app.index','config')],
                'terms' => ['title' => 'Marcadores', 'link' => route('super-admin.app.get',['taxs','select_term'])],
                'file_manager' => ['title' => 'Gerenciador de Arquivos', 'link' => route('super-admin.file','files'), 'icon'=>'fa-fw fa-files-o']
            ],
        ];
        
        if($user_logged->user_level=='dev'){
            $menu['superusers'] = ['title' => 'Super Usuários', 'link' => route('super-admin.app.index','superusers'), 'icon'=>'user-plus'];
        }
        
        $menu['perfil'] = ['title' => 'Perfil', 'icon'=>'user','link'=>route('super-admin.user.perfil')];
        $menu['logs'] = ['title' => 'Logs', 'link' => route('super-admin.app.index','logs') , 'icon'=>'list-ul'];
        $menu['info'] = [
            'title' => 'Informações', 'link'=>'#', 'icon'=>'book',
            'sub'=>[
                'errors' => ['title' => 'Descrição dos Erros', 'link' => route('super-admin.app.view','errors-list')],
                'cad_apolice_busca' => ['title' => 'Busca de Apólices', 'link' => route('super-admin.app.view','cad_apolice-busca')],
            ],
        ];
        $menu['dev'] = [
            'title' => 'Desenvolvedor', 'link' =>'#' ,'can'=>'dev', 'icon'=>'codepen',
            'sub'=>[
                'general' => ['title' => 'Gerais', 'link' => route('super-admin.app.get',['dev','view'])],
                'jobs' => ['title' => 'Fila de Processos', 'link' => route('super-admin.app.index','jobs') ],
            ],
        ];
        
        return $menu;
    }
    
   
    
}
