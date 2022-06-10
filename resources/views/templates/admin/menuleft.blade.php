<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar" id="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
      {!! $adminClass::menuHeader();  !!}

      <!-- Sidebar Menu -->
      <ul class="sidebar-menu" data-widget="tree">
        <li class="header" xstyle="height:5px;padding:0;">{{Auth::user()->user_name}}</li>
        @php

        //se definido, contém o nome do menu selecionado
        $menuSelected=$adminClass::$menuSelected;


        $url = trim(URL::current(),'/');
        $qsArr = explode('&',$_SERVER['QUERY_STRING']);//armazena um array da querystring

        $levels=[0,0];
        $is_active=false;

        foreach($adminClass->getMenuMain() as $menu_id => $menu){
             if(!empty($menu['sub'])){
                echo'<li class="treeview" data-name="'.$menu_id.'">'.
                     '<a href="#" data-name="'.$menu_id.'"><i class="fa fa-'.array_get($menu,'icon','check').'"></i> <span>'.array_get($menu,'title','Sem título').'</span>'.
                        '<span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>'.
                     '</a>'.
                     '<ul class="treeview-menu">';
                        if(is_callable($menu['sub']))$menu['sub']=call_user_func($menu['sub']);


                        $selected_submenu_id=null;
                        foreach($menu['sub'] as $submenu_id => $submenu){
                            $link = array_get($submenu,'link');if(is_callable($link))$link=call_user_func($link);
                            //tira a barra final após o endereço da página (e antes da querystring)
                            $link=explode('?',$link);
                            $link[0]=trim($link[0],'/');
                            $link=join('?',$link);

                            $menu['sub'][$submenu_id]['__link'] = $link;

                            //verifica se a url completa se encaixa com a querystring
                            $n='';
                            foreach($qsArr as $qs){
                                 $n.= ($n==''?'':'&'). $qs;
                                 //echo dump($link .'='.$url .'?'.$n);
                                 if($link == $url.'?'.$n || $link == $url.'/?'.$n){$selected_submenu_id=$submenu_id;break;}
                            }
                        }

                        $is_active_sub=false;
                        foreach($menu['sub'] as $submenu_id => $submenu){
                            if($submenu['head']??false){
                                echo '<li class="header" style="color:#ffffff55">'.Arr::get($submenu,'title','Head Sem Título') .'</li>';

                            }else{
                                $link = $submenu['__link'];

                                $t=false;
                                if($menuSelected==''){
                                     $sel_slugs = array_get($submenu,'sel_slugs');
                                     if($sel_slugs){
                                         if(!is_array($sel_slugs))$sel_slugs=[$sel_slugs];
                                          foreach($sel_slugs as $ss){
                                              if(!$t)$t=strpos($url,$ss)!==false;
                                          }
                                     }

                                     if($selected_submenu_id)$t = $selected_submenu_id==$submenu_id;

                                     if(!$t && !$is_active_sub){
                                          $t=!$is_active && strpos($url,trim(explode('?',$link)[0],'/'))!==false;
                                     }
                                }else{
                                     if($menu_id.'-'.$submenu_id == $menuSelected){
                                         $t=true;
                                         $is_active=true;
                                     }
                                }

                                if($t)$is_active_sub=true;

                                echo '<li '.
                                     ($t?'class="active"':'').
                                     '><a href="'. (empty($link)?'#':$link) .'" data-name="'. $menu_id.'-'.$submenu_id .'" '. array_get($submenu,'attr') .'><i class="fa fa-caret-right"></i> '.array_get($submenu,'title','#') .'</a>'.
                                '</li>';
                           }
                        }

                echo'</ul>'.
                '</li>';

             }else{
                if(is_callable($menu)){
                        echo callstr($menu);

                }elseif(gettype($menu)=='string'){
                        echo $menu;
                }else{
                        $link = array_get($menu,'link');if(is_callable($link))$link=call_user_func($link);

                        $t=false;
                        if($menuSelected==''){
                            if($levels[0]===0){
                                $t = strpos($link,explode('?',$url)[0])!==false;
                                if($t){
                                    $levels[0]=1;
                                    $is_active=true;
                                }
                            }
                        }else{
                            if($menu_id == $menuSelected){
                                $t=true;
                                $is_active=true;
                            }
                        }

                        if($menu['head']??false){
                            echo '<li class="header">'.Arr::get($menu,'title','Head Sem Título') .'</li>';
                        }else{
                            echo '<li data-name="'.$menu_id.'" '. ($t?'class="active"':'') .' class="'. array_get($menu,'li_class') .'">'.
                                '<a href="'. $link .'" data-name="'.$menu_id.'" '. array_get($menu,'attr') .'>'.
                                    '<i class="fa fa-'. array_get($menu,'icon','check') .'"></i>'.
                                    '<span>'.array_get($menu,'title','Sem título').'</span>'.
                                '</a></li>';
                        }
                }
             }
        }
        @endphp
        </ul>

       {!! $adminClass::menuFooter();  !!}

      <!-- /.sidebar-menu -->
    </section>
    <!-- /.sidebar -->
</aside>
<script>$().ready(function(){ DashboardMenu('active_menu'); });</script>
