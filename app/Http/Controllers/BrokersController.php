<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Utilities\FieldsValidatorUtility;
use App\Utilities\ValidateUtility;
use App\Services\MetadataService;
use Gate;

use App\Models\BrokerAuth as Broker;
use App\Models\BrokerInsurerData;
use Auth;

class BrokersController extends Controller{

    public function __construct(Broker $BrokerModel, BrokerInsurerData $BrokerInsurerData, Request $req){
        $this->BrokerModel = $BrokerModel;
        $this->BrokerInsurerData = $BrokerInsurerData;
    }

    //Redireciona se o usuário não tiver permissão de acesso
    public function permissAdmin(){
        if(Gate::denies('admin')){//é negado a permissão para não administrador
            return \Redirect::to(route('admin.index'))->send();
        }
    }


    public function index(Request $request){
        $this->permissAdmin();//permissão do administrador ou acima

        $model = $this->getListFilter([
            'q'=>$request->q,
            'status'=>$request->broker_status,
            'is_trash'=>_GET('is_trash'),
        ]);

        return view('templates.pages.page', [
            'title'=>'Corretores',
            'toolbar'=>function(){
                echo view('templates.components.button',['title'=> '+ Corretor','color'=>'primary','href'=>route('admin.app.create','brokers')]);
            },
            'content'=> function() use ($model){
                echo view('templates.ui.toolbar',[
                    'autocolumns'=>[
                        'q'=>['label'=>'Corretor, SUSEP ou CNPJ/CPF','width_group'=>250],
                        'broker_status'=>['label'=>'Status','type'=>'select','list'=>[''=>'','a'=>'Normal','c'=>'Cancelado']],
                    ],
                ]);

                echo view('templates.ui.auto_list',[
                        'data'=>$model,
                        'columns'=>[
                            'id'=>'ID',
                            'broker_alias'=>'Corretor',
                            'broker_doc'=>'SUSEP',
                            'broker_cpf_cnpj'=>'CNPJ / CPF',
                            'status_label'=>'Status',
                        ],
                        'options'=>[
                            'checkbox'=>true,
                            'select_type'=>2,
                            'pagin'=>true,
                            'confirm_remove'=>true,
                            'toolbar'=>true,
                            'regs'=>true,
                            'search'=>false
                        ],
                        'routes'=>[
                            'click'=>function($reg){return route('admin.app.edit',['brokers',$reg->id]);},
                            'remove'=>route('admin.app.remove','brokers'),
                        ],
                        'field_click'=>'broker_alias',
                        'row_opt'=>[
                            'class'=>function($reg){return $reg->broker_status=='c'?'row-deleted':'';}
                        ],
                        'metabox'=>true,
                    ]);

            },
        ]);

    }

    /**
     * Captura a lista de corretores para uma requisição ajax
     * @param $request - valores:
     *          status  - filtro por status
     *          q       - filtro por palavra chave nos campos: broker_alias, broker_name, broker_doc, broker_cpf_cnpj
     *          mode    - modo de retorno da lista. Valores: json, select2
     *          first_blank - se 's' irá gerar o primeiro resultado em branco
     */
    public function get_listAjax(Request $request){

        //ddx($request->all());
        $model = $this->getListFilter([
            'status'=>$request->input('status'),
            'q'=>$request->input('q'),
            'is_trash'=>'all',
            'regs'=>1000,  //limita a 1000 resultados
            'first_blank'=>'',
            'account_id'=>$request->input('account_id'),//usado apenas quando for acesso dentro de um superadmin
        ]);

        $r=$model->pluck('broker_name2','id')->toArray();
        if($request->input('first_blank')=='s')$r=[' '=>'']+$r;
        if($request->input('mode')=='select2'){
            $n=[];
            foreach($r as $id=>$text){
                $n[]=['id'=>$id,'text'=>$text];
            }
            $r=$n;
        }
        return $r;
    }


    /**
     * Retorna ao model filtrado da lista de corretores
     * @param array $opt - valores:
     *      status      - filtro por status
     *      q           - filtro por palavra chave nos campos: broker_alias, broker_name, broker_doc, broker_cpf_cnpj
     *      regs        - registros por página (default 15). Aceita 0|false para retornar a todos os campos
     *      is_trash    - filtro por registros excluídos. Valores true | 's' para sim, default false. Aceita 'all' para indicar que deve filtrar em ambos os casos
     *      account_id  - filtro pelo id da conta
     */
    private function getListFilter($opt){
        $opt = array_merge([
            'status'=>'',
            'q'=>'',
            'regs'=>_GETNumber('regs')??15,
            'is_trash'=>false,
            'account_id'=>null,//usado apenas quando for acesso dentro de um superadmin
        ],$opt);

        $model = $this->BrokerModel
            ->selectRaw('*, CONCAT(broker_name, IF(broker_status="c"," - Cancelado","") ) as broker_name2')
            ->orderBy('broker_alias','asc');
        if($opt['status'])$model->where('broker_status',$opt['status']);

        $q=$opt['q'];
        if($q){
            $model->where(function($query) use($q){
                $n=str_replace(['/','.','-'],'',$q);
                $query->where('broker_alias','like','%'. $q .'%')
                    ->orWhere('broker_name','like','%'. $q .'%');
                if(is_numeric($n)){
                    $query->orWhereRaw('REPLACE(REPLACE(REPLACE(broker_doc,"/",""),".",""),"-","") like ?',['%'.$n.'%'])
                          ->orWhereRaw('REPLACE(REPLACE(REPLACE(broker_cpf_cnpj,"/",""),".",""),"-","") like ?',['%'.$n.'%']);
                }
            });
        }

        if($opt['is_trash']=='s'){
            $model->onlyTrashed();
        }else if($opt['is_trash']=='all'){
            $model->withTrashed();
        }//else //somente não excluídos


        if(\Config::adminPrefix()=='super-admin'){//é o painel superadmin
            $n=$opt['account_id'];
            if(empty($n))$n='-1';//seta -1 para anular o sql
            $model->where('account_id',$n);
        }//else{ é painel 'admin', portanto dentro da model já irá validar pelo id da conta logada

        if($opt['regs']){
            return $model->paginate($opt['regs']);
        }else{
            return $model->get();
        }
    }

    public function create(){
        $this->permissAdmin();//permissão do administrador ou acima
        return view('admin.broker_add_edit',['model'=>null]);
    }


    public function edit($id){
        $this->permissAdmin();//permissão do administrador ou acima
        $model = $this->BrokerModel->find($id);
        if(!$model)return 'Erro ao localizar cadastro';
        return view('admin.broker_add_edit',['model'=>$model]);
    }



    public function store(Request $request,$id=null){
        $this->permissAdmin();//permissão do administrador ou acima
        $data = $request->all();
        if(!$id)$data['broker_status']='a';//registro normal

        $account_id = Auth::user()->getAuthAccount('id');
        $account_config = \Config::accountData()->data['config']??null;

        $param1 = [
            'broker_alias'=>'required|max:50',
            'broker_name'=>'required|max:100',
            'broker_doc'=>'required|max:100',
            /*'broker_doc'=>['required','max:100',
                                Rule::unique('brokers','broker_doc')->where(function($query) use($id,$account_id){
                                    if($id)$query->where('id','!=',$id);
                                    return $query->where('account_id', $account_id);
                                })
            ],*/
            'broker_cpf_cnpj'=>['required','max:20',
                                Rule::unique('brokers','broker_cpf_cnpj')->where(function($query) use($id,$account_id){
                                    if($id)$query->where('id','!=',$id);
                                    $query->where('account_id', $account_id);
                                    return $query;
                                })
            ],
            //'broker_col_user'=>'required',
            //'broker_col_login'=>'required',
        ];

        if($id){//edit
            $action='edit';
            $param1['broker_status']='required';
        }else{
            $action='add';
            //$param1['broker_col_senha']='required';
        }

        $msgValidator = FieldsValidatorUtility::getMessages();

        $validate = validator($data, $param1, $msgValidator);
        if($validate->fails()){
            $messages = $validate->errors()->messages();
            //customiza as mensagens
            $messages=FieldsValidatorUtility::customMessages($messages, 'broker_doc', 'Este valor já está cadastrado', 'Este SUSEP já está cadastrado para outro corretor no sistema');
            return ['success'=>false,'msg'=>$messages];
        }


        //verifica se o campo broker_doc não está duplicado
        foreach(explode(',',$data['broker_doc']) as $n){
            $n = str_replace(['/','.','-'],'',$n);
            $m = $this->BrokerModel->whereRaw('REPLACE(REPLACE(REPLACE(broker_doc,"/",""),".",""),"-","") like ?','%'.$n.'%');
            if($id)$m->where('id','<>',$id);
            $m = $m->first();
            if($m)return ['success'=>false,'msg'=>['broker_doc'=>'Nº SUSEP '.$n.' já cadastrado para outro corretor ('. $m->broker_alias . ' #'. $m->id .')']];
        }


        if($data['broker_cpf_cnpj'] && !ValidateUtility::isDocument($data['broker_cpf_cnpj']))return ['success'=>false,'msg'=>['broker_cpf_cnpj'=>'Documento inválido']];

        if(!empty($data['broker_col_senha'])){//foi definida uma senha
            $validate = validator($data, [
                'broker_col_senha'=>'required',
                'broker_col_senha2'=>'required|required_with:broker_col_senha|same:broker_col_senha',
            ], $msgValidator);
            if($validate->fails()){return ['success'=>false,'msg'=>$validate->errors()->messages()];}
        }else{
            unset($data['broker_col_senha']);
        }
        unset($data['broker_col_senha2']);

        if($data['broker_col_senha']??false)$data['broker_col_senha']=$data['broker_col_senha'];
        if(empty($data['broker_col_user']))unset($data['broker_col_user']);
        if(empty($data['broker_col_login']))unset($data['broker_col_login']);
        if(empty($data['broker_col_senha']))unset($data['broker_col_senha']);


        //verifica as configurações de logins das seguradoras
        $is_config_seguradora_data = array_get($account_config,'seguradora_data.active')=='s';
        $logins_insurer_save=[];
        if($is_config_seguradora_data && $data['logins_insurer_id']??false){
            $validate=[];
            foreach($data['logins_insurer_id'] as $insurer_id){
                $active = ($data['active_'.$insurer_id]??null)=='s';
                $isnew = $data['new_item_'.$insurer_id]=='s';
                $use_quiver = $data['insurer_use_quiver_'.$insurer_id];
                $login_quiver = $data['insurer_login_quiver_'.$insurer_id];
                $login = $data['insurer_login_'.$insurer_id];
                $pass = $data['insurer_pass_'.$insurer_id];
                $code = $data['insurer_code_'.$insurer_id];
                $user = $data['insurer_user_'.$insurer_id];

                $logins_insurer_save[$insurer_id]=['active'=>$active,'use_quiver'=>$use_quiver,'login_quiver'=>$login_quiver,'login'=>$login,'pass'=>$pass,'code'=>$code,'user'=>$user];
                if($active){
                    if($use_quiver=='s'){//login pela central de senhas
                        if(!$login_quiver)$validate['insurer_login_quiver_'.$insurer_id]='Campo obrigatório';
                    }else{//login no site da seguradora
                        if(!$login)$validate['insurer_login_'.$insurer_id]='Campo obrigatório';
                        if($isnew && !$pass)$validate['insurer_pass_'.$insurer_id]='Campo obrigatório';
                    }
                }
            }
            //dd($validate ,$logins_insurer_save);
            if($validate)return ['success'=>false,'msg'=>$validate];
        }

        try{
            if($id){
                $model = $this->BrokerModel->find($id);
                $model->update($data);
                $r=[
                    'success'=>true,
                    'msg' => 'Registro atualizado',
                    'action'=>'edit'
                ];
            }else{
                $model = $this->BrokerModel->create($data);
                $id = $model->id;
                $r=[
                    'success'=>true,
                    'msg' => 'Registro cadastrado',
                    'action'=>'add',
                    'url_edit' => route('admin.app.edit',['brokers',$id]),
                    'data' => $model->toArray(),
                ];
            }

        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }



        if($r['success']){
            if(empty($data['cad_apolice_comissao_desc']))$data['cad_apolice_comissao_desc']=[];//por ser checkbox, este campo pode não existir em $data
            //lógica: remove os registros de 'meta_name=comissao_desc' de todos os corretores para adicionar somente as opções marcadas
            //$configProcessNames = \App\ProcessRobot\VarsProcessRobot::$configProcessNames;

            $n=[];//armazena na sintaxe: [insurer_id => [product=>true, ] ]
            foreach($data['cad_apolice_comissao_desc'] as $v){
                $v=explode(',',$v);//sintaxe: 'product,insurer_id'  - ex: 'automovel,1'
                if(!isset($n[$v[1]]))$n[$v[1]]=[];
                $n[$v[1]][$v[0]]=true;

            }
            //atualiza no db
            $this->BrokerInsurerData->where(['broker_id'=>$id, 'meta_name'=>'cad_apolice_comissao_desc'])->delete();
            foreach($n as $insurer_id=>$v){//$v = [prod=>boolen)
                $this->BrokerInsurerData->create(['broker_id'=>$id, 'insurer_id'=>$insurer_id, 'meta_name'=>'cad_apolice_comissao_desc', 'meta_value'=> serialize($v)]);
            }
            //dd($data['cad_apolice_comissao_desc'],$n);

            //adiciona o log
            $model->addFieldsLog($action,$data,'','denied:logins_insurer_id');
        }


        //salva os logins das seguradoras
        if($is_config_seguradora_data && $logins_insurer_save){
            //dd($logins_insurer_save);
            foreach($logins_insurer_save as $insurer_id => $fields){
                $arr=['insurer_id'=>$insurer_id,'broker_id'=>$id, 'meta_name'=>'seguradora_config'];
                $m=$this->BrokerInsurerData->where($arr);
                if($m->exists()){
                    $olddata = unserialize($m->value('meta_value'));
                    if(empty($fields['pass']))$fields['pass'] = $olddata['pass'];//a senha não foi alterada, pois não está informando, portanto seta a que está gravado no db
                    if($olddata==$fields)continue;//quer dizer as arrays são iguais, e portanto não precisa atualizar
                    $m->update(['meta_value'=>serialize($fields)]);
                }else{
                    $arr['meta_value']=serialize($fields);
                    $this->BrokerInsurerData->create($arr);
                }
            }
        }


        //salva os nomes dos produtos permitidos
        $produtcs_allow = join(',',$data['produtcs_allow']??[]);
        $model->setMetadata('products_allow',$produtcs_allow);


        return $r;
    }

    public function update(Request $request, $id){
        return $this->store($request,$id);
    }


    public function remove(\App\Models\Base\ProcessRobot $ProcessRobotModel,Request $request) {
        $this->permissAdmin();//permissão do administrador ou acima
        $data = $request->all();
        $id=$data['id'];

        //verifica se o registro está associado associado a um processo, e neste caso não deverá excluí-lo
        if($ProcessRobotModel->where('broker_id',$id)->count()>0)return ['success'=>false,'msg' =>'Não é possível excluir. Motivo: registro de processos do robô relacionado.'];

        if($data['action']=='remove'){
            $model = $this->BrokerModel->onlyTrashed()->find($id);
            if($model){
                 $this->BrokerInsurerData->where('broker_id',$id)->delete();
                 MetadataService::del('brokers', $id);
                 $model->forceDelete();//deleta o registro
                 $model->addLog('remove');
            }
            $r=['success'=>true,'msg' => 'Removido com sucesso'];

        }else if($data['action']=='restore'){
            $model = $this->BrokerModel->onlyTrashed()->find($id);
            $model->restore();
            $model->addLog('restore');
            $r=['success'=>true,'msg' => 'Registro Restaurado'];

        }else if($data['action']=='trash'){
            $model = $this->BrokerModel->find($id);
            if($model){
                $model->delete();//irá mandar para a lixeira
                $model->addLog('trash');
            }
            $r=['success'=>true,'msg' => 'Movido para a lixeira'];
        }
        return $r;
    }



}
