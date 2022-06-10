<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;
use App\Models\FileExtractText;
use App\Http\Controllers\WSFileExtractTextController;

class FilesExtractController extends SuperAdminBaseController{
    private $fileExtractModel;
    
    private static $statusLabel = ['0'=>'Não iniciado','a'=>'Em andamento','e'=>'Erro','f'=>'Finalizado'];
    
    public function __construct(FileExtractText $fileExtractModel) {
        $this->fileExtractModel = $fileExtractModel;
    }
    
    public function index(){
        $model = $this->fileExtractModel->orderBy('id', 'desc')->paginate(50);
        $prefix = \Config::adminPrefix();
        
        return view('templates.pages.page', [
            'title'=>'Extração de Arquivos',
            'toolbar'=>function()use($prefix){
                return \Auth::user()->user_level=='dev' ? '<a class="btn btn-primary" href="'. route('super-admin.app.get',['dev','view','?pag=pdftext-test']) .'">Adicionar</a>'  : ''; 
            },
            'content'=>function() use($model,$prefix){
                return view('templates.ui.auto_list',[
                    'list_id'=>'process_robot_list',
                    'list_class'=>'table-striped',// table-hover
                    'data'=>$model,
                    'columns'=>[
                        'id'=>'ID',
                        'area'=>['Área','value'=>function($v,$reg){ return $reg->area_name . ($reg->area_name!='manual'?' #'. $reg->area_id : ''); }],
                        'created_at'=>'Cadastro',
                        //'engine'=>['Engine','value'=>function($v)use(){return $varsProcessRobot::$pdfEngines[$v];}],
                        'engine'=>'Engine',
                        'status'=>['Status','value'=>function($v){ return self::$statusLabel[$v]; }],
                        'view'=>['Arquivo','value'=>function($v,$reg){
                            return '<a class="fa fa-file-pdf-o margin-r-5" target="_blank" title="Visualizar PDF" href="'.$reg->file_url.'"></a> '.
                                   ($reg->status=='f' ? '<a class="fa fa-file-text-o" target="_blank" title="Visualizar TXT" href="'. route('super-admin.app.get',['files_extract/view_txt','?id=2']) .'"></a>' : '');
                        }],
                    ],
                    'options'=>[
                        'checkbox'=>true,
                        'select_type'=>2,
                        'pagin'=>true,
                        'confirm_remove'=>true,
                        'toolbar'=>true,
                        'search'=>false,
                        'regs'=>false,
                        'list_remove'=>false
                    ],
                    'routes'=>[
                        'remove'=>route($prefix.'.app.remove','files_extract'),
                    ],
                    'metabox'=>true,
                    'toolbar_buttons'=>[
                        ['title'=>false,'alt'=>'Forçar reprocessamento','icon'=>'fa-circle-o',
                            'attr'=>'onclick="if(confirm(\'Libera os registros com status=andamento, mas que estão demorando muito ou deram erro, para serem processados imediatamente.\nDeseja continuar?\'))awBtnPostData({url:\''. route($prefix.'.app.post',['files_extract','clear_locked']) .'\',data:{id:$(\'#process_robot_list\').triggerHandler(\'get_select\')},cb:function(r){if(r.success)window.location.reload();}},this);"',
                            'class'=>'j-show-on-select'
                        ],
                    ],
                ]);
            },
        ]);
    }
    
    /**
     * Limpa a trava de registros para que possam ser processados imediatamente pelo robô
     */
    public function post_clearLocked(Request $request){
        $ids = $request->input('id');
        if($ids)$this->fileExtractModel->whereIn('id',$ids)->update(['locked_at'=>null,'status'=>'0']);
        return ['success'=>true];
    }
    
    
    public function get_viewTxt(Request $request){
        $model = $this->fileExtractModel->find($request->input('id'));
        if($model){
            if($model->status=='f'){
                return '<html><style>body{margin:0;}textarea{width:100%;height:100%;resize:none;border:0;outline:none;}</style><body><textarea readonly="readonly">'.$model->file_text.'</textarea></body></html>';
            }else{
                return 'Não disponível - Status='. strtoupper($model->status);
            }
        }else{
            return 'Erro ao localizar registro';
        }
    }
    
    public function remove(Request $request){
        $data = $request->all();
        $r = $this->destroy($data['id']);
        return $r;
    }
    
    private function destroy($id){
        try{
            $model = $this->fileExtractModel->find($id);
            if($model){
                $p=$model->file_path;
                if($model->area_name=='manual'){//se <> 'manual' então a classe responsável por gerar este registro na tabela 'process_robot' é que deve fazer a remoção do arquivo
                    if(file_exists($p))unlink($p);
                }
                $model->delete();
            }
            $r=['success'=>true,'msg' => 'Registro deletado'];
        } catch (\Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        return $r;
    }
}