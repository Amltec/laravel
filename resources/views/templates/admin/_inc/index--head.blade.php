    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
   
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="{{asset('AdminLTE-2.4.5/bower_components/bootstrap/dist/css/bootstrap.min.css')}}">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{asset('AdminLTE-2.4.5/bower_components/font-awesome/css/font-awesome.min.css')}}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="{{asset('AdminLTE-2.4.5/bower_components/Ionicons/css/ionicons.min.css')}}">
    
    
    
    <!-- jQuery 3 -->
    <script src="{{asset('AdminLTE-2.4.5/bower_components/jquery/dist/jquery.min.js')}}"></script>
    <!-- Bootstrap 3.3.7 -->
    <script src="{{asset('AdminLTE-2.4.5/bower_components/bootstrap/dist/js/bootstrap.min.js')}}"></script>
    <!-- AdminLTE App -->
    <script src="{{asset('AdminLTE-2.4.5/dist/js/adminlte.min.js')}}"></script>
    
    <!-- Custom Aurl -->
    <script src="{{asset('js/src/main.js?ver=1.114')}}"></script>
    <script src="{{asset('js/src/admin.js?ver=1.114')}}"></script>
    
    @stack('head')
    @stack('head_once')
    {!! Form::writeScripts();!!}
    
    <!-- Theme style -->
    <link rel="stylesheet" href="{{asset('AdminLTE-2.4.5/dist/css/AdminLTE.min.css')}}">
    
    <!-- Custom Aurl -->
    <link rel="stylesheet" href="{{asset('css/src/main.css?ver=1.113')}}">
    <link rel="stylesheet" href="{{asset('css/src/admin.css?ver=1.113')}}">
    <link rel="stylesheet" href="{{asset('AdminLTE-2.4.5/dist/css/skins/skin-black.min.css')}}">
    
    
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
    <script>
    var admin_vars={
        token:'{{csrf_token()}}',
        url:'{{URL::to("/")}}',
        url_current:'{{URL::current()}}',
        url_get:'{{URL::to("/get")}}',
        url_app:'{{URL::to("/".$adminClass->prefix)}}',
        querystring:'{!!$_SERVER['QUERY_STRING']!!}',
        max_size:'{{ini_get('upload_max_filesize')}}',
        max_size_bytes:'{{\App\Utilities\FormatUtility::bytesVal(ini_get('upload_max_filesize'))}}',
        route_tax_post:'{{route($prefix.'.taxonomy.post')}}',
        route_file_modal:'{{route($prefix.'.file.getmodal','@controller')}}',
        prefix:'{{$prefix}}',
        account_id:'{{Config::accountID()}}',
        account_login:'{{Config::isSuperAdminPrefix()?'superadmin':Config::accountPrefix()}}',
        login_ctrl:'{{Cookie::get('login_ctrl')}}',
    };
    </script>
