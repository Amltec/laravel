<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;


class AdminController extends Controller{
    public $prefix='';//para palavras compostas usar '-', ex: super-admin
    
    function __construct(){
        $this->prefix = \Config::adminPrefix();
    }

    
    /**
     * Página inicial do painel
     * @param $request - valores esperados:
     *          view - carrega outras versões do template da home, ex: view=v2  - espera o arquivo index-v2.blade
     */
    public function index(Request $request){
        //$diff = \App\Utilities\TextDiffUtility::class;
        //exit( $diff::toHTML('aaa'.chr(10).'meu nome é joão'.chr(10).'Teste', 'aaa'.chr(10).'meu e nome é maria'.chr(10).'e o dele é paulo') . $diff::css(true) );
        
        /*echo '
          <script>
            window.open("http://localhost/robo-gc/robo-gc-v03/public/wsrobot/data?action=get_process&robot_id=1&post=true");
            window.open("http://localhost/robo-gc/robo-gc-v03/public/wsrobot/data?action=get_process&robot_id=2&post=true");
         </script>
         ok';
        */
        /*
        $r = \App\Services\MailService::sendClass(new \App\Mail\TestMail());//,['queue'=>1]     //['tries'=>4,'retry_after'=>0]
        $r = \App\Services\MailService::send([
            'to'=>'aurelio@aurlweb.com.br',
            'subject'=>'E-mail de teste 1 - depois de 2 minutos',
            'message'=>'Minha mensagem de texto',
            'queue'=>2  //2 minutos para enviar
        ]);
        dd('ok',$r);
        */
        
        
        
        
        $view = $request->input('view');
        if(\Config::accountData() || $this->prefix=='super-admin'){//está com uma conta associada no login
            return view($this->prefix.'.index'. ($view?'-'.$view:''));
            
        }else{//sem conta associada e tentando acessar o admin, portanto redireciona o super-admin
            return \Redirect::to(route('super-admin.index'))->send();
        }
    }
}
