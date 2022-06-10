<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/


/*
 * Criação de rotas dinâmcias
 * @param $prefix - valores: admin, super-admin, site, etc
 * @param $controller = nome do controler, padrão: 'AppController'
 */
function thisDynamicRoutes($prefix='',$controller='AppController'){
    //**** rotas dinâmicas para qualquer uso (posta {pagename} para um controller padrão, que chama o respectivo controller) *****
    //rota para carregar diretamente a view, ex: route('admin.app.post',['processrobot','admin.app.view',['dir/dir2/..../view']]
    Route::get('/view/{any}',['uses'=>$controller.'@view','as'=>$prefix.'.app.view'])->where('any', '.*');
    //rotas resource
    Route::get('{pagename}',['uses'=>$controller.'@index','as'=>$prefix.'.app.index']);
    Route::get('{pagename}/create',['uses'=>$controller.'@create','as'=>$prefix.'.app.create']);
    Route::post('{pagename}/store',['uses'=>$controller.'@store','as'=>$prefix.'.app.store']);
    Route::get('{pagename}/{id}/show/',['uses'=>$controller.'@show','as'=>$prefix.'.app.show']);
    Route::get('{pagename}/{id}/edit',['uses'=>$controller.'@edit','as'=>$prefix.'.app.edit']);
    Route::put('{pagename}/{id}/update',['uses'=>$controller.'@update','as'=>$prefix.'.app.update']);
    Route::delete('{pagename}/remove',['uses'=>$controller.'@remove','as'=>$prefix.'.app.remove']);
    
    //rota de post dinâmico geral, ex de uso route('admin.app.post',['processrobot','upload'])   //vai para o /App/ProcessRobotController::(post|get)_upload
    Route::post('{pagename}/{methodname}/{arg1?}',['uses'=>$controller.'@post','as'=>$prefix.'.app.post']);
    Route::put('{pagename}/{methodname}/{arg1?}',['uses'=>$controller.'@post','as'=>$prefix.'.app.post']);
    Route::delete('{pagename}/{methodname}/{arg1?}',['uses'=>$controller.'@post','as'=>$prefix.'.app.post']);
    Route::get('{pagename}/{methodname}/{arg1?}',['uses'=>$controller.'@get','as'=>$prefix.'.app.get']);
}

//Criação de rotas padrões para uma área segura
function thisDefaultAdminRoutes($prefix=''){//$prefix = admin, super-admin
    //dashboard 
    Route::get('/',['uses'=>'AdminController@index','as'=>$prefix.'.index']);
    
    //usuário logado
    Route::get('user/perfil',['uses'=>'UsersController@perfilEdit','as'=>$prefix.'.user.perfil']);
    
    
    //fiemanager
    Route::get('/filemanager',['uses'=>'FilesController@index','as'=>$prefix.'.file']);//index
    Route::get('/filemanager/modal',['uses'=>'FilesController@indexModal','as'=>$prefix.'.file.getmodal']);//lista de arquivos dentro da uma janela modal
    Route::get('/filemanager/view/{file_id}/{filename?}',['uses'=>'FilesController@view','as'=>$prefix.'.file.view']);//visualização do arquivo e informações
    Route::post('/filemanager/getdata/',['uses'=>'FilesController@getData','as'=>$prefix.'.file.getdata']);//visualização do arquivo e informações
    Route::delete('/filemanager/post/remove',['uses'=>'FilesController@post','as'=>$prefix.'.file.remove']);//post automático com o método delete
    Route::post('/filemanager/post',['uses'=>'FilesController@post','as'=>$prefix.'.file.post']);//post automático
    //*** file direct ***
    Route::post('/filemanager/postdirect',['uses'=>'FilesController@postDirect','as'=>$prefix.'.file.postdirect']);//post automático
    Route::delete('/filemanager/postdirect',['uses'=>'FilesController@postDirect','as'=>$prefix.'.file.postdirect']);//post automático com o método delete
    
    
    //taxonomias - post automático pelo templates.ui.tax_form
    Route::post('/taxonomy/post',['uses'=>'TaxsController@post','as'=>$prefix.'.taxonomy.post']);
    
    //config: taxonomias
    Route::resource('terms/{term_id}/taxonomy','TaxsController',['as'=>$prefix]);
    
    
    //cria as rotas dinâmicas
    thisDynamicRoutes($prefix);
}

$account_login = Config::accountPrefix();

//setup
Route::get('setup','SetupController@index');
Route::post('setup-post',['uses'=>'SetupController@post','as'=>'setup-post']);
Route::get('setup-update','Setup\SetupUpdateController@index');

//Queue
Route::get('queue/{action}','QueueController@execute');


//*** acesso direto pelo arquivo sem passar pelo admin ***
//Filemanager - carregar arquivo
Route::get('/app/filemanager/fileload/{id}/{thumbnail}/{filename?}/',['uses'=>'FilesController@load','as'=>'app.file.load']);
Route::get('/app/filemanager/filedirectload/{data_serialize}/',['uses'=>'FilesController@loadDirect','as'=>'app.filedirect.load']);

//retorna a dados gerais via ajax
Route::post('get/data',['uses'=>'GetDataController@getData','as'=>'get_data']);//para informações de webservice
Route::get('get/token',['uses'=>'GetDataController@getToken','as'=>'get_token','middleware'=>'auth.app']);//para capturar o token atualizado //rota autenticada

//login
Route::get('login',['uses'=>'Site\LoginController@login', 'as'=>'login']);
Route::post('login',['uses'=>'Site\LoginController@auth','as'=>'postlogin','middleware'=>'throttle:30,1']);
Route::get('{account_name}/login',['uses'=>'Site\LoginController@login', 'as'=>'account_login']);
Route::post('{account_name}/login',['uses'=>'Site\LoginController@auth','as'=>'account_postlogin','middleware'=>'throttle:30,1']);
Route::get('logout',['uses'=>'LoginController@logout','as'=>'logout']);
//login ajax
Route::get('login-ajax',['uses'=>'LoginController@loginAjax','as'=>'login.ajax']);
//redirect to route /admin to /{account_login/admin
Route::get('admin','Site\LoginController@rdFromUrlAdmin');


//Solicitações de dados para o App do Robô processo
Route::get('wsrobot/data',['uses'=>'WSRobotController@data','as'=>'wsrobot.data','middleware'=>'auth']);//via get precisa ser autenticado
Route::post('wsrobot/data',['uses'=>'WSRobotController@data','as'=>'wsrobot.data']);
//Solicitações de dados para o App do Robô de extração de arquivos
Route::get('wsfilesextract/data',['uses'=>'WSFileExtractTextController@data','as'=>'wsfilesextract.data','middleware'=>'auth']);//via get precisa ser autenticado
Route::post('wsfilesextract/data',['uses'=>'WSFileExtractTextController@data','as'=>'wsfilesextract.data']);


//Leitura do arquivo por rota não autenticada, mas de modo seguro
Route::get('process_{process_name}/robot_file_load/{data_serialize}/{filename}',['uses'=>'Site\SiteController@robotFileLoad','as'=>'process_robot_fileload']);



//*** superadmin - painel geral - rotas são autenticadas 
Route::group(['prefix'=>'super-admin','middleware'=>'super.app'],function(){
    
    //config: grupo de marcadores (terms)
    Route::resource('terms','SuperAdmin\TermsController',['as'=>'super-admin']);

    //cria as rotas dinâmicas
    thisDefaultAdminRoutes('super-admin');
});



//*** admin - painel do cliente - rotas são autenticadas ***
//rota da página inicial do painel informando o parâmetro {account_login}
Route::get('{account_login}/admin',['uses'=>'AdminController@index','as'=>'admin.index_account','middleware'=>'auth.app']);
//grupo de rotas
Route::group(['prefix'=>$account_login.'/admin','middleware'=>'auth.app'],function(){
    //cria as rotas dinâmicas
    thisDefaultAdminRoutes('admin');
});



//rota dinâmica fora do admin para post dinâmico geral, ex de uso route('site.post',['login','password'])   //vai para o /Site/LoginController::(post|get)_password
//obs: precisa ter o prfeixo /site/ para que não quebra a segurança das rotas de /admin/
thisDynamicRoutes('site','Site\SiteController');


//site
//ainda não desenvolvido
//Route::get('/', function (){return view('welcome');});
Route::get('/','Site\LoginController@rdFromUrlAdmin');
