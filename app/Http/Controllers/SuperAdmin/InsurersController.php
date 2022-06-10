<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Utilities\ValidateUtility;
use App\Services\MetadataService;

use App\Models\Insurer;
use App\Models\BrokerInsurerData;
use Gate;

class InsurersController extends SuperAdminBaseController {
    
    /**
     *Nomes de classes que já estão prontas para o processamento do robô
     */
    public static $classNamesProcessRobot=[
        'bradesco.automovel'=>'Bradesco - Automóvel'
    ];
    
    
    
    public function __construct(Insurer $InsurerModel, BrokerInsurerData $BrokerInsurerData){
        parent::__construct();
        
        $this->InsurerModel = $InsurerModel;
        $this->BrokerInsurerData = $BrokerInsurerData;
        
        if(!Gate::allows('dev')){//somente desenvolvedor pode ter acesso
            return self::redirectUserDenied();//permissão negado para o usuário não super programador
        }
    }
    
    
    public function index(Request $request){
        $filter_status=$request->input('status');
        $model = $this->InsurerModel;
            if(_GET('is_trash')=='s')$model=$model->onlyTrashed();
        $model=$model->paginate(_GETNumber('regs')??15);
        
        return view('templates.pages.page', [
            'title'=>'Seguradoras',
            'toolbar'=>function(){
                echo view('templates.components.button',['title'=> '+ Seguradora','color'=>'primary','href'=>route('super-admin.app.create','insurers')]);
            },
            'content'=> function() use ($model){
                echo view('templates.ui.auto_list',[
                        'data'=>$model,
                        'columns'=>[
                            'id'=>'ID',
                            'insurer_alias'=>['Seguradora','value'=>function($v,$reg){
                                return $v .'<br><span class="nostrong">'. $reg->insurer_basename .'</span>';
                            }],
                            'insurer_doc'=>['CNPJ / SUSEP / Text ID','value'=>function($v){
                                return join('<br>',explode(',',$v));
                            }],
                            'status_label'=>'Status',
                            'created_at'=>'Cadastro',
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
                            'click'=>function($reg){return route('super-admin.app.edit',['insurers',$reg->id]);},
                            'remove'=>route('super-admin.app.remove','insurers'),
                        ],
                        'field_click'=>'insurer_alias',
                        'row_opt'=>[
                            'class'=>function($reg){return $reg->insurer_status=='c'?'row-deleted':'';}
                        ],
                        'metabox'=>true,
                    ]);
                        
            },
        ]);
        
    }
    
    
    public function create() {
        return view('templates.pages.page', [
            'title'=>'Nova Seguradora',
            'content'=> function(){
                echo $this->html_form();
            }
        ]);
    }
    
    
    public function edit($id){
        $model = $this->InsurerModel->find($id);
        //$model->metadata_process_robot = MetadataService::getValue($model->getTable(),$model->id,'process_robot');
        $model->insurer_doc = join(chr(10),array_map('trim',explode(',',$model->insurer_doc)));
        return view('templates.pages.page', [
            'title'=>'Seguradora <small>#'.$id.'</small>',
            'content'=> function() use($model){
                echo $this->html_form($model);
            }
        ]);
    }
    
    
    
    public function store(Request $request,$id=null){
        $data = $request->all();
        if(!$id)$data['insurer_status']='a';//registro normal
        
        $param1 = [
            'insurer_alias'=>'required',
            'insurer_name'=>'required',
            'insurer_razaosocial'=>'required',
            'insurer_doc'=>'required',
        ];
        if($id){//edit
            $action='edit';
            $param1['insurer_status']='required';
            unset($param1['insurer_basename']);
        }else{//add
            $action='add';
            //formata o campo basename
            $data['insurer_basename']= str_replace('-','_',\App\Utilities\FormatUtility::sanitizeSlug($data['insurer_basename']));
            $param1['insurer_basename']='required|max:50|unique:insurers,insurer_basename';
        }
        
        //dd($param1);
        $validade = validator($data, $param1, \App\Utilities\FieldsValidatorUtility::getMessages());
        if($validade->fails()){
            return ['success'=>false,'msg'=>$validade->errors()->messages()];
        }
        
        $insurer_find_rule = $data['insurer_find_rule'];
        if($insurer_find_rule && !ValidateUtility::isJson($insurer_find_rule))return ['success'=>false,'msg'=>['insurer_find_rule'=>'Formato json inválido']];
        
        
        $docs = explode(',',str_replace(chr(10),',',$data['insurer_doc']));
        /*$x=0;
        foreach($docs as $doc){
            $doc=trim($doc);
            if($doc)$x++;
            if($doc && (ValidateUtility::isCNPJ($doc)==false && ValidateUtility::isNumberStr($doc)==false)){
                return ['success'=>false,'msg'=>['insurer_doc'=>'CNPJ '.$doc.' inválido']];
            }
        }
        if($x==0)return ['success'=>false,'msg'=>['insurer_doc'=>'CNPJ inválido']];
        */
        $log_insurer_doc=$docs;
        $data['insurer_doc']=join(',',array_map('trim',$docs));
        
        
        /*if(!empty($data['metadata_process_robot'])){
            if(!ValidateUtility::isJson($data['metadata_process_robot'])){
                return ['success'=>false,'msg'=>['metadata_process_robot'=>'Formato JSON inválido']];
            }
        }*/
        
        
        try{
            if($id){
                $model = $this->InsurerModel->find($id);
                $model->update($data);
                $r=[
                    'success'=>true,
                    'msg' => 'Registro atualizado',
                    'action'=>'edit'
                ];
            }else{
                $model = $this->InsurerModel->create($data);
                $r=[
                    'success'=>true,
                    'msg' => 'Registro cadastrado',
                    'action'=>'add',
                    'url_edit' => route('super-admin.app.edit',['insurers',$model->id]),
                    'data' => $model->toArray(),
                ];
            }
        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        
        if($r['success']){
            //atualiza o campo para o log
            $data['insurer_doc']=$log_insurer_doc;
            $model->addFieldsLog($action,$data);
        }
        
        return $r;
    }
    
    public function update(Request $request, $id){
        return $this->store($request,$id);
    }
    
    
    public function remove(\App\Models\Base\ProcessRobot $ProcessRobotModel,Request $request){
        $data = $request->all();
        $id=$data['id'];
        
        
        //verifica se o registro está associado associado a um processo, e neste caso não deverá excluí-lo
        if($ProcessRobotModel->where('insurer_id',$id)->count()>0)return ['success'=>false,'msg' =>'Não é possível excluir. Motivo: registro de processos do robô relacionado.'];
        
        if($data['action']=='remove'){
            $model = $this->InsurerModel->onlyTrashed()->find($id);
            if($model){
                 $this->BrokerInsurerData->where('insurer_id',$id)->delete();
                 MetadataService::del('insurers', $id);
                 $model->forceDelete();//deleta o registro
                 $model->addLog('remove');
            }
            $r=['success'=>true,'msg' => 'Removido com sucesso'];
            
        }else if($data['action']=='restore'){
            $model=$this->InsurerModel->onlyTrashed()->find($id);
            $model->restore();
            $model->addLog('restore');
            $r=['success'=>true,'msg' => 'Registro Restaurado'];
            
        }else if($data['action']=='trash'){
            $model = $this->InsurerModel->find($id);
            if($model){
                $model->delete();//irá mandar para a lixeira
                $model->addLog('trash');
            }
            $r=['success'=>true,'msg' => 'Movido para a lixeira'];
        }
        return $r;
    }
    
    
    
    


    //monta o html de visualização
    private function html_form($model=null){
        $param_fields = [
            'insurer_basename'=>['label'=>'Identificador','maxlength'=>100,'require'=>true,'info_html'=>'<span class="text-muted">Exatamente conforme nome de classe de processamento</span>'],
            'insurer_alias'=>['label'=>'Nome Curto','maxlength'=>50,'require'=>true],
            'insurer_name'=>['label'=>'Nome da Seguradora','maxlength'=>100,'require'=>true,'info_html'=>'<span class="text-muted">Exatamente como consta no cadastro do Quiver<br>Para mais de um valor, utilize virgula</span>'],
            'insurer_razaosocial'=>['label'=>'Razão Social','maxlength'=>100,'require'=>true],
            'insurer_doc'=>['type'=>'textarea','label'=>'CNPJ / SUSEP / Text ID','require'=>true,'rows'=>'3','info_html'=>'<span class="text-muted">Documento ou Texto Identificador da apólice. Para mais de um valor, utilize virgula</span>','auto_height'=>true],
            'insurer_find_rule'=>['type'=>'textarea','label'=>'Regras adicionais','rows'=>'3','info_html'=>'<span class="text-muted">Utilizado para o caso de mais de uma seguradora encontrada. Formato <a href="#" onclick="awModal({title:\'Regras adicionais para localização de seguradora\',descr:\'<strong>Lógica</strong>: Procura as strings para aceitar ou negar o registro.<br>Parâmetros: <strong>allow</strong> - operador AND, <strong>denied</strong> - operador OR<br>Sintaxe:<br><br>{\<br>&quot;allows&quot;: [string1, string2,...], <br>&quot;denies&quot;: [string1, string2,...]<br>}\'});">json</a>.</span>','auto_height'=>true],
        ];
        if(!empty($model)){//edit
            $param_fields['insurer_status']=['type'=>'select','label'=>'Status','list'=>[''=>'','a'=>'Normal','c'=>'Cancelado','0'=>'Não Ativado'],'require'=>true];
            $param_fields['insurer_basename']=['label'=>'Identificador','attr'=>'disabled="disabled"'];
        }

        /*
        $param_fields['label_config']=['type'=>'info','text'=>'<hr><span class="text-muted">Configurações</span>'];
        $param_fields['metadata_process_robot']=['type'=>'textarea','label'=>'Processos do Robô','placeholder'=>'Formato json','rows'=>'10',
                'info_html'=>'<span class="text-muted"><small>'.
                                '<a href="#" class="margin-r-5" onclick=\'var o=$(this).closest("form").find("[name=metadata_process_robot]");if(o.val()=="" || (o.val()!="" && confirm("Substituir texto?")))o.val("{\n\&quot\cad_apolice\&quot:{\n\t\&quot\automovel\&quot:{\n\t\t\&quot\pdfText\&quot:\&quot;ws01\&quot;\n\t\t}\n\t}\n}").focus();return false;\'>carregar exemplo</a> '.
                                '<a href="#" onclick="alert(\'Veja o arquivo: estrutura-sistema.xlsx - insurers->metadata->process_robot\');return false;">+ info</a> '.
                             '</small></span>'
        ];
        */
        
        return view('templates.ui.auto_fields',[
            'layout_type'=>'horizontal',
            'form'=>[
                'url_action'=> (!empty($model)?route('super-admin.app.update',['insurers',$model->id]):route('super-admin.app.store','insurers')),
                'url_back'=>route('super-admin.app.index','insurers'),
                'data_opt'=>[
                    'focus'=>true,
                    'onSuccess'=>"@function(r){if(r.action=='add'){window.location=String('". route('super-admin.app.edit',['insurers',':id']) ."').replace(':id',r.data.id);} }"
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
