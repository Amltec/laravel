<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;
use App\Utilities\ValidateUtility;
use App\Services\MetadataService;
use App\Models\Robot;
use Exception;

class RobotsController extends SuperAdminBaseController{

    public function __construct(Robot $RobotModel){
        parent::__construct();

        $this->RobotModel = $RobotModel;
    }


    public function index(Request $request){
        $userLoggedLevel = \Auth::user()->user_level;

        $filter_status=$request->input('status');
        $model = $this->RobotModel;
            if(_GET('is_trash')=='s')$model=$model->onlyTrashed();
        $model=$model->orderBy('robot_name','asc')->paginate(_GETNumber('regs')??50);

        return view('templates.pages.page', [
            'title'=>'Robôs',
            'toolbar'=>function(){
                echo view('templates.components.button',['title'=> '+ Robô','color'=>'primary','href'=>route('super-admin.app.create','robots')]);
            },
            'content'=> function() use ($model, $userLoggedLevel){
                $param = [
                    'data'=>$model,
                    'columns'=>[
                        'id'=>'ID',
                        'robot_name'=>'Robô',
                        'conn_last'=>['Última Conexão','value'=>function($v){return $v?$v:'-';}],
                        'account_ids'=>['Conta Exclusiva','value'=>function($v,$reg){$as = $reg->getAccounts(); return $as ? join(', ',$as->pluck('account_name')->toArray()) : '-'; }],
                        'status_label'=>'Status',
                    ],
                    'options'=>[
                        'checkbox'=>true,
                        'select_type'=>2,
                        'pagin'=>true,
                        'confirm_remove'=>true,
                        'toolbar'=>true,
                        'regs'=>false,
                        'search'=>false
                    ],
                    'routes'=>[
                        'click'=>function($reg){return route('super-admin.app.edit',['robots',$reg->id]);},
                        'remove'=>route('super-admin.app.remove','robots'),
                    ],
                    'field_click'=>'robot_name',
                    'row_opt'=>[
                        'class'=>function($reg){return $reg->robot_status=='c'?'row-deleted':'';}
                    ],
                    'metabox'=>true,
                ];

                echo view('templates.ui.auto_list',$param);

            },
        ]);

    }


    public function create() {
        return view('templates.pages.page', [
            'title'=>'Novo Robô',
            'content'=> function(){
                echo $this->html_form();
            }
        ]);
    }


    public function edit($id){
        $model = $this->RobotModel->find($id);
        $userLoggedLevel = \Auth::user()->user_level;
        if($userLoggedLevel=='dev' || $userLoggedLevel=='superadmin'){
            return view('templates.pages.page', [
                'title'=>'Robô <small>#'.$id.'</small>',
                'content'=> function() use($model){
                    echo $this->html_form($model);
                }
            ]);
        }else{
            return view('templates.pages.page', [
                'title'=>'Robô <small>#'.$id.'</small>',
                'content'=> function() use($model){
                    echo $this->info($model);
                },
                'dashboard'=>['route_back'=>route('super-admin.app.index','robots')]
            ]);
        }
    }


    public function store(Request $request,$id=null){//se definido o $id, então é atualização
        $data = $request->all();
        if(!$id)$data['robot_status']='0';//aguardando integração

        $param1 = [
            'robot_name'=>'required|max:100',
            'robot_name_cli'=>'required|max:100',
            'key_active'=>'max:100|unique:robots,key_active',
            //'account_ids'=>'',
        ];
        if($id){//edit
            $action='edit';
            $param1['key_active'].=','.$id.'';
            $param1['robot_status']='required';
        }else{//add
            $action='add';
            $data['key_active']=$this->post_generateKey()['key'];
        }
        //dd($param1);
        $validade = validator($data, $param1, \App\Utilities\FieldsValidatorUtility::getMessages());
        if($validade->fails()){
            return ['success'=>false,'msg'=>$validade->errors()->messages()];
        }
        if($id){//edit
            if(!$data['key_robot'] && $data['robot_status']=='a')return ['success'=>false,'msg'=>['robot_status'=>'Não é possível ativar alterar o status para Ativado. Precisa ser integrado pelo Aplicação do Robô.']];
        }


        $robot_config = [
            'filter_process_name'=>$data['config_filter_process_name']??'',
            'filter_process_prod'=>$data['config_filter_process_prod']??'',
            'filter_insurer_id'=>$data['config_filter_insurer_id']??'',
            'filter_broker_id'=>$data['config_filter_broker_id']??'',
            'filter_process_id'=>$data['config_filter_process_id']??'',
        ];
        $data['robot_config'] = serialize($robot_config);
        //dd($data,$robot_config);

        //verifica se a conta existe
        if($data['account_ids']){
            $ac_ids = explode(',',$data['account_ids']);
            foreach($ac_ids as $aid){
                $aid = trim($aid);
                $account=\App\Models\Account::find($aid);
                if(!$account)return ['success'=>false,'msg'=>['account_ids'=>'ID da conta '. $aid .' inválido']];
                if(!$account->process_single)return ['success'=>false,'msg'=>['account_ids'=>'A conta informada '. $aid .' precisa ter o processamento exclusivo']];
            }
        }

        try{
            if($id){//edit
                $r=[
                    'success'=>true,
                    'msg' => 'Registro atualizado',
                    'action'=>'edit'
                ];
                if(($data['key_active_old']??null) != $data['key_active']){//quer dizer que houver mudança de token
                    $data['key_robot']=null;//limpa o campo da chave do robô
                    if($data['robot_status']=='a'){
                        $data['robot_status']='0';//aguardando integração
                        $r['action_js']='refresh';
                    }
                }
                $model = $this->RobotModel->find($id);
                $model->update($data);


            }else{//add
                $model = $this->RobotModel->create($data);
                $r=[
                    'success'=>true,
                    'msg' => 'Registro cadastrado',
                    'action'=>'add',
                    'url_edit' => route('super-admin.app.edit',['robots',$model->id]),
                    'data' => $model->toArray(),
                ];
            }
        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }

        if($r['success']){
            //ajusta os dados para o log
            unset($data['robot_config']);
            $model->addFieldsLog($action,$data);
        }

        return $r;
    }

    public function update(Request $request, $id){
        return $this->store($request,$id);
    }


    public function remove(Request $request) {
        $data = $request->all();
        if($data['action']=='remove'){
            $id=$data['id'];
            $model = $this->RobotModel->onlyTrashed()->find($id);
            if($model){
                 MetadataService::del('robots', $id);
                 $model->forceDelete();//deleta o registro
                 $model->addLog('remove');
            }
            $r=['success'=>true,'msg' => 'Removido com sucesso'];

        }else if($data['action']=='restore'){
            $model=$this->RobotModel->onlyTrashed()->find($data['id']);
            $model->restore();
            $model->addLog('restore');
            $r=['success'=>true,'msg' => 'Registro Restaurado'];

        }else if($data['action']=='trash'){
            $model = $this->RobotModel->find($data['id']);
            if($model){
                $model->delete();//irá mandar para a lixeira
                $model->addLog('trash');
            }
            $r=['success'=>true,'msg' => 'Movido para a lixeira'];
        }
        return $r;
    }



    public function post_generateKey(){//gera uma nova chame
        $key = md5(rand().time());
        return ['success'=>true,'key'=> strtoupper($key)];
    }

    //monta o html de visualização
    private function info($model) {
        return view('templates.components.metabox',[
            'content'=>function() use ($model){
                echo view('templates.ui.view',[
                    'data'=>[
                        'robot_name'=>['title'=>'Nome do Robô','value'=>$model->robot_name],
                        'key_active'=>['title'=>'Token de Instalação','value'=>$model->key_active],
                        'key_robot'=>['title'=>'Identificador do Robô','value'=>$model->key_robot],
                        'conn_last'=>['title'=>'Última Conexão','value'=>$model->conn_last]
                    ]
                ]);
            }
        ]);

    }

    //monta o html do
    private function html_form($model=null){
        $param_fields = [
            'robot_name'=>['label'=>'Nome do Robô','maxlength'=>100,'require'=>true,'attr'=>'onchange=\'var o=$(this).closest("form").find("#robot_name_cli");if(o.val()=="")o.val(this.value);\''],
            'robot_name_cli'=>['label'=>'Nome do Robô (cliente)','maxlength'=>100,'require'=>true,'id'=>'robot_name_cli'],
            'key_active_old'=>['type'=>'hidden','value'=> $model?$model->key_active:'' ],
            'key_active'=>['label'=>'Token de instalação','id'=>'field-key_active','maxlength'=>100,'require'=>true,'attr'=>'readonly="readonly"',
                    'button'=>['icon'=>'fa-key','title'=>'Gerar chave',
                    'post'=>[
                            'url'=>route('super-admin.app.post',['robots','generateKey']),
                            'data'=>['test_json'=>'ok','a'=>1,'b'=>true,'c'=>'teste'],
                            'cb'=>'@function(r){ if(r.success)$("#field-key_active").val(r.key); }',
                            'confirm'=>'Ao continuar, o novo token precisará ser atualizado no App do Robô'
                        ]
                    ]
            ],
            'key_robot'=>['label'=>'Identificador do Robô','attr'=>'readonly="readonly"','placeholder'=>'Aguardando integração com o App Local',
                    'button'=>['icon'=>'fa-refresh','alt'=>'Recarregar página','title'=>false,'attr'=>'onclick="window.location.reload()"']
            ],
            'robot_status'=>['type'=>'select','label'=>'Status','list'=>[
                ''=>'',
                '0'=>'Aguardando integração',
                'a'=>'Ativado',
                'e'=>'Erro de integração',
                'c'=>'Cancelado'
             ],'require'=>true],

            'account_ids'=>['label'=>'Exclusivo para as contas Ids','placeholder'=>'Ids separados por virgula'],
            'line01'=>['type'=>'info','text'=>'<hr><span class="text-muted">Configurações adicionais - Filtros:<br>Nos campos abaixo, para mais de um valor, utilize virgula, ex: val, val2, val3</span>'],
            'config_filter_process_name'=>['label'=>'Filtro por processo', 'attr'=>'title="Ex: cad_apolice, seguradora_files, ..."','placeholder'=>'Nome do processo','info_html'=>'<small class="text-muted">Os registros serão executados na ordem dos nomes informados acima</small>'],
            'config_filter_process_prod'=>['label'=>'Filtro por produto', 'attr'=>'title="Ex: automovel, vida, ..."','placeholder'=>'Nome do subprocesso/produto'],
            'config_filter_insurer_id'=>['label'=>'Filtro por seguradora', 'attr'=>'title="Ex: 1, 2, 3"','placeholder'=>'ID da seguradora'],
            'config_filter_broker_id'=>['label'=>'Filtro por corretor', 'attr'=>'title="Ex: 1, 2, 3"','placeholder'=>'ID do corretor'],
            'config_filter_process_id'=>['label'=>'Filtro por id do processo', 'attr'=>'title="Ex: 1, 2, 3"','placeholder'=>'ID do processo do robõ (precisa estar com um dos status=p|0|e|c|1)'],

        ];
        if(empty($model)){//add
            //$param_fields['key_active']['value']= $this->post_generateKey()['key'];
            unset($param_fields['robot_status'],$param_fields['key_active'],$param_fields['key_robot']);
        }

        //ajusta a var model para o preenchimento atuomático pelo auto_fields
        $config = $model ? unserialize($model->robot_config) : null;
        if($config){
            foreach($config as $f=>$v){
                $model->{'config_'.$f}=$v;
            }
        }
        //dd($model->toArray());

        return view('templates.ui.auto_fields',[
            'layout_type'=>'horizontal',
            'form'=>[
                'url_action'=> (!empty($model)?route('super-admin.app.update',['robots',$model->id]):route('super-admin.app.store','robots')),
                'url_back'=>route('super-admin.app.index','robots'),
                'data_opt'=>[
                    'focus'=>true,
                    'onSuccess'=>"@function(r){if(r.action_js=='refresh'){window.location.reload();}else if(r.action=='add'){window.location=String('". route('super-admin.app.edit',['robots',':id']) ."').replace(':id',r.data.id);} }"
                ],
                'bt_save'=>true,
                'bt_back'=>true,
                'autodata'=>$model??false
            ],
            'metabox'=>true,
            'autocolumns'=>$param_fields,
        ]);
    }



}
