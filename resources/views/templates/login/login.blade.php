<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>@yield('title')</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="{{asset('AdminLTE-2.4.5/bower_components/bootstrap/dist/css/bootstrap.min.css')}}">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{asset('AdminLTE-2.4.5/bower_components/font-awesome/css/font-awesome.min.css')}}">
  <!-- Ionicons -->
  <link rel="stylesheet" href="{{asset('AdminLTE-2.4.5/bower_components/Ionicons/css/ionicons.min.css')}}">
  
  @stack('head')
  
  <!-- Theme style -->
  <link rel="stylesheet" href="{{asset('AdminLTE-2.4.5/dist/css/AdminLTE.min.css')}}">
  <!-- iCheck -->
  <link rel="stylesheet" href="{{asset('AdminLTE-2.4.5/plugins/iCheck/square/blue.css')}}">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
  
  
  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  <style>
      body{height:auto;}
      .login-page,.register-page{
            background: rgba(49,62,63,1);
            background: -moz-radial-gradient(center, ellipse cover, rgba(49,62,63,1) 0%, rgba(49,62,63,1) 16%, rgba(0,0,0,1) 100%);
            background: -webkit-gradient(radial, center center, 0px, center center, 100%, color-stop(0%, rgba(49,62,63,1)), color-stop(16%, rgba(49,62,63,1)), color-stop(100%, rgba(0,0,0,1)));
            background: -webkit-radial-gradient(center, ellipse cover, rgba(49,62,63,1) 0%, rgba(49,62,63,1) 16%, rgba(0,0,0,1) 100%);
            background: -o-radial-gradient(center, ellipse cover, rgba(49,62,63,1) 0%, rgba(49,62,63,1) 16%, rgba(0,0,0,1) 100%);
            background: -ms-radial-gradient(center, ellipse cover, rgba(49,62,63,1) 0%, rgba(49,62,63,1) 16%, rgba(0,0,0,1) 100%);
            background: radial-gradient(ellipse at center, rgba(49,62,63,1) 0%, rgba(49,62,63,1) 16%, rgba(0,0,0,1) 100%);
            filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#313e3f', endColorstr='#000000', GradientType=1 );
      }
      .login-box{margin:0;position:absolute;top: 50%;left: 50%;transform: translate(-50%, -50%);}
      .login-box-body{padding:30px 30px;margin-top:-100px;border-radius:5px;box-shadow:0px 0px 15px rgba(0,0,0,0.3);}
      .login-logo-img img{max-width:300px;max-height:300px;}
  </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-box-body">
    <div class="login-logo">
      @yield('logo')
    </div>
    <br>
    @yield('content')
    </div>
    
</div>
<!-- /.login-box -->

<!-- jQuery 3 -->
<script src="{{asset('AdminLTE-2.4.5/bower_components/jquery/dist/jquery.min.js')}}"></script>
<!-- Bootstrap 3.3.7 -->
<script src="{{asset('AdminLTE-2.4.5/bower_components/bootstrap/dist/js/bootstrap.min.js')}}"></script>

@stack('bottom')
</body>
</html>
