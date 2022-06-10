<?php
namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\JobFailed;
use Exception;

/* 
 * Lista os processo as filas de execuções de processos das tabelas jobs e failed_jobs
 */
class JobsController{
    
    
    public function __construct(Job $Job,JobFailed $JobFailed) {
        $this->jobModel = $Job;
        $this->JobFailedModel = $JobFailed;
    }
    
    
    public function index(Request $request){
        $job_count = $this->jobModel->count();
        $JobFailed_count = $this->JobFailedModel->count();
        
        return view('templates.pages.page', [
            'title'=>'Fila de Processos',
            'content'=>function() use($job_count,$JobFailed_count,$request){
                $route = route('super-admin.app.index','jobs');
                
                echo '
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box" onclick="goToUrl(\''.$route.'/list\')"; style="cursor:pointer";>
                            <span class="info-box-icon bg-aqua"><i class="fa fa-circle-o"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Aguardando Execução</span>
                                <span class="info-box-number" style="font-size:35px;font-weight:normal;">'. $job_count .'</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="info-box" onclick="goToUrl(\''.$route.'/list_failed\');" style="cursor:pointer";>
                            <span class="info-box-icon bg-red"><i class="fa fa-circle-o"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Falhas</span>
                                <span class="info-box-number" style="font-size:35px;font-weight:normal;">'. $JobFailed_count .'</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="'.$route.'/list">
                    <span class="pull-right">Veja mais</span>
                    <h4>Últimos Processamentos</h4>
                </a>';
                echo $this->content_list($request,5,false);
                
                
                echo '
                <a href="'.$route.'/list_failed">
                    <span class="pull-right">Veja mais</span>
                    <h4>Últimas Falhas</h4>
                </a>';
                echo $this->content_listFailed($request,5,false);
            }
        ]);
        
    }
    
    
    private function content_list($request,$limit=null,$is_edit=true){
        $model = $this->jobModel->orderBy('id','desc');
        if($is_edit){
            $model=$model->paginate($limit??15);
        }else{
            $model=$model->take($limit??15)->get();
        }
        return view('templates.ui.auto_list',[
            'list_id'=>'jobs_list',
            'data'=>$model,
            'columns'=>[
                'id'=>'ID',
                'queue'=>'Fila',
                'payload'=>['Trabalho','value'=>function($v){
                    try{
                        return json_decode($v)->displayName;
                    } catch(\Exception $e){
                        return 'Erro ao converter JSON';
                    }
                }],
                'attempts'=>'Tentativas',
                'reserved_at'=>['Processamento','value'=>function($v){ return $v?$v:'-'; }],
                'created_at'=>'Cadastro',
                'status'=>['Status','value'=>function(){ return '<span class="btn btn-xs btn-primary">Pendente / Execução</span>'; }],
            ],
            'options'=>[
                'checkbox'=>true,
                'select_type'=>$is_edit?2:0,
                'pagin'=>true,
                'confirm_remove'=>true,
                'toolbar'=>true,
                'search'=>false,
                'regs'=>false,
                'list_remove'=>false
            ],
            'routes'=>[
                'click'=>function($reg) use($request){return route('super-admin.app.get',['jobs','show',$reg->id,'rd='. urlencode($request->fullUrl()) ]);},
                'remove'=>route('super-admin.app.remove','jobs'),
            ],
            'metabox'=>true,
        ]);
    }
    
    public function get_list(Request $request){
        return view('templates.pages.page', [
            'title'=>'Fila de Processos - Pendente / Em Execução',
            'content'=>function() use($request){
                return $this->content_list($request);
            },
        ]);
    }
    
    
    
    private function content_listFailed($request,$limit=null,$is_edit=true){
        $model = $this->JobFailedModel->orderBy('id','desc');
        if($is_edit){
            $model=$model->paginate($limit??15);
        }else{
            $model=$model->take($limit??15)->get();
        }
        
        return view('templates.ui.auto_list',[
            'list_id'=>'jobs_list',
            'data'=>$model,
            'columns'=>[
                'id'=>'ID',
                'connection'=>'Conexão',
                'queue'=>'Fila',
                'payload'=>['Trabalho','value'=>function($v){
                    try{
                        return json_decode($v)->displayName;
                    } catch(\Exception $e){
                        return 'Erro ao converter JSON';
                    }
                }],
                'exception'=>['Exceção','value'=>function($v,$reg){return str_limit($v,50);}],
                'failed_at'=>'Data do Erro',
                'status'=>['Status','value'=>function(){ return '<span class="btn btn-xs btn-danger">Falha</span>'; }],
            ],
            'options'=>[
                'checkbox'=>true,
                'select_type'=>$is_edit?2:0,
                'pagin'=>true,
                'confirm_remove'=>true,
                'toolbar'=>true,
                'search'=>false,
                'regs'=>false,
                'list_remove'=>false
            ],
            'routes'=>[
                'click'=>function($reg) use($request){return route('super-admin.app.get',['jobs','show_failed',$reg->id,'rd='. urlencode($request->fullUrl()) ]);},
                'remove'=>route('super-admin.app.remove','jobs').'?failed=ok',
            ],
            'metabox'=>true,
        ]);
    }
    
    
    public function get_listFailed(Request $request){
        return view('templates.pages.page', [
            'title'=>'Fila de Processos - Falha',
            'content'=>function() use($request){
                return $this->content_listFailed($request);
            },
        ]);
    }
    
    
    
    public function get_show(Request $request, $id){
        $model = $this->jobModel->find($id);
        if(!$model)return 'Falha ao localizar registro';
        
        return view('templates.pages.page', [
            'title'=>'Fila Processo #'.$id .' <span style="margin-left:20px;" class="btn btn-xs btn-primary">Pendente / Execução</span>',
            'content'=>function() use($model,$request){
                return view('templates.ui.view',[
                    'data'=>[
                        'queue'=>['title'=>'Fila','value'=>$model->queue],
                        'payload'=>['title'=>'Trabalho','value'=>function() use($model){
                            $v=$model->payload;
                            try{$v=json_decode($v);}catch(\Exception $e){}
                            dump($v);
                            
                        }],
                        'attempts'=>['title'=>'Tentativas','value'=>($model->attempts?$model->attempts:'0')],
                        'reserved_at'=>['title'=>'Processamento','value'=>function() use($model){ $v=$model->reserved_at;return $v?$v:'-'; }],
                        'created_at'=>['title'=>'Cadastro','value'=>$model->created_at,'type'=>'datetime'],
                    ],
                ]);
            },
        ]);
    }
    
    
    
    public function get_showFailed(Request $request, $id){
        $model = $this->JobFailedModel->find($id);
        if(!$model)return 'Falha ao localizar registro';
        
        return view('templates.pages.page', [
            'title'=>'Fila Processo - Falha #'.$id .' <span style="margin-left:20px;" class="btn btn-xs btn-danger">Falha</span>',
            'content'=>function() use($model,$request){
                return view('templates.ui.view',[
                    'data'=>[
                        'connection'=>['title'=>'Conexão','value'=>$model->connection],
                        'queue'=>['title'=>'Fila','value'=>$model->queue],
                        'payload'=>['title'=>'Trabalho','value'=>function() use($model){
                            $v=$model->payload;
                            try{$v=json_decode($v);}catch(\Exception $e){}
                            dump($v);
                        }],
                        'exception'=>['title'=>'Exceção','value'=>function() use($model){
                            foreach(explode("\n", $model->exception) as $line){
                                echo '<p>'.$line.'</p>';
                            }
                            
                        }],
                        'failed_at'=>['title'=>'Data do Erro','value'=>$model->failed_at],
                    ],
                ]);
            },
        ]);
    }
    
    
    
    public function remove(Request $request){
        $data = $request->all();
        $r = $this->destroy($data['id'],($data['failed']??false)=='ok');
        return $r;
    }
    
    
    private function destroy($id,$is_tbl_failed=false){
        try{
            if($is_tbl_failed){
                $model = $this->JobFailedModel->find($id);
            }else{
                $model = $this->jobModel->find($id);
            }
            if($model)$model->delete();
            $r=['success'=>true,'msg' => 'Registro deletado'];
        } catch (\Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        return $r;
    }
    
}

