<?php
/*
 * Controller das taxonomias.
 * Todas as funções vem a partir das rotas.
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tax;
use App\Models\Term;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ColorsUtility;
use App\Services\TaxsService;

class TaxsController extends Controller{
    private $taxModel;
    private $termModel;
    
    public function __construct(Tax $taxModel,Term $termModel) {
        $this->taxModel = $taxModel;
        $this->termModel = $termModel;
    }
    
    /**
     * Exibe uma lista de termos relacionado a conta para seleção.
     * Caso exista mais mais de um, será redirecionado automaticamente para o método index()
     */
    public function get_selectTerm(){
        $prefix = \Config::adminPrefix();
        $terms = $this->termModel->countTaxs()->get();
        if(count($terms)==1){//redireciona automaticamente
            return \Redirect::to( route($prefix.'.taxonomy.index',$terms[0]->id) )->send();
        };
        
        return view('templates.pages.page', [
            'title'=>'Selecione o Grupo',
            'content'=>function() use($terms,$prefix){
                return view('templates.ui.auto_list',[
                    //'list_class'=>'table-striped',
                    'data'=>$terms,
                    'columns'=>[
                        'term_title'=>['Grupo','value'=>function($v,$reg) use($prefix){
                            return '<a href="'. route($prefix.'.taxonomy.index',$reg->id) .'"><strong>'.$v.'</strong></a>'.
                                    ($reg->term_description ? '<br>'.$reg->term_description:'');
                        }],
                        'taxs_count'=>'Itens',
                        'ref'=>['Ref.','value'=>function($v,$reg){
                            return $reg->area_name ? $reg->area_name.' #'.$reg->area_id : '-';
                        }],
                    ],
                    'options'=>[
                        //'header'=>false,
                        'select_type'=>2
                    ],
                    'routes'=>[
                        'click'=>function($reg)use($prefix){return route($prefix.'.taxonomy.index',$reg->id);},
                    ],
                    'metabox'=>true,
                ]);
            }
        ]);
    }
    
    
    
    public function index(Request $request,$term_id){
        //captura todos os registros
        $term = $this->termModel->find($term_id);
        if(!$term){exit('Termo não encontrado');}
        
        $prefix = \Config::adminPrefix();
        
        return view('templates.pages.page', [
            'title'=>$term->term_title,
            'content'=>function() use($term){
                return $this->createlList($term);
            }
        ]);
    }
    
    /**
     * Retorna ao view da lista de taxonomia
     */
    public function createlList($term,$auto_list_opt=[]){
        $taxs = $this->getTaxList($term->id,[
            'merge_list'=>true,//para trazer todos os resultados em uma única lista
            'paginate'=>100
        ]);
        
        $prefix = \Config::adminPrefix();
        
        $params=[
            'list_class'=>'table-striped',// table-hover
            'data'=>$taxs,
            'columns'=>[
                //'id'=>'ID',
                'tax_title'=>['Título','value'=>function($v,$reg){
                    $opt = $reg->tax_opt;
                    return  '<span>'.
                                '<span class="fa '.($opt['icon']?$opt['icon']:'fa-tags').'" style="margin-right:10px;xfont-size:0.8em;'. ColorsUtility::getTextColor($opt['color']) .'"></span>'.
                                '<span class="strong">'.$reg->tax_title.'</span>'.
                            '</span>';
                }],
                'tax_description'=>'Descrição',
                'tax_relations_count'=>['Relacionados','value'=>function($v,$reg){ return $reg->relations->count(); }],
                'tax_hide'=>['Oculto','value'=>function($v){ return $v?'Sim':'-'; }],
                //'tax_level'=>'Nível',
            ],
            'options'=>[
                'checkbox'=>true,
                'select_type'=>2,
                'pagin'=>true,
                'confirm_remove'=>true,
                'toolbar'=>true,
                'search'=>false,
                'regs'=>false,
                'list_remove'=>false,
                'order'=>true,
            ],
            'routes'=>[
                'click'=>function($reg)use($term,$prefix){return route($prefix.'.taxonomy.edit',[$term->id,$reg->id]);},
                'add'=>route($prefix.'.taxonomy.create',$term->id),
                'remove'=>route($prefix.'.app.remove',['taxs','term_id='.$term->id]),
            ],
            //'field_click'=>'tax_title',
            'open_modal'=>[
                'title_add'=>'Adicionar '.$term->term_singular_title,
                'title_edit'=>'Editar '.$term->term_singular_title,
            ],
            'row_opt'=>[
                'class'=>function($reg){
                    if($reg->tax_hide)return 'text-gray';
                }
            ],
            'metabox'=>true,
        ];
        
        $params = FormatUtility::array_merge_recursive_distinct($params, $auto_list_opt);
        
        return view('templates.ui.auto_list',$params);
    }
    
    
    /**
     * Carrega a página de adição/edição dos dados via ajax
     */
    public function create($term_id,$id=null){
        $term = $this->termModel->find($term_id);
        if(!$term){exit('Termo não encontrado');}
        
        $prefix = \Config::adminPrefix();
        
        $tax_list = $this->getTaxList($term_id,[
            'merge_list'=>true,//para trazer todos os resultados em uma única lista
            'id_not'=>($id?$id:null)
        ]);
        
        $tax = null;
        if($id){
             $tax = $this->taxModel->find($id);
        }
        
        
        if($tax){
            //atualiza os campos abaixo para ficar compatível com o formulário
            if(empty($tax->tax_opt))$tax->tax_opt='{"color":null,"icon":null}';
            $tax->tax_opt=json_decode($tax->tax_opt,true);
            $tax->color=$tax->tax_opt['color'];
            $tax->icon=$tax->tax_opt['icon'];
        }
        
        $params=[
            'view'=>'templates.ui.auto_fields',
            'data'=>[
                'layout_type'=>'horizontal',
                'form'=>[
                    'url_action'=> ($tax ? route($prefix.'.taxonomy.update',[$term->id,$tax->id]) : route($prefix.'.taxonomy.store',$term->id)),
                    'data_opt'=>[
                        'focus'=>true,
                        'onSuccess'=>'@function(r){ if(r.success)window.location.reload(); }',
                    ],
                    'bt_save'=>true,
                    'autodata'=>$tax??false,
                ],
                //'metabox'=>true,
                'autocolumns'=>[
                    'tax_title'=>['label'=>'Título','maxlength'=>50,'class_group'=>'require'],
                    'tax_description'=>['label'=>'Descrição','type'=>'textarea','rows'=>'5'],
                    'tax_id_parent'=>['label'=>$term->term_short_title.' pai','type'=>'select',
                        'list'=>[''=>'']+$tax_list->pluck('tax_title','id')->toArray()
                    ],
                    'color'=>['label'=>'Cor','type'=>'colorbox'],
                    'tax_hide'=>['label'=>'Ocultar','type'=>'sim_nao','default'=>'n'],
                ],
            ],
        ];
        if($tax)$params['data']['autocolumns']['tax_order']=['label'=>'Ordem','type'=>'number','width'=>100];
        return view('templates.ajax_load',$params);
    }
    
    
    
    public function store(Request $request,$term_id){
        $account_id = \Config::accountID();
        $tax = TaxsService::addTax($account_id,$request->all(),$term_id);
        if(!$tax['success'])return $tax;
        
        if($tax['action']=='add'){
            $tax['url_edit'] = route(\Config::adminPrefix().'.taxonomy.index',$term_id);
        }
        
        return $tax;
    }
    
    public function show($term_id,$id){}
    
    public function edit($term_id,$id){
        return $this->create($term_id,$id);
    }
    
    
    public function update(Request $request, $term_id, $id){
        $account_id = \Config::accountID();
        return TaxsService::editTax($account_id,$request->all(),$term_id,$id);
    }
    
    
    /**
     * Remove or restore
     * Valores esperados: action:trash|restore|remove, id
     */
    public function remove(Request $request){
        $data = $request->all();
        $r = $this->destroy($data['term_id'],$data['id']);
        return $r;
    }
    
    public function destroy($term_id,$id){
        $account_id = \Config::accountID();
        return TaxsService::delTax($account_id, $term_id, $id);
    }
    
        
    //***************** funções adicionais *************
    
    /**
     * Retorna a lista de taxonomias considerando os níveis de hierarquia.
     * Mais parâmetros na função TaxsService::getTaxList()
     */
    public function getTaxList($term_id,$params=array(),$ret='model'){
         return TaxsService::getTaxList($term_id,$params,$ret);
    }
    
    
    /**
     * Retorna aos dados do termo
     * return array object
     */
    public function getTerm($term_id){
        return $this->termModel->find($term_id);
    }
    
    
    
    
    /**
     * Posts gerais de taxonomias para relação automática com outras tabelas.
     * @param Request $request - valores esperados:
     *      string action  - nome da ação. Valores: add_relation, del_relation
     *      int tax_id 
     *      string area_name
     *      int area_id   - pode ser: string|int|array - ex: '1,2,3' | 1 | [1,2,3]
     * @return array[success,msg,...]
     */
    public function post(Request $request){
        $data = $request->all();
        $tax_id = $data['tax_id'];
        $area_name = $data['area_name'];
        $area_id = $data['area_id'];
        if(!$area_name)return ['success'=>false,'msg'=>'Erro de parâmetro area...'];
        
        $taxModel = $this->taxModel->find($tax_id);
        if(!$taxModel){
            $r = ['success'=>false,'msg'=>'Taxonomia não existe'];
        }
        
        if(!is_array($area_id))$area_id=explode(',',$area_id);
        switch($data['action']??null){
        case 'add_relation':
            foreach($area_id as $a_id){
                $r = TaxsService::addRelation($tax_id, $area_name, $a_id);
            }
            break;
        
        case 'edit_relation':
            foreach($area_id as $a_id){
                $r = TaxsService::delRelation(['tax_id'=>$tax_id, 'area_name'=>$area_name, 'area_id'=>$a_id]);
                if($r['success'])$r = TaxsService::addRelation($tax_id, $area_name, $a_id);
            }
            break;
        
        case 'del_relation':
            foreach($area_id as $a_id){
                $r = TaxsService::delRelation(['tax_id'=>$tax_id, 'area_name'=>$area_name, 'area_id'=>$a_id]);
            }
            break;
        
        default:
            $r = ['success'=>false,'msg'=>'Erro de parâmetro action'];
        }
        
        return $r;
    }
    
    
}
