<?php
/*
 * Exemplos gerais de sistema
 */

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tax;
use App\Utilities\FormatUtility;
use App\Services\FilesService;
use URL;
use Auth;
                    
                    
class ExampleController extends \App\Http\Controllers\Controller{
    
    
    public function __construct(User $UserModel){
        //$this->userModel = $UserModel;
        if(Auth::user()->user_level!='dev')exit('Acesso negado');
    }
    
    
    public function index(Request $request){
        //return $this->testMetadata();
        //return $this->testTax();
        
        $name = $request->input('name');//mehod get
        
        if($name=='page01'){
            
            echo view('templates.pages.page',[
                'title'=>'Título - página criada diretamente pelo controller',
                'title_bar'=>'Barra de Título',
                'description'=>'Descrição adicional',
                'content'=> 'Página criada pelo template <strong>templates.pages.page</strong> diretamente pelo controller<br>'.
                            'Conteúdo HTML principal da página',
                'toolbar'=>'HTML qualquer',
                'head'=>'<script>console.log("html head adicionado");</script>',
                'bottom'=>'<script>console.log("html bottom adicionado");</script>',
            ]);
            
        }else if($name=='data-select-ex'){
            //lista de exemplo para retorno em ajax
            return [
                ['id'=>'001','text'=>'Itapeva'],
                ['id'=>'002','text'=>'Itapetininga'],
                ['id'=>'003','text'=>'Sorocaba'],
                ['id'=>'004','text'=>'São Paulo'],
                ['id'=>'005','text'=>'CapãoBonito'],
                ['id'=>'006','text'=>'Itararé'],
                ['id'=>'007','text'=>'Guiapiara'],
                ['id'=>'008','text'=>'Itapetininga'],
                ['id'=>'009','text'=>'Buri'],
            ];
            
            
        }else if($name=='html'){
            //Função de exemplo de retorno de html 
            $html='Lorem ipsum dolor sit amet, <strong>consectetur adipiscing</strong> elit. Nihil enim iam habes, quod ad corpus referas; Quod cum dixissent, ille contra. Nunc agendum est subtilius. Quid, quod res alia tota est? Virtutis, magnitudinis animi, patientiae, fortitudinis fomentis dolor mitigari solet. Duo Reges: constructio interrete. Estne, quaeso, inquam, sitienti in bibendo voluptas? Itaque rursus eadem ratione, qua sum paulo ante usus, haerebitis.'.
                  '<script>console.log("script executado a partir do carregamento ajax com sucesso")</script>';
            return ['success'=>true,'html'=>$html];
        
            
        }else if($name=='list02'){
                    //carrega a lista de dados de exemplo (veja mais detalhes do exemplo no arquivo views\super-admin\_examples\list02.blade.php
            
                    $files_filter = [
                        'regs'=>(_GETNumber('regs')??3),
                        'is_trash'=>_GET('is_trash')=='s',
                        'search'=>_GET('q'),
                    ];
                    if($request->input('filter_id'))$files_filter['id']=$request->input('filter_id');
                    $files = \App\Services\FilesService::getList($files_filter);

                    //cria uma coluna adicional para simular agrupamento de dados
                    foreach($files['files'] as $file){
                        $file->date_group = date("F \/ Y",strtotime($file->created_at));
                    }
                    
                    $list_params=[
                        'list_id'=>'my_table_id1',
                        'list_class'=>'table-striped',// table-hover

                        'data'=>$files['files'],
                        'columns'=>[
                            'id'=>'ID',
                            'file_title'=>'Título',
                            'file_size'=>[
                                    'Tamanho',
                                    'value'=>function($val,$reg=null){ return FormatUtility::bytesFormat($reg ? $reg->file_size : $val); },
                                    'calc_total'=>function($val,$reg){return $val+=$reg->file_size;} //função de cálculo de total e subtotal
                                ],
                            'folder'=>'Pasta',
                            'date_group'=>'Data2',//Custom column
                        ],
                        'options'=>[
                            'collapse'=>true,'checkbox'=>true,'pagin'=>true,'reload'=>true,'select_type'=>2,'toolbar'=>true
                        ],
                        'routes'=>[
                            'load'=>route('super-admin.app.index','example').'/?name=list02',
                            'click'=>function($reg){return ($reg->__lock_del?'#':'mypage/'.$reg->id.'/');},
                            'collapse'=>route('super-admin.app.index','example').'/?name=html',
                            'remove'=>route('super-admin.app.index','example'),
                        ],
                        'field_group'=>'date_group',
                        'field_click'=>'file_title',
                        'row_opt'=>[
                            'lock_del'=>[22],
                        ],
                        'metabox'=>[
                            'title'=>'Minha lista de dados',
                            //'is_padding'=>false
                        ],
                    ];
            
            //if(request()->wantsJson()){//return request json
            if($request->ajax()){
                
                //O parâmetro 'is_ajax_load' é opcional pois já é capturado automaticamente dentro da view
                //Serve para forçar o carregamento no modo ajax ou não (true|false)
                //$list_params['is_ajax_load']=true;
                
                return view('templates.ui.auto_list',$list_params);
            }else{//return view
                return view('super-admin._examples.'.$name,['list_params'=>$list_params]);
            }
        
            
        }else if($name=='listfiles_01'){
                /*** 
                 * Exemplo que carrega a lista de arquivos do exemplo /super-admin/_examples?name=filemanager01 nas opções:
                 *      - botão 'reload' da lista
                 *      - inserção após o upload
                 *      (veja mais detalhes do exemplo no arquivo views\super-admin\_examples\filemanager01.blade.php)
                 * 
                 * Obs: Neste exemplo, usado somente para requisições ajax, 
                 *      e abaixo é carregado o próprio templates.ui.files_list de lista de arquivos, 
                 *      pois nele já contém o templates.ui.auto_list que processa de forma automática 
                 *      e correta quando a requisição é ajax.
                 * 
                 * É importante ressatar que os parâmetros abaixo são parciais em relação ao exemplo (/super-admin/_examples?name=filemanager01) 
                 *      que contém mais opções (servem apenas como referência ao programador)
                 */
                return view('templates.ui.files_list',[
                            'files'=>FilesService::getList([
                                //filtros para o ajax
                                'id'=> _GET('filter_id'), //somente somente 1 (que é executado após o upload)
                                'regs'=>(_GETNumber('regs')??5),
                                'search'=>_GET('q'),
                                'folder'=> _GET('folder'),
                                'private'=> _GET('access')=='private',
                                'is_trash'=> _GET('is_trash')=='s',
                                'taxs_id'=> _GET('taxs_id'),
                            ]),
                            //estes parâmetros são necessários para ficar compatível com o exemplo
                            'auto_list'=>[
                                'routes'=>[
                                    'click'=>function($reg){
                                        return ($reg->is_deleted?'javascript:alert("Este registro está #'. $reg->id .' excluído");void[0]':$reg->getUrl().'/');
                                    },
                                ],
                                'taxs'=>[
                                    1=>[
                                        'show_list'=>'file_title',
                                        //'button'=>['title'=>'Texto']
                                    ]
                                ]
                            ],
                        ]);
            
             
        }else if(!empty($name)){
            //carrega as views a partir do nome
            return view('super-admin._examples.'.$name);
        
           
        }else{
            //carrega os links de exemplos
            $r= '<span style=color:red>Obs: nem todos os exemplos estão funcionais nestes sistema '.
                    '<br><small>(são os exemplos autofield e as tabelas padrões (que não existem no db nesta aplicação))</small>'.
                    '<br><small>demais exemplos em http://localhost/aurlwebapp/aurlwebapp-v01/public/admin/example</small>'.
                '</span>'.
                '<h3>Exemplos de funções do sistema</h3>'.
                '<a href="https://adminlte.io/themes/AdminLTE/index2.html">AdminLTE</a><br>'.
                '<a href="https://fontawesome.com/icons">Fonts Awesome</a><br>'.
                '<a href="?name=page01">Páginas pelo Controller</a><br>'.
                '<a href="?name=tax_page_ex">Taxonomias</a><br>'.
                '<a href="?name=tax_page_bts_ex">Taxonomias botões</a><br>'.
                '<a href="?name=tax_page_tags">Taxonomias adicionando tags</a><br>'.
                '<a href="?name=forms01">Forms 1 - tipos de campos</a><br>'.
                '<a href="?name=forms02">Forms 2 - organização de campos</a><br>'.
                '<a href="?name=forms03">Forms 3 - opções autofields</a><br>'.
                '<a href="?name=forms04">Forms 4 - formulários</a><br>'.
                '<a href="?name=forms05">Forms 5 - lista de blocos dinâmicos de campos</a><br>'.
                '<a href="?name=forms06">Forms 6 - criação do formulário direto pelo componente</a><br>'.
                '<a href="?name=editor01">Editor</a><br>'.
                '<a href="?name=editorjs01">Editorjs</a><br>'.
                '<a href="?name=editor-view01">Editor com Visualizador <span style=color:red>Em análise / desenvolvimento...</span></a><br>'.
                '<a href="?name=buttons">Botões</a><br>'.
                '<a href="?name=buttons_postdata">Botões com Post Data</a><br>'.
                '<a href="?name=menus">Menus</a><br>'.
                '<a href="?name=metaboxs">Metabox</a><br>'.
                '<a href="?name=autofield_combinacoes_form01 ">Template Autofield - Testando combinações de Formulários</a><br>'.
                '<a href="?name=table_default_contacts">Tabela Padrão: Contatos</a><br>'.
                '<a href="?name=table_default_addresses">Tabela Padrão: Endereços</a><br>'.
                '<a href="?name=table_default_mails">Tabela Padrão: E-mails</a><br>'.
                '<a href="?name=table_default_phones">Tabela Padrão: Telefones</a><br>'.
                '<a href="?name=table_default_posts">Tabela Padrão: Posts</a><br>'.
                '<a href="?name=list01">Lista de dados 01</a><br>'.
                '<a href="?name=list02">Lista de dados 02 - usando ajax</a><br>'.
                '<a href="?name=list03">Lista de dados 03 - taxonomias - código manual</a><br>'.
                '<a href="?name=list04">Lista de dados 04 - taxonomias - automático</a><br>'.
                '<a href="?name=tree01">Diretórios</a><br>'.
                '<a href="?name=uploads">Uploads (tabela files)</a><br>'.
                '<a href="?name=uploads-zone">Uploads Zone (tabela files)</a><br>'.
                '<a href="?name=uploads-direct">Uploads (arquivos diretos (sem tabela files))</a><br>'.
                '<a href="?name=uploads-box">Uploads Box - Campo completo de upload</a><br>'.
                '<a href="?name=filemanager-info">Informações da Classe de Arquivos</a><br>'.
                '<a href="?name=filemanager01">Gerenciador de Arquivos - Upload na Janela de Arquivos</a><br>'.
                '<a href="?name=filemanager01-ajax">Gerenciador de Arquivos - Abrindo em Janela Modal</a><br>'.
                '<a href="?name=filemanager02">Gerenciador de Arquivos - Seleção na Janela de Arquivos</a><br>'.
                '<a href="?name=fileview01">Visualizador de Arquivos</a><br>'.
                '<a href="?name=view01">Visualizador de dados 01 - Tipo de dados</a><br>'.
                '<a href="?name=view02">Visualizador de dados 02 - Views Recursivas</a><br>'.
                '<a href="?name=view03">Visualizador de dados 03 - De objeto Models</a><br>'.
                '<a href="?name=view04">Visualizador de dados 04 - Customização visual</a><br>'.
                '<a href="?name=view05">Visualizador de dados 05 - Extração completa de dados da model (taxonomias e metadados)</a><br>'.
                '<a href="?name=view06">Visualizador de dados 06 - Exemplo de filtros de campos</a><br>'.
                '<a href="?name=view07">Visualizador de dados 07 - Exemplo com metabox e botões</a><br>'.
                '<a href="?name=tab01">Tabs</a><br>'.
                '<a href="?name=accordion01">Accordions</a><br>'.
                '<a href="?name=toolbar01">Barra de Ferramentas</a><br>'.
                '<a href="?name=ajax01">Ajax - Acessando recursos por ajax</a><br>'.
                '';
            return $r;
        }
    }
    
    
    
    /*
     * Função de exemplo padrão para salvamento de dados com o autofields.
     */
    public static function post_testSaveAuto(Request $request){
        $data = $request->all();
        
        if($data['test_json']??null=='ok'){sleep(2);return ['success'=>true,'data'=>$data];}//apenas uma linha de teste para retorno json
        //if($data['test_json']??null=='ok'){sleep(2);return ['success'=>false,'msg'=>'mensagem personalizada de erro','data'=>$data];}//apenas uma linha de teste para retorno json
            
        dd($data);
        
        $controller=$data['_tmp_controller_test'];
        $id=$data['_tmp_table_id']??'0';
        $param=$data['_tmp_table_param']??'[]';
        $param=(array)json_decode($param);
        //dd($data);
        unset($data['_tmp_controller_test'],$data['_tmp_table_id'],$data['_tmp_table_param']);
        
        $r = \App::call('App\\Services\\'.  $controller, [
            'data'=>$data,
            'mail_id'=>$id,
            'param'=>$param
        ]);
        
        //dd($r);
        
        return $r;
    }
    
    
    
    
    /*
     * Função de exemplo de exclusão de dados por ajax
     * Recebe pelo método POST
     */
    public static function post_testDelAuto(Request $request){
        $data = $request->all();
        //dd('del',$data);
        
        $r=['success'=>true,'msg'=>'Excluído com sucesso'];
        return $r;
    }
    
    /*
     * Função de exemplo de exclusão de dados por ajax (
     * Recebe pelo método PUT
     */
    public function remove(Request $request){
        $data = $request->all();
        dd('del',$data);
        
        $r=['success'=>true,'msg'=>'Excluído com sucesso'];
        return $r;
    }
    
    
    
    
    
    /*
     * Função de exemplo padrão postagem de dados do editor, com sanitização de dados
     */
    public static function post_testSaveEditor(Request $request){
        $data = $request->all();
        
        $data['editor01'] = \App\Utilities\HtmlUtility::sanitizeHTML($data['editor01']);
        $data['editor02'] = \App\Utilities\HtmlUtility::sanitizeHTML($data['editor02']);
        $data['editor03'] = \App\Utilities\HtmlUtility::sanitizeHTML($data['editor03']);
        $data['editor04'] = \App\Utilities\HtmlUtility::sanitizeHTML($data['editor04']);
        
        dd($data);
        
    }
    
    
    //Carregamento da lista de arquivos dentro da uma janela modal
    public function get_filemangerModal(Request $request){
        $list_params = [
            'files_opt'=>[
                'uploadSuccess'=>'route_load', //dispara a rota 'load' ajax
                'fileszone'=>['maximize'=>'.j_files_list_zone'],
                'metabox'=>[
                    'class'=>'j_files_list_zone'        //classe identificadora da zona de upload
                ],
                'modeview_img'=> _GET('modeview')=='',
                
                //altera para o modo de selecionar arquivos
                'mode_select'=>true,
                
                //visualização de arquivos por janela modal
                'file_view'=>'modal'
            ],
            'auto_list'=>[
                'options'=>[
                    //'select_type'=>1,//somente 1 seleção por vez
                    'checkbox'=>false,
                ],
                'routes'=>[
                    'load'=>route('super-admin.app.get',['example','filemangerModal']),
                    'click'=>function($reg){
                        return route('admin.file.view',[$reg->id,$reg->file_name_full,'view=modal&rd='.urlencode(\Request::fullUrl()) ]) ;
                    },
                    'remove'=>route('admin.file.remove'),
                ],
                'class'=>'j-filemanager-wrap',
                //'is_ajax_load'=>false,//como será carregado em uma janela modal, precisa ser ==false para que carregue todos os elementos UI
            ],
        ];
        if($request->input('load_type')=='modal'){//indica que é o primeiro carregamento em janela modal
            $list_params['auto_list']['is_ajax_load']=false; //=false para que carregue o template completo com todos os elementos UI
        }
        
                    
        //utiliza a view templates.ajax_load para carregar os recursos javascript corretamente
        return view('templates.ajax_load',['view'=>'templates.ui.files_list','data'=>$list_params]);
    }
    
    
    //Exemplo de abertura de formulário dentro da uma janela modal ajax (exemplo em /public/example?name=ajax01)
    public function get_ajaxForm01($param){
        return view('templates.ajax_load',[//utiliza a view templates.ajax_load para carregar os recursos javascript corretamente
            'view'=>'templates.ui.auto_fields',
            'data'=>[
                'form'=>[
                    'url_action'=>route('admin.app.post',['example','testSaveAuto']),
                    'data_opt'=>[
                        'focus'=>true,
                    ],
                    'bt_save'=>true,
                ],
                'autocolumns'=>[
                    'nome'=>['label'=>'Corretor','maxlength'=>100,'require'=>true],
                    'cpf_cnpj'=>['label'=>'CNPJ / CPF','maxlength'=>20,'require'=>true],
                    'status'=>['type'=>'select','label'=>'Status','list'=>[''=>'','a'=>'Normal','c'=>'Cancelado'],'require'=>true],
                    'cidades'=>['type'=>'select2','label'=>'Cidades','list'=>[''=>'','1'=>'Itapeva','2'=>'Itararé'],'require'=>true],
                    'descricao'=>['type'=>'editor','label'=>'Informações','template'=>'short', 'filemanager'=>true],
                ],
            ]
        ]);
    }
    
    
    
    /*
    public function testTax(){
        //add taxonomia
        //$r=$this->taxService->add([
        //    'term_id'=>1,
        //    'tax_title'=>'Jquery',
        //    //'tax_description'=>'As melhores mentes criativas',
        //    //'tax_id_parent'=>2,
        //]);
        dd('*',$r, $this->taxService->getError());
        
        //del tax
        /*$r=$this->taxService->del(10);
        dd('*',$r,$this->taxService->getError());
        
        
        //get tax
        //$r=$this->taxService->get(2);
        //foreach($r as $a){
        //    foreach($a->relations as $b){
        //        echo $b->area_name.'|'.$b->area_id.'<br>';
        //    }
        //}
        //dd($r);
        
        
        //add relation
        $tax_id=16;$user_id=9;
        //$r = $this->taxService->addRelation($tax_id,'users',$user_id);//pela classe de serviços
        $r=$this->userModel->addTaxRelation($tax_id,$user_id);//pela model usando a classe trait
        dd('*',$r);
        
        
        //del relation
        /*$r = $this->taxService->delRelation(['area_name'=>'users','area_id'=>4]);//pela classe de serviços
        dd('*',$r,$this->taxService->getError());
        
        
        //get relation area
        //$r = $this->taxService->getRelationByArea('users',3);//pela classe de serviços
        //foreach($r as $a){
        //    dd($a,$a->getAreaData());
        //    echo $a.'<br>';//->area_name.'|'.$b->area_id.'<br>';
        //}
        //dd($r);
         
        //get tax by user
        //$r=$this->userModel->getTax(3);//pela model usando a classe trait
        //foreach($r as $a){
        //    dd($a->tax);
        //}
        //dd($r);
        
        
        ////filter sql - scope
        //$r=$this->userModel->WhereTax([2,4]);
        //dd($r->toSql(),$r->getBindings());
        //dd($r->toSql(),$r->getBindings(),$r->get());
        
        return 'tax test ok';
    }*/
    
    
    
    
    /*public function testMetadata(){
        //ex get metadata
        //$r=$this->userModel->getMetaValue(18,'a');
        //$r=\App\Services\MetadataService::getValue('users', 18,'n');
        //dd($r);
        
        //filter metadata
        //$r=$this->userModel->WhereMetadata(['x__like'=>'%casa%']);
        //dd($r->toSql());
        //$r=$r->get()->toArray();
        //ddx($r,true);
        
        
        //$r=$this->userModel->find(11)->update(['nome'=>'1234']);
        //dd($r);
        
        return 'metadata test ok';
    }*/
    
    /*//página de visualização da taxonomia
    public function viewTaxPage(){
        $term_id = 1;
        return view('_test.tax_page_ex',[
            'term_id'=>$term_id
        ]);
    }*/
}
