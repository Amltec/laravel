<?php

namespace App\Http\Controllers\Setup;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use App\Models\UserAccountRelation;
use App\Services\MetadataService;
use Artisan;
use DB;

use App\ProcessRobot\VarsProcessRobot;
use App\Http\Controllers\Setup\Functions\SetupFunctions;

/**
 * Executa a configuração inicial para funcionamento do sistema no caso de novas instalações.
 * Ações: Cadastra uma conta de teste e um usuário admin para iniciar o sistema.
 */
class SetupController extends Controller{

    function __construct(User $UserModel,UserAccountRelation $UserAccountRelationModel){//Account $AccountModel,
        //$this->accountModel = $AccountModel;
        $this->userModel = $UserModel;
        $this->UserAccountRelation=$UserAccountRelationModel;
        
        if(env('APP_SETUP')!==true)exit('Já instalado. <a href="'.route('login').'">Fazer login</a>');
    }
    
    
    public function index(){
        return view('setup.index');
    }
    
    
    
    public function post(Request $request){
        /*Valores opt1
         *  0 - Nenhuma ação
         *  1 - Recriar todo o banco de dados, deletando todos os registros e arquivos
         *  2 - Resetar toda a tabela e deletar todos os registros e arquivos
         *  (em revisão) 3 - Deletar todos os registros, com exceção da configuração do sistema e usuários +
         *  (em revisão) 4 - Deletar apenas os dados do processo do robô
         * install - Instalar banco de dados (quer dizer que está vazio)
         */
        $debug =  $request->input('debug');
        $cache_clear = $request->input('cache-clear')=='s';
        $opt =  $request->input('opt1');
        
        $r_db = '';
        $r_user = '';
        $r_cache= '';
        $is_producao=false;
        
        if(env('APP_ENV')=='production'){
            $is_producao=true;
            SetupFunctions::updateENV(['APP_ENV'=>'local']);//altera temporariamente para local
        }
        
        
        
        //opções de banco de dados
        if($opt=='1'){//Recria todo o banco de dados, deletando todas as tabelas, registros e arquivos
            Artisan::call('migrate:fresh');
            $r_db = Artisan::output();
           
        }elseif($opt=='2'){//Resetar toda a tabela e deletar todos os registros e arquivos
            Artisan::call('migrate:reset');
            $r_db = Artisan::output();
            
        }elseif($opt=='3'){//Deletar todos os registros, com exceção da configuração do sistema e usuários
            /**** em revisão: depois de deletar, precisa recriar estas tabelas !!!!!
            $this->deleteTables([
                'files','files_relations',
                'brokers','insurers','robots',
                'process_robot_resume','process_robot_data','process_robot',
            ]);
             */
            
        }elseif($opt=='4'){//Deletar apenas os dados do processo do robô
            /**** em revisão: depois de deletar, precisa recriar estas tabelas !!!!!
            $this->deleteTables(['process_robot_resume','process_robot_data','process_robot']);//deleta as tabelas
             */
            
        }elseif($opt=='install'){///instalar banco de dados (quer dizer que está vazio)
            Artisan::call('migrate:fresh');
            $r_db = Artisan::output();
        }
        
        if($opt!='' && $opt!='0'){
            //deleta os arquivos dos processos
            $folder_apolices = VarsProcessRobot::$path_upload . VarsProcessRobot::$folder_upload;
            $this->deleteFolders($folder_apolices);
        }
        
        
        if(in_array($opt, ['1','2','install'])){//foi removido todas as tabelas, portanto cria novamente um usuário padrão
            $r_user = $this->addAccountUser();
            
            //adiciona a configuração incial (metadata config.)
            MetadataService::set('config', 0, 'title', env('APP_NAME'));
            MetadataService::set('config', 0, 'updated_at', time());
        }
        
        
        if($cache_clear){//limpa o cache
            Artisan::call('clear-compiled');$r_cache.=Artisan::output();
            Artisan::call('route:clear');$r_cache.=Artisan::output();
            Artisan::call('view:clear');$r_cache.=Artisan::output();
            Artisan::call('config:clear');$r_cache.=Artisan::output();
            Artisan::call('cache:clear');$r_cache.=Artisan::output();
        }
        
        //atualiza o arquivo de configuração env
        $n=[
            'APP_SETUP' => 'false',//atualiza o arquivo env para informar que já foi atualizado
            'APP_DEBUG' => ($debug=='s'?'true':'false')
        ];
        if($is_producao)$n['APP_ENV']='production';
        SetupFunctions::updateENV($n);
        
        //desconecta
        \Auth::logout();
        
        return 'Configuração finalizada em '.date("Y-m-d H:i:s", time()) .' - <a href="'.route('login').'">Fazer login</a>'.
               (empty($r_user)?'':'<br><strong>User</strong><pre>'.$r_user.'</pre>').
               (empty($r_cache)?'':'<br><strong>Cache</strong><pre>'.$r_cache.'</pre>');
               (empty($r_db)?'':'<br><strong>DB</strong><pre>'.$r_db.'</pre>');
    }
    
    
    
   
    
    /*
     * Deleta as tabelas
     * @param array $arr_tables - nomes des tabelas
     * @return void
     */
    private function deleteTables($arr_tables){
        DB::statement("SET foreign_key_checks=0");
        foreach($arr_tables as $tableName){
            //remove a tabela
            DB::table($tableName)->truncate();
            
            //deleta os respectivos meta dados
            DB::table('metadata')->where('area_name','like', $tableName.'%')->delete();
        }
        DB::statement("SET foreign_key_checks=1");
    }
    
    /*
     * Deleta os subdiretórios do diretório informado
     * Return boolean
     */
    private function deleteFolders($dir,$private=false){
        if($private){
            $p=storage_path($dir);
        }else{//public
            $p=public_path($dir);
        }
        if(file_exists($p)){
            return (new Filesystem)->deleteDirectory($p,true);//,true para não excluir o próprio diretório
        }else{
            return false;
        }
    }
    
    
    /*
     * Adiciona um nova conta e usuário administrativo
     * Retorna a uma string com os dados criados
     */
    private function addAccountUser(){
        /*//verifica se a conta de teste está cadastrada
        $account = $this->accountModel::where('account_login','system')->get();
        if($account->count()==0){
             //adiciona a conta
            $account = $this->accountModel->create([
                'account_name'=>'Conta Principal',
                'account_status'=>'a',
                'account_email'=>'aurelio@aurlinformatica.com.br',
                'account_login'=>'system',
                'account_key'=> md5('aurlweb_'.rand(1000,9999)),
            ]);
            
        }else{
            $account=$account[0];
        }
        */
        
        //verifica se o usuário admin está cadastrado
        $senha = '123456';
        $user = $this->userModel::where('user_level','dev')->get();
        if($user->count()==0){
            //adiciona o usuário
            $user = $this->userModel->create([
                'user_name'=>'Administrador Principal',
                'user_alias'=>'Admin Aurl',
                'user_email'=>'aurelio@aurlinformatica.com.br',
                'user_status'=>'a',
                'user_level'=>'dev',
                'user_pass'=>\Hash::make($senha),
                'area_name'=>'system',
            ]);
        }else{
            $user=$user[0];
            $this->userModel->find($user->id)->update([
                'senha'=>\Hash::make($senha),//regrava a senha
            ]);
        }
        
        //adiciona o relationamento entre o usuário e a conta
        /*if($this->UserAccountRelation->where(['user_id'=>$user->id,'account_id'=>$account->id])->count()==0)
            $this->UserAccountRelation->create(['user_id'=>$user->id,'account_id'=>$account->id]);*/
        
        
        //atualiza o metadado informando que já foi configurado
        MetadataService::set('system', 0, 'setup', 'on');
        
        
        return  'Usuário: '.$user->user_name.' #'.$user->id.'<br>'.
                'E-mail: '.$user->user_email.'<br>'.
                'Senha: '.$senha.' <small>(caso não tenha sido alterada)</small><br>'.
                //'Conta: '.$account->account_name.' #'.$account->id.'<br>'.
                'Configuração concluída em '.date("Y-m-d H:i:s");
    }
}
