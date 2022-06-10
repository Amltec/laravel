    <header class="main-header">
        <a href="{{route($adminClass->prefix.'.index')}}" class="logo" {!! !$logo_icon?'style="padding-left:10px;"':'' !!}>
          {!! $logo_icon ? '<span class="logo-icon"><img src="'. $logo_icon.'?'. $ctrl_updated_at . '" /></span>' : '' !!}
          <span class="logo-mini">{{$account_name}}</span>
          <span class="logo-lg">{{$account_name}}</span>
        </a>

        @if($dashboard['navbar'])
        <nav class="navbar navbar-static-top" role="navigation">
          <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button" id="header-push-menu">
            <span class="sr-only">Toggle navigation</span>
          </a>
                <div class="navbar-custom-menu">
                  <ul class="nav navbar-nav">
                  <li class="dropdown notifications-menu hiddenx" id="notifications-menu"></li>
                  <li class="dropdown user user-menu">
                          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-user"></i>
                            {{--<span class="hidden-xs">{{$userLogged->user_name}}</span>--}}
                          </a>



                        <ul class="dropdown-menu nav nav-stacked">
                          <li class="user-header" style="height:auto;">
                          @php
                          if( $adminClass->prefix=='super-admin' && in_array($userLogged->user_level,['dev','superadmin']) ){//se true, está na página do superadmin
                              echo '<p>'. 
                                      $userLogged->user_name .'<br><small>'. $userLogged->level_name.'</small>'.
                                      '<small style="font-weight:600;">Gerenciamento do Sistema</small>'.
                                   '</p>';
                          }else{
                              echo '<p>'.
                                      $userLogged->user_name .'<br><small>'. $userLogged->level_name.'</small>'.
                                      '<small style="font-weight:600;">'. $userLogged->getAuthAccount()->account_name .'</small>'.
                                   '</p>';
                          }
                          @endphp
                          </li>
                          @php

                          if($adminClass->prefix=='super-admin' || ($adminClass->prefix=='admin' && !in_array($userLogged->user_level,['dev','superadmin']))){
                              echo '<li><a style="border-bottom:1px solid #e2e2e2;color:#333;" href="'. route($adminClass->prefix.'.user.perfil') .'">Editar Perfil</a></li>';
                          }

                          if($adminClass->prefix=='admin' && in_array($userLogged->user_level,['dev','superadmin']) ){
                              echo '<li><a style="border-bottom:1px solid #e2e2e2;color:#333;" href="'. route('super-admin.index') .'">Sistema</a></li>';
                          }

                          @endphp
                          <li><a style="border-bottom:1px solid #e2e2e2;color:#333;" href="{{route('logout')}}">Sair</a></li>
                        </ul>
                  </li>
                  </ul>
                </div>
        </nav>
        @endif
    </header>