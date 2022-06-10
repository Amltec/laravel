<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Services\LogsService;
use App\Models\UserLog;
use Auth;

class LogsController extends SuperAdminBaseController{
    public function __construct(UserLog $UserLogModel){
        parent::__construct();
        $this->UserLogModel = $UserLogModel;
    }
    
    public function index(Request $request){
        $userLogged=Auth::user();
        
        $filter=[];
        foreach(['account_id','user_id','user_level','area_name','area_id','action','dts','dte'] as $f){
            $filter[$f]=$request->input($f);
        }
        if(empty(array_filter($filter))){//nenhum filtro, portanto adiciona um padrão
            $filter['dts']=date("d/m/Y", time());
            $filter['dte']=$filter['dts'];
        }
        
        $model = $this->UserLogModel->selectRaw(
                    '*,'.
                    '(select accounts.account_name from accounts where accounts.id=user_logs.account_id) as account,'.
                    '(select users.user_name from users where users.id=user_logs.user_id) as user'
                );
        
        
        if($filter['account_id'])$model->where('account_id',$filter['account_id']);
        if($filter['user_id'])$model->where('user_id',$filter['user_id']);
        if($filter['user_level'])$model->where('user_level',$filter['user_level']);
        if($filter['area_name'])$model->whereIn('area_name',explode(',',$filter['area_name']));
        if($filter['area_id'])$model->where('area_id',$filter['area_id']);
        if($filter['action'])$model->where('action',$filter['action']);
        if($filter['dts'])$model->where('created_at','>=',FormatUtility::convertDate($filter['dts']));
        if($filter['dte'])$model->where('created_at','<', date("Y-m-d",strtotime("+1 days",strtotime(FormatUtility::convertDate($filter['dte'])))) );//adiciona 1 dia a última data processada
        //dd($model->toSql(),$model->getBindings());
        $model = $model->orderBy('id', 'desc')->paginate(_GETNumber('regs')??50);
        
        return view('super-admin.log_list', ['model'=>$model,'filter'=>$filter,'userLogged'=>$userLogged]);
    }
    
    
    public function show(Request $request,$id){
        $model = $this->UserLogModel->find($id);
        $user = $model->user;
        $account = $model->account;
        $is_ajax = $request->ajax();
        
        $fHtml=function() use ($model,$user,$account,$is_ajax){
            echo view('templates.ui.view',[
                    'data'=>[
                        ['title'=>'Conta Logada','value'=> isset($account->account_name) ? $account->account_name .' <small style="margin-left:5px;" class="text-muted">#'.$model->account_id.'</small>': '-'],
                        ['title'=>'Usuário Logado','value'=>function() use($user,$model){
                            if($user){
                                return $user->user_name .' <small style="margin-left:5px;" class="text-muted">#'.$model->user_id.' '. (\App\Services\UsersService::$levels[$user->user_level]??$user->user_level) .'</small>';
                            }else{
                                return '-';
                            }
                        }],
                        ['title'=>'Área','value'=>$model->area_name .' #'. $model->area_id],
                        ['title'=>'Data','value'=>$model->created_at],
                        ['title'=>'Log','value'=>LogsService::getResumeData($model)],
                        ['title'=>'Url','value'=>$model->url??'-','type'=>'link'],
                        ['title'=>'IP','value'=>$model->ip??'-'],
                        ['title'=>false,'value'=> ($model->action=='manual' ? str_replace(chr(10),'<br>',$model->log_data) : LogsService::formatLogData($model->log_data)) ],
                    ],
                    'class'=>'view-bordered'. ($is_ajax?' view-condensed':''),
                    'class_field'=>'text-muted',
                    'attr'=>'style="max-width:1000px;"'
                ]);//->render();

            if(Auth::user()->user_level=='dev'){
                echo '<a href="#" onclick="$(\'#div-view-dump\').fadeToggle(\'fast\');">Visualizar pelo Dump</a>';
                echo '<div id="div-view-dump" class="hiddenx">';
                    if(\App\Utilities\ValidateUtility::isSerialized($model->log_data)){
                        dump(unserialize($model->log_data));
                    }else{
                        dump($model->log_data);
                    }
                    echo \App\Utilities\TextDiffUtility::css(true);
                echo '</div>';
            }
        };
        
        if($is_ajax){
            return $fHtml();
            
        }else{
            return view('templates.pages.page', [
                'title'=>'Logs do Sistema <small>#'.$id.'</small>',
                'content'=> $fHtml,
            ]);
        }
    }
    
    
    /**
     * Exibe a página que permite adiciona um registro manual de log
     */
    public function get_add(Request $request){
        return view('templates.pages.page', [
            'title'=>'Adicionar registro manual de log</small>',
            'content'=> function() use($request){
                $rd = $request->input('rd');
                
                echo 'Referência: '. $request->input('area_name') .' #'.$request->input('area_id') .'';
                echo view('templates.ui.auto_fields',[
                    'form'=>[
                        'url_action'=> route('super-admin.app.post',['logs','add']),
                        'url_back'=> $rd,
                        'data_opt'=>[
                            'focus'=>true,
                            'onBefore'=>"@function(r){ return confirm('Confirmar registro manual de log?'); }",
                            'onSuccess'=>"@function(r){if(r.success)window.location=String('". route('super-admin.app.show',['logs',':id']) ."').replace(':id',r.id); }"
                        ],
                        'bt_save'=>'Adicionar',
                        'bt_back'=>$rd!='',
                    ],
                    'metabox'=>true,
                    'autocolumns'=>[
                        'area_name'=>['type'=>'hidden','value'=>$request->input('area_name')],
                        'area_id'=>['type'=>'hidden','value'=>$request->input('area_id')],
                        'log_data'=>['label'=>'Texto do log','type'=>'textarea','require'=>true,'rows'=>10],
                    ],
                ]);
            },
        ]);
    }
    
    /**
     * Salva os dados de um registro manual
     */
    public function post_add(Request $request){
        $userLogged=Auth::user();
        if(!in_array($userLogged->user_level,['dev','superadmin']))exit('Acesso negado');
        $data = $request->all();
        
        $param1 = [
            'area_name'=>'required',
            'area_id'=>'required',
            'log_data'=>'required|min:10',
        ];
        $validade = validator($data, $param1, \App\Utilities\FieldsValidatorUtility::getMessages());
        if($validade->fails()){
            return ['success'=>false,'msg'=>$validade->errors()->messages()];
        }
        
        LogsService::add('manual',$data['area_name'],$data['area_id'],$data['log_data']);
        $id = LogsService::getLastId();
        
        return ['success'=>true,'msg'=>'Adicionado com sucesso','id'=>$id];
    }
    
}
