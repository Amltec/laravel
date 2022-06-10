<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;
use App\Models\Term;
use App\Utilities\ValidateUtility;
use Gate;
use App\Services\TermService;

class TermsController extends SuperAdminBaseController{
    private $termModel;
    
    
    public function __construct(Term $termModel) {
        $this->termModel = $termModel;
    }
    
    
    public function index(Request $request){
        if(!Gate::allows('dev'))return self::redirectUserDenied();
        
        $prefix = \Config::adminPrefix();
        //$terms = $this->termModel->countTaxs()->orderBy('term_title', 'asc');
        $terms = $this->termModel->orderBy('term_title', 'asc');
        
        //filtros
        $filters=[];
        foreach($filters as $f){
            $filters[$f]=$request->input($f);
        }
        
        
        $terms = $terms->paginate(15);
        
        //o parâmetro action=rdcount serve apenas para validar o IF abaixo
        if($terms->count()==1 && $request->input('action')=='rdcount'){
            //com apenas 1 registro, redireciona para a taxonomia diretamente
            return redirect()->route($prefix.'.taxonomy.index',$terms->first()->id);
            
        }else{
            return view('templates.pages.page', [
                'title'=>'Grupo de Marcadores',
                'description'=>'<strong>Observação: precisa ser vinculado manualmente via programação</strong>',
                'toolbar'=>function()use($prefix){return view('templates.components.button',['title'=> '+ Grupo','color'=>'primary','href'=>route($prefix.'.terms.create')]);},
                'content'=>function() use($terms,$prefix){
                    return view('templates.ui.auto_list',[
                        'list_class'=>'table-striped',// table-hover
                        'data'=>$terms,
                        'columns'=>[
                            'id'=>'ID',
                            'term_title'=>'Título',
                            'term_description'=>'Descrição',
                            'area_name'=>'Relacionado',
                            //'taxs_count'=>'Total'
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
                            'click'=>function($reg) use($prefix){return route($prefix.'.terms.edit',$reg->id);},
                            'remove'=>route($prefix.'.app.remove','terms'),
                        ],
                        'field_click'=>'term_title',
                        'metabox'=>true,
                    ]);
                },
            ]);
        }
    }
    
    
   
    public function create($id=null){
        if(!Gate::allows('dev'))return self::redirectUserDenied();
        return $this->formHtml();
    }
    
    
    private function formHtml($term=null){
        $prefix = \Config::adminPrefix();
        
        return view('templates.pages.page', [
            'dashboard'=>[
                'bt_back'=>true,
                'route_back'=>route($prefix.'.terms.index')
            ],
            'title'=>($term ? 'Grupo de Marcador <small>#'.$term->id.'</small>': 'Novo Grupo de Marcador'),
            'content'=>function() use($term,$prefix){
                return view('templates.ui.auto_fields',[
                    'layout_type'=>'horizontal',
                    'form'=>[
                        'url_action'=> (isset($term)?route($prefix.'.terms.update',$term->id):route($prefix.'.terms.store')),
                        'data_opt'=>[
                            'focus'=>true,
                        ],
                        'bt_save'=>true,
                        'autodata'=>$term??false,
                        'url_success'=> $term ? null : route($prefix.'.terms.edit',':id')
                    ],
                    'metabox'=>true,
                    'autocolumns'=>[
                        'term_title'=>['label'=>'Título','maxlength'=>50,'class_group'=>'require'],
                        'term_singular_title'=>['label'=>'Título no Singular','maxlength'=>50,'class_group'=>'require','attr'=>'onchange=\'var o=$(this).closest("form").find("[name=term_short_title]");if(o.val()=="")o.val(this.value);\''],
                        'term_short_title'=>['label'=>'Título Curto (singular)','maxlength'=>50,'class_group'=>'require'],
                        'term_description'=>['label'=>'Descrição','type'=>'textarea','maxlength'=>255],
                    ],
                ]);
            }
        ]);
    }
    
    
    
    public function store(Request $request,$id=null){
        if(!Gate::allows('dev'))return self::redirectUserDenied();
        return TermService::add($request->all(),$id);
    }
    
    public function show($id){}
    
    public function edit($id){
        if(!Gate::allows('dev'))return self::redirectUserDenied();
        return $this->formHtml($this->termModel->find($id));
    }
    
    
    public function update(Request $request, $id){
        return $this->store($request,$id);
    }
    
    
    
    /**
     * Remove or restore
     */
    public function destroy(Request $request){
        return TermService::del($request->input('id'));
    }
}
