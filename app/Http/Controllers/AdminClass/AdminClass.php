<?php

namespace App\Http\Controllers\AdminClass;
use App\ProcessRobot\VarsProcessRobot;

class AdminClass extends BaseClass{


    public static function getMenus(){
        $user_logged = \Auth::user();
        //dd(\Config::accountData()->data);
        $accountData = \Config::accountData();
        $accountConfig = $accountData->data['config']??null;

        $is_cad_apolice=false;

        $menu=[];
        if(($accountData->data['robot_start']??false)=='off')$menu['robot_start'] ='<li><a href="'. route('admin.app.index',['robot_status']) .'"><i class="fa fa-stop text-red"></i> Robô Pausado</a></li>';
        $menu['dashboard'] = ['title' => 'Painel', 'link' => route('admin.index'), 'icon'=>'home'];


        //monta a lista de menus de processos a partir dos nomes registrados no controller ProcessRobotController
        foreach(VarsProcessRobot::$configProcessNames as $name=>$opt){
            if(($opt['panel_superadmin']??false)==true)continue;//quer dizer que este menu é apenas para ser exibido no superadmin

            if(isset($accountConfig[$name])){
                if($accountConfig[$name]['active']!='s')continue;//se !='s', então quer dizer que o serviço está desativado, portanto não exibe
                if(($accountConfig[$name]['show_cli']??null)=='n')continue;//se =='n', então quer dizer que não deve exibir este recurso para o cliente
            }

            if($name=='cad_apolice'){
                $menu['uploads']=['title'=>'Enviar Arquivos','icon'=>'upload', 'link' => route('admin.app.create',['process-cad-apolice'])];
                $is_cad_apolice=true;
            }

            $class='\\App\\Http\\Controllers\\Process\\Process'.studly_case($name).'Controller';
            //dump($class);
            if($class::$menu_admin===false)continue;
            $process_menu=[];
            if($class::$submenus_admin){
                foreach($class::$submenus_admin as $menu_name=>$menu_opt){
                    $process_menu[$menu_name]=[
                        'title'=>$menu_opt['title'], 'link'=>self::formatLink($menu_opt['link']), 'icon'=>'files-o',
                    ];
                }
            }

            if($class::$submenus_admin!==false && !$process_menu){
                //captura pela relação de status
                foreach($class::$status_to_admin as $st_value=>$st_opt){
                    $process_menu[$st_value]=[
                        'title'=>$st_opt['label'], 'link'=>route('admin.app.get',['process_'.$name,'list','?status='.$st_value]), 'icon'=>'files-o',
                        //'sel_slugs'=>'/admin/processRobot'    //linha de exemplo
                    ];
                }
                if($process_menu)$process_menu=['all'=>['title'=>'Todos', 'link'=>route('admin.app.get',['process_'.$name,'list']), 'icon'=>'files-o']] + $process_menu;
            }

            if($class::$menu_admin){
                $menu[$name]=[
                    'title' => $class::$menu_admin['title'] ?? $opt['title'],
                    'link' => self::formatLink($class::$menu_admin['link'] ?? route('admin.app.index','process_'.$name)),
                    'icon' => 'dot-circle-o','sub'=>$process_menu
                ];
            }else{
                $menu[$name]=[
                    'title' => $opt['title'].'',
                    'link' => self::formatLink($process_menu?'#':route('admin.app.index','process_'.$name)),
                    'icon' => 'dot-circle-o','sub'=>$process_menu
                ];
            }
        }


        //seguradora data - boleto_seg e boleto_quiver
        if(array_get($accountConfig,'seguradora_data.active_boleto_seg')=='s'){
            $class='\\App\\Http\\Controllers\\Process\\ProcessSeguradoraDataController';
            $link = route('admin.app.index','process_seguradora_data/boletos_list');
            /*$process_menu=['all'=>['title'=>'Todos', 'link'=>$link , 'icon'=>'files-o']];
            foreach($class::$status_pr as $st_value=>$st_label){
                $process_menu[$st_value]=['title'=>$st_label, 'link'=>$link .'?status='.$st_value, 'icon'=>'files-o'];
            }*/
            $menu['seguradora_data_boletos']=[
                'title'=>'Boletos Seguradoras',
                'link'=>$link,
                'icon'=>'dot-circle-o'//,'sub'=>$process_menu,
            ];
        }


        //$menu['report'] = ['title' => 'Relatórios', 'link'=>'#', 'icon'=>'files-o', 'can'=>'superadmin'];

        //lógica: como este é um painel 'admin', os níveis 'dev' e 'superadmin' vem logado a partir de um painel 'super-admin', e portanto a edição do perfil não deve ser feita aqui (e sim no painel super-admin)
        if(!in_array($user_logged->user_level,['dev','superadmin'])){
            $menu['perfil'] = ['title' => 'Perfil', 'icon'=>'user','link'=>route('admin.user.perfil')];
        }

        $menu['config'] = [
            'title' => 'Configurações', 'link'=>'#', 'icon'=>'gears', 'can'=>'admin',
            'sub'=>[
                'brokers' => ['title' => 'Corretores', 'link' => route('admin.app.index','brokers')],
                'account_pass' => ['title' => 'Usuários do Quiver', 'link' => route('admin.app.index','account_pass')],
                'cad_apolice' => ['title'=>'Cadastro de Apólices', 'link' => route('admin.app.index','config_cad_apolice'), 'can'=>'superadmin' ],
                'users' => ['title' => 'Usuários do Sistema', 'link' => route('admin.app.index','users'), 'can'=>'admin'],
                'account' => ['title' => 'Gerais', 'link' => route('admin.app.index','account')],
            ],
        ];
        if(!$is_cad_apolice)unset($menu['config']['sub']['cad_apolice']);

        $menu['info'] = [
            'title' => 'Informações', 'link'=>'#', 'icon'=>'book',
            'sub'=>[
                'errors' => ['title' => 'Descrição dos Erros', 'link' => route('admin.app.view','errors-list')],
                'cad_apolice_busca' => ['title' => 'Busca de Apólices', 'link' => route('admin.app.view','cad_apolice-busca')],
                'about' => ['title' => 'Sobre o Sistema', 'link' => route('admin.app.view','info-about')],
            ],
        ];

        $menu['system'] = ['title' => 'Sistema', 'icon'=>'paper-plane','link'=>route('super-admin.index'),'can'=>'superadmin'];

        return $menu;
    }

}
