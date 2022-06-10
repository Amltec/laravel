@extends('templates.admin.index')


@section('title')
@php
    echo 'Log da Baixa - '. $configProcessProd['title_long'] .' - <span style="font-size:0.8em;">Id</span> <large class="strong">'. $model->id . ($model->process_auto?'*':'') .'</large> ';
    echo $model->process_test?'<span class="label bg-orange" style="margin-left:10px;font-size:10px;">Teste</span>':'';

    if(Config::adminPrefix()=='super-admin'){
        $account_data = $model->account->getData();
        echo '<div style="display:inline-block;margin-left:70px;position:relative;top:-2px;">';
            if($account_data['logo_icon']??false){
                echo '<span style="top:9px;" class="account-logo-icon"><img src="'. $account_data['logo_icon'].'?'. $account_data['updated_at'] . '" /></span>';
            }
            echo '<span  class="label bg-navy" style="font-size:12px;position:relative;margin-left:5px;">'.$model->account->account_name.' #'. $model->account_id .'</span>';
        echo '</div>';
    }
@endphp
@endsection

@section('content-view')
@php
/*  Parâmetros esperados:
        $model
        $execsModel
        $fileInfo
        $configProcessNames
        $configProcessProd
        $robotModel
        $status_list
        status_color
        $user_logged_level
        $user_logged_id
        $thisClass

*/
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Services\PrSegService;
use App\Services\AutoFieldsService;
use App\ProcessRobot\VarsProcessRobot;
use App\Error;
//dd(VarsProcessRobot);





if(!function_exists('__PCAShowItem01')){
    function __PCfxv($n){//converte de name{1}|field para field_1
        if(strpos($n,'{')!==false){
            $a = (int)filter_var($n, FILTER_SANITIZE_NUMBER_INT);
            if($a)$n=substr($n,strpos($n,'|')+1) .'_'. str_replace(['{','}'],'',$a);
        }
        return $n;
    }
    function __PCALabel($field,$dadosLabel,$parcelasLabel,$prodLabel){//retorna ao label do campo
        $n=explode('_',$field);
        $x=count($n);
        if($x>1 && is_numeric($n[$x-1])){
            unset($n[$x-1]);
            $field=join('_',$n);
        }
        return $dadosLabel[$field] ?? $parcelasLabel[$field] ?? $prodLabel[$field] ?? $field;
    }
    function __PCAShowItem01($blockname, $dataarr,$field,$field_to_form,$ctrlChanged_user,$ctrlChanged_robo,$dataPdf,$execsArr,$execsFinal,$validate_not,$fields_ignore_show,$i=null){
        $tds='';
        $is_change_manual = ($ctrlChanged_user[$field]??'') == 'user';
        $is_change_quiver = ($ctrlChanged_robo[$field]??'') == 'robo';

        $row_apolice_check = false;
        //dump([$field,$is_change_quiver]);
        //dd($dataarr,$execsArr);

        foreach($dataarr as $col => $arr){
            $v = (string)($arr[$i ? $field.'_'.$i : $field] ?? '');
            $cls = '';
            $trcls = '';
            $attr = '';


            //Linhas de teste (não deletar)
            //if($col=='exec_final' && $field=='fpgto_n_prestacoes')dump([$col,$v,$is_change_quiver,$ctrlChanged_robo]);


            //está visualizando dados das execuções do robô
            //colunas 'exec_{N}' - captura os dados originais retornados para considerar a var 'status_code'
            //coluna 'quiver'    - armazena os dados mesclados considerando a última execução de 'exec_N'

            $this_execArr=null;
            $this_status_code='';
            if($col=='execfinal' || substr($col,0,5)=='exec_'){
                    $cls.=' col-quivexec';
                    if($col=='quiver' || $col=='execfinal'){
                        $this_execArr = $execsFinal[$blockname]??null;
                    }else{//exec_
                        $i_exec = substr($col,5);
                        $this_execArr = $execsArr[$i_exec-1][$blockname]??null;

                        $cls.=' col-execs';
                    }

                    if($this_execArr)$this_status_code=$this_execArr['_status_code']??'';
                    if($this_status_code){
                        $cls.=$this_status_code=='ok' || $this_status_code=='ok2' ? ' code-ok' : ' code-err';
                        $attr.=' data-status-code="'. $this_status_code .'"';


                        if($col=='execfinal'){
                            //obs: todo este trecho de código, existe para que na coluna agrupada de status da execução, para o caso de erro, exibe seta na classe da coluna se o valor é igual ou diferente (classe .equal-value, .diff-value)
                            if($this_status_code=='ok' || $this_status_code=='ok2'){
                                //nenhuma ação
                            }else{
                                $tmp_arr = $i ? ($this_execArr[$i][$field]??null) : ($this_execArr[$field]??null);
                                if($tmp_arr){
                                    $q_v0 = $tmp_arr[0];
                                    $p_v0 = $dataarr['manual'][ $i ? $field.'_'.$i : $field ];
                                    if($q_v0==$p_v0){//quer dizer que o valor do quiver é igual ao valor atual do pdf/manual, o que significa que o valor é igual
                                        $cls.=' equal-value';
                                    }else{
                                        $cls.=' diff-value';
                                    }
                                    //if(Auth::user()->user_level=='dev' && $field=='veiculo_ano_fab' )dump($field,$field,$tmp_arr,$q_v0,$p_v0 );
                                }
                            }
                        }
                    }
            }

            $is_changed=false;
            //remover?? if($field!='anexo_upl'){
                    if($col=='manual' && $is_change_manual && $v!=''){
                        $is_changed=true;

                    //}else if(($col=='execfinal' || substr($col,0,5)=='exec_') && $v!='' && strtolower($v)!='não alterado'){ //&& $is_change_quiver  //??analisando esta condição adicional
                        $is_changed=true;
                    }
            //remover??}
            if($is_changed)$cls.=' td-changed';

            if($col=='manual'){
                    //popover - data original pdf
                    $tmp=['',''];
                    $f = $field . ($i?'_'.$i:'');

                    $name=__PCfxv($field_to_form);
                    $a=$dataarr['manual'][$name]??'';
                    $b=$dataPdf[$name]??'';
                        $b_view = substr($field_to_form,-5)=='_code' ? ($dataPdf[__PCfxv(substr($field_to_form,0,strlen($field_to_form)-5))] ?? $b) : $b;   //no valor original de visualização, verifica se existe o campo ex de 'combustivel_code' para 'combustivel' que contém o valor formatado

                    if(($a!='' && $a!=$b) || $is_change_manual){
                        //if($field=='fpgto_1_prestacao_valor')dump($b,$b_view,$f,$name,$dataPdf);
                        if($field=='anexo_upl' && $col=='manual'){
                            $n= 'Valor no Pdf: <b>'. ($b!=''?$b_view:'Arquivo sem nome') .'</b>';
                        }else{
                            $n= 'Valor no Pdf: <b>'. ($b!=''?$b_view:'Não existe') .'</b>'.
                                '<br>Alterado: <b style=color:#3366ff>'.$a.'</b>';
                        }
                        $tmp[1] = $n; //content
                    }else{
                        $tmp[1] = 'Valor no Pdf: <b>'. ($b!=''?$b_view:'Não existe') .'</b>'; //content
                    }
                    $tmp[0] = ($dataarr['label'][$f]??''); //title

                    //quer dizer que existe um erro de validação neste caso (não bate com a regra de validação ocorrido durante a extração ou edição de dados)
                    if($validate_not && isset($validate_not[$f])){
                        if(strpos($cls,'code-err')===false)$cls.=' code-err';
                        $tmp[0] .= ' - <span class=text-red>Erro</span>';
                        $tmp[1] .= '<br><b class=text-red>'.$validate_not[$f].'</b>';
                        $attr.=' data-validate="'. $validate_not[$f] .'"';
                    }

                    $attr.=' title="'. $tmp[0] .' <a href=# class=close data-dismiss=alert style=\'position:relative;top:-3px;\'>&times;</a>" data-content="'. $tmp[1] .'" data-html="true" data-placement="right" data-trigger="manual" data-value-original="'. $b .'" data-value-view-original="'. $b_view .'"';

            }else{// if($col=='manual'){
                    $f = $field . ($i?'_'.$i:'');
                    $attr.=' title="'. ($dataarr['label'][$f]??'') .' <a href=# class=close data-dismiss=alert style=\'position:relative;top:-3px;\'>&times;</a>" data-content="'. htmlspecialchars($v) .'" data-html="true" data-placement="right" data-trigger="manual"';
            }


            /*if($this_status_code){
                if($this_status_code=='ok' || $this_status_code=='ok2'){
                    $v='<span class="fa fa-check text-green" style="font-size:0.8em;"></span>'. $v;
                }else{
                    $v='<span class="fa fa-warning text-red" style="font-size:0.8em;"></span>'. $v;
                }
            }*/


            //Linhas de teste (não deletar)
            //if($col=='execfinal' && $field=='fpgto_n_prestacoes')dump($field,$arr,$dataarr,$v);


            $tds.='<td data-col="'.$col.'" class="col-'. $col . $cls .'" '. $attr .'><div class="text-truncate">'. ($v!=''?$v:'-') .'</div></td>';

            if(strpos($trcls,'code-err')===false && strpos($cls,'code-err')!==false)$trcls.= ' tr-code-err';

            if($col=='apolice_check' && $v!='')$row_apolice_check=true;//quer dizer que nesta linha, existem dados verificados da segurado (da var dataApoliceCheck)

        }

        $cls=$trcls;
        if($is_change_manual)$cls.=' tr-changed-manual';
        if($is_change_quiver)$cls.=' tr-changed-quiver';
        if($is_change_manual || $is_change_quiver)$cls.=' tr-changed';
        if($field=='anexo_upl')$cls.=' j-disable-edit';
        if($row_apolice_check)$cls.=' tr-apolice_check';

        if($fields_ignore_show){
            $n = $fields_ignore_show['hide_admin']??false; if($n && in_array($field,$n))$cls.=' tr-igshow-hide_admin';
            $n = $fields_ignore_show['not_quiver']??false; if($n && in_array($field,$n))$cls.=' tr-igshow-not_quiver';
        }

        return '<tr data-group="'.$blockname.'" data-field="'.$field.'" data-field-to-form="'.$field_to_form.'" class="'. trim($cls) .'" data-col-pdf="'. ($dataPdf[__PCfxv($field_to_form)]??'') .'">'.$tds.'</tr>';
    }

    function __PRConvertField($col, $v, $field=null){//$col - valores: quiver, execfinal, exec  //sintaxe $v: [0 val_from, 1 val_to]   //$field (opcional) apenas para referência do programador
        if(is_array($v)){//$v tem que ser array para poder formatar corretamente por $col
            if($col=='quiver'){//exibe os dados que estão no quiver [0 val_from]
                $v = $v[0];

            }else if($col=='execfinal'){//exibe apenas 'ok' //e no css será ajustado o respectivo
                //dump($v);
                $v = $v[1]!='' ? '<span class="hidden">ok</span>' : '';

            }else{//exec    //exibe os dados do processanto  [0 val_from e 1 val_to]
                //lógica: se null, quer dizer que o valor era igual e não ocorreu alterações, e por isto, exibe um só valor
                //if(substr($col,0,4)=='exec')dump([$v,$field]);
                if(is_null($v[1]??null)){
                    $v = 'Não alterado';
                }else{
                    $v = '<span class="col-span col-from">'. ($v[0]!=''?$v[0]:'vazio') .'</span>'.
                         '<span class="col-span-x">|</span>'.
                         '<span class="col-span col-to">'. ($v[1]!=''?$v[1]:'vazio') .'</span>';
                }
            }
        }
        if($field=='anexo_upl')$v .='|'. print_r($v,true);
        return $v;
    }


    function __PCAWriteBlockItemView($datablocks,$segDados_label,$countcol,$dataarr,$ctrlDados_user,$ctrlDados_robo,$dataPdf,$f_block_name,$execsArr,$execsFinal,$execs_count, $validate_not,$thisClass, $fields_ignore_show){
        foreach($datablocks as $blockname=>$blockopt){
            if($blockname!=$f_block_name)continue;
            echo '<tr data-group="'.$blockname.'" class="tr-group-name tr-group-col-'.$blockname.' j-collapse-rows">';

            //label
            echo '<td data-col="label" class="col-label no-select">
                    <span class="margin-r-5">'. $blockopt['label']. '</span>
                    <span class="fa fa-angle-down icon-collapse" data-icon="fa-angle-right|fa-angle-down"></span>
                </td>';

            //quiver
            echo '<td data-col="quiver" class="col-quiver"></td>';    //__PCAWriteBlockItemView_x1([$execsFinal],$f_block_name,'quiver',$thisClass);

            //pdf
            echo '<td data-col="apolice_check" class="col-apolice_check"></td>';

            //pdf
            echo '<td data-col="pdf" class="col-pdf"></td>';

            //manual
            echo '<td data-col="manual" class="col-manual"></td>';

            //execfinal
            __PCAWriteBlockItemView_x1([$execsFinal],$f_block_name,'execfinal',$thisClass);

            //execs
            if($execsArr)__PCAWriteBlockItemView_x1($execsArr,$f_block_name,'exec',$thisClass);

            echo '</tr>';

            foreach($segDados_label as $field=>$label){
                if(!in_array($field,$blockopt['fields']))continue;
                echo __PCAShowItem01($blockname, $dataarr, $field, $field, $ctrlDados_user, $ctrlDados_robo, $dataPdf, $execsArr, $execsFinal, $validate_not, $fields_ignore_show);
            }
        }
    }
    //complemento de __PCAWriteBlockItemView()
    function __PCAWriteBlockItemView_x1($execsArr,$f_block_name,$datacol,$thisClass){
        //monta as células dos resumos de cada coluna execs com o status_code geral retornado por bloco (dados, premio, etc)
        $count=0;
        foreach($execsArr as $i => $arr){
            if(!$execsArr[$i])continue;
            $arr = $arr[$f_block_name]??'';
            //dd($arr);
            $status_code = $arr['_status_code']??'';
            $status_code_label = $status_code && $status_code!='ok' && $status_code!='ok2' ? $thisClass::getStatusCode($status_code) : '';
            echo '<td data-col="'. ( $datacol=='execfinal' ? $datacol : ($datacol.'_'.($i+1)) ).'" class="text-truncate group-col-quivexec group-col-'.$datacol. ($status_code=='ok' || $status_code=='ok2' ? ' code-ok' : ($status_code!=''?' code-err':'') ) .'" data-status-code="'. $status_code .'" data-status-code-label="'. htmlspecialchars($status_code_label) .'" title="'. $status_code_label .'">';
                if($datacol=='execfinal'){
                    echo $status_code=='ok' || $status_code=='ok2'?' <span class="fa fa-check text-green"></span> ':'Erro';
                }else{
                    echo $status_code_label;
                }
            echo '</td>';
            $count++;
        }
        if($count==0){
            echo '<td data-col="'. ( $datacol=='execfinal' ? $datacol : ($datacol.'_'.(1)) ).'" class="group-col-quivexec group-col-'.$datacol.' code-none" data-status-code="none" data-status-code-label="Não processado">&nbsp;</td>';
        }
    }


}



$PrSegService = new PrSegService;
$PrSegService->setProcessClass($thisClass);
Form::loadScript('forms');
Form::loadScript('inputmask');
Form::loadScript('doublescroll');
Form::loadScript('select2');

$prefix = Config::adminPrefix();
$model_data = $model->data_array;
if(!isset($model_data['error_msg']))$model_data['error_msg']='';
if(!isset($model_data['ctrl_changes']))$model_data['ctrl_changes']=false;
//dd($model_data);
$data_type = 'apolice';

//se true indica que somente o superadmin tem permissão para editar o registro estar como suporte ou análise
$is_change_status_data = (in_array($user_logged_level,['dev','superadmin'])) || (!in_array($user_logged_level,['dev','superadmin']) && !in_array($model->process_status,['e','1']));
//dump($is_change_status_data);


$is_lock_form_edit = in_array($model->process_status,['e','c','i','1']);   //se true, bloqueia a edição de dados pelo form
$is_block_collapsed = false;    //se true, inicia os blocos colapsados
$is_user_manual_confirm = ($model_data['user_manual_confirm']??'')=='s';

//lógica: se já foi finalizado (status=f) e changed==0 - quer dizer que foi finalizado pelo robô e não pode ser alterado pelo usuário admin
if(in_array($user_logged_level,['dev','superadmin'])){
    $is_change_status = true;
}elseif($user_logged_level=='admin'){
    $is_change_status = $model->process_status!='f'; //altera somente os status não finalizados
}else{//user
    $is_change_status = $model->process_status!='f' && $model->user_id=$user_logged_id; //altera somente os status feitos por ele
};



$status_list_change = $status_list;
if($user_logged_level=='dev'){
    unset($status_list_change['o'],$status_list_change['a']);//tira os status que são alterados dinamicamente pelo sistema
}else{
    unset($status_list_change['o'],$status_list_change['0'],$status_list_change['a']);//tira os status que são alterados dinamicamente pelo sistema
}


$termsList=[];
if($user_logged_level=='dev'){//temporário enquanto estiver em teste
    $terms = $configProcessNames['cad_apolice']['terms']??null;
    $termsList = \App\Models\Term::whereIn('id',$terms)->get();
}



//***** arquivo *****
    $file_pass='';
    $n=$model_data['file_original_name']??'';if(empty($n))$n='arquivo sem nome';
    $model_data['file_original_name_tmp']=$n;

    if($fileInfo['success']==false){
        $fileInfo['link_html']='<span style="color:red;">'.$fileInfo['msg'].'</span>';
        $fileInfo['file_url']=null;

    }else if(file_exists($fileInfo['file_path'])){
        $file_pass = $model_data['file_pass']??'';
        if($file_pass){
            try{
                $file_pass=json_decode($file_pass,true);
            }catch (\Exception $e){
                $file_pass = '';
            }
        }
        if($file_pass && is_array($file_pass) && count($file_pass)==1){//existe apenas 1 senha
            $file_pass=$file_pass[0];
        }else{
            $file_pass='';//como existem mais senhas, deixa vazia, pois não fica intuitivo exibir várias opções nesta tela
        }
        $fileInfo['link_html']='<a href="'.$fileInfo['file_url'].'" target="_blank" title="Arquivo PDF que gerou este processo: '.$n.'" '.
                                    ($file_pass ? 'onclick="if(!prompt(\'Senha do arquivo. Copie para avançar.\',\''. $file_pass .'\'))return false;" ' : '').
                                    '>'. str_limit($n,20) .'</a>';
    }else{
        $fileInfo['link_html']='<a href="'.$fileInfo['file_url'].'" target="_blank" title="Arquivo não encontrado no diretório" style="color:red;">Erro: '. $n .'</a>';
    }





//captura os dados necessários para montar a tabela ************
    //classes dos seguros
        $segDadosClass = $PrSegService::getSegClass('dados');
        $segDados_label = $segDadosClass::fields_labels();
        $segDados_db = $PrSegService->getTableModel('dados')->where('process_id',$model->id)->first();
        $segParcelasClass = $PrSegService::getSegClass('parcelas');
        $segParcelas_label = $segParcelasClass::fields_labels();
        $segParcelas_db = $PrSegService->getTableModel('parcelas')->where('process_id',$model->id)->get();
        if($segDados_db)$data_type=$segDados_db->data_type;

            //quer dizer que esta apólice é do tipo histório, e neste caso verifica se tem o registro de apólice associado
            $modelRefHist=null;
            if($data_type=='historico' && $model->process_status!='f'){//se estiver finalizado não precisa verificar
                $modelRefHist = \App\Models\Base\ProcessRobot::whereRaw('process_robot.id = (select d.process_id from process_robot_data d where d.meta_name=? and d.meta_value=?)', ['hist_id',$model->id] )->first();
            }


    //classe específicas dos produtos/ramos
        $segProdClass = $PrSegService::getSegClass($model->process_prod);
        $segProd_label = $segProdClass::fields_labels();
        $segProd_db = $PrSegService->getTableModel($model->process_prod)->where('process_id',$model->id)->get();
        $segProd_count=1;   //obs: assim que ajustar novos produtos com mais de um item, então deve-se revistar este código para mais produtos
        $segProd_title = $configProcessProd['title'];


    //dados verificados no site da seguradora
        if(in_array($user_logged_level,['dev','superadmin'])){//somente para o admin e super admin que exibe
            $ProdApoliceCheck = new \App\Http\Controllers\Process\SeguradoraData\ProdApoliceCheck;
            $dataApoliceCheck = $ProdApoliceCheck->getDataApoliceCheck($model);
            $is_show_apolice_check = $dataApoliceCheck?true:false;

            $m=\App\Models\PrSeguradoraData::where(['process_rel_id'=>$model->id,'process_prod'=>'apolice_check'])->first();
            if($m)$dataApoliceCheck['_model_data'] = $m;
            //dd($dataApoliceCheck);

        }else{
            $dataApoliceCheck=null;
            $is_show_apolice_check = false;
        }


    //dados extraídos do pdf
        $dataPdf = $PrSegService->getDataPdf($model,'view','view',false);
        if($is_show_apolice_check){
            //não mescla os dados do pdf, pois deve poder visualizar separadamenteo os dados
        }else{
            if($dataApoliceCheck)//mescla os resultados
                $dataPdf = (new \App\Http\Controllers\Process\SeguradoraData\ProdApoliceCheck)->mergeDataPdfApoliceCheck($dataPdf,$dataApoliceCheck,$model->process_prod);
        }
        //dd($is_show_apolice_check,$dataPdf['segurado_nome'],$dataApoliceCheck['dados']['segurado_nome']);
        if(!$dataPdf){//a variável está vazia, quer dizer que ainda não foi indexado
            //trava a edição de dados pelo form, pois não está extraído
            $is_lock_form_edit=false;

            //salva para o ponto programado
            goto tableview;
        }



    //registro de alterações nos dados - pelo usuário
        $ctrlDados_user = $PrSegService->getDataCtrlStatus('dados',$model->id,'user',['only_changed'=>true]);
        $ctrlParcelas_user = $PrSegService->getDataCtrlStatus('parcelas',$model->id,'user',['only_changed'=>true]);
        $ctrlProd_user = $PrSegService->getDataCtrlStatus($model->process_prod,$model->id,'user',['only_changed'=>true]);
    //registro de alterações nos dados - pelo robo
        $ctrlDados_robo = $PrSegService->getDataCtrlStatus('dados',$model->id,'robo',['only_changed'=>true]);
        $ctrlParcelas_robo = $PrSegService->getDataCtrlStatus('parcelas',$model->id,'robo',['only_changed'=>true]);
        $ctrlProd_robo = $PrSegService->getDataCtrlStatus($model->process_prod,$model->id,'robo',['only_changed'=>true]);
        //dd($ctrlDados_user,$ctrlDados_robo);

    //anexo //ajuste manual do valor do campo
        if($segDados_db)$segDados_db->anexo_upl = $model_data['file_original_name_tmp'];
        //dd($segDados_db);


//cria a variável de lista de blocos
    $blocks_list = ['dados'=>'Dados',$model->process_prod=>$segProd_title,'premio'=>'Prêmio','parcelas'=>'Parcelas','anexo'=>'Anexo'];


//nos registros de execuções do robô no Quiver, cria um resultado final com os dados mesclados
            //array final com dados mesclados
            //lógica: nos campos finais, os valores estão assim [valFrom,valTo] e se valTo=null, desconsidera ficando apenas com o anterior
            //cada parâmetro $arr deve ser um valor de {process_id}_exec_{exec_id}.data (resultado da função models\ProcessRobotExecs->getText())
            //sintaxe: $arr = [blockname=> [field1=>[valFrom,valTo], ...], ...]   OU    $arr = [blockname=> [$i=>[field1=>[valFrom,valTo], ...], ...], ...]
            function arrayMergeFieldsExecs($arr1,$arr2){
                $r=$arr1;
                foreach($arr2 as $block=>$fields){
                    if(is_array($fields)){ //obs: se não for array, quer dizer que existem um campo inválido em $arr2 (provável erro na montagem do json)
                        $r[$block] = arrayMergeFieldsExecs_x1($arr1[$block]??[],$fields);
                    }
                }
                return $r;
            }
            //sintaxe: $arr [field1=>[valFrom,valTo], ...]
            function arrayMergeFieldsExecs_x1($arr1,$arr2){//auxiliar de arrayMergeFieldsExecs()
                $r=$arr1;
                foreach($arr2 as $f=>$v){
                    if(is_numeric($f) && is_array($v)){//está no formato [num1=>[field1=>val,...],...]
                        $n = arrayMergeFieldsExecs_x1($arr1[$f]??[],$v);
                        $r[$f]=$n;//if($n)//seta somente se receber o array preenchido
                    }else{
                        $v1=$arr1[$f]??null;
                        $v2=$v;
                        if(is_array($v2)){
                            if(isset($v2[0])){//$v2 = [from,to]
                                $v = $v1 && is_array($v1[1])?$v1[1]:$v1;
                                $r[$f] = [$v2[0] , (is_null($v2[1])?$v:$v2[1]) ];
                            }else{//$v2 = [key=>value]
                                $r[$f] = $v2[ array_keys($v2)[0] ];
                            }
                        }else{
                            if($v2)$r[$f] = $v2;
                        }
                    }
                }
                return $r;
            }
    $execsArr = [];
    $execsFinal = [];
    $exec_last_date='';//data do último processamento
    $execs_count=0;
    $exec_error_msg=null;

    $i=0;
    foreach($execsModel as $ii => &$reg){
        $m = $reg->getText($model);
        if($user_logged_level=='dev'){
            //dd($m);
            //################## código para correção manual do erro ao montar o json no retorno do robô ###########################
            //                   executar de modo manual para correção e tomar com muito cuidado !!!!!!!!!
            //                   atualização 08/03/2021
            //                   precisa ser revisado, pois não está 100% funcionando...
            //    $p = $model->baseDir()['dir_final'] . DIRECTORY_SEPARATOR . $reg->process_id.'_exec_'.$reg->id.'.data';
            //    $m=$m['automovel'];
            //    $model->setText('exec_'.$reg->id,$m);
            //    dd($m,$p,file_exists($p));
            //########################### ########################### ###########################
        }

        $exec_error_msg = $m['error_msg']??null;//armazena o último erro retornado de 'error_msg'
        if($m){
            //formata os valores
            $m['dados'] = FormatUtility::formatData($m['dados']??[] , $segDadosClass::fields_format_quiver(), 'view');
            $m['premio'] = FormatUtility::formatData($m['premio']??[] , $segDadosClass::fields_format_quiver(), 'view');
            $m['anexo'] = FormatUtility::formatData($m['anexo']??[] , $segDadosClass::fields_format_quiver(), 'view');
            $m['parcelas'] = FormatUtility::formatDataArr($m['parcelas']??[] , $segParcelasClass::fields_format_quiver(), 'view');
            $m[$model->process_prod] = FormatUtility::formatDataArr($m[$model->process_prod]??[] , $segProdClass::fields_format_quiver(), 'view');

            $reg->_textData=$m;
            $execsArr[$i] = $m;
            $exec_last_date = $reg->process_start;
            //array final com dados mesclados
            //lógica: nos campos finais, os valores estão assim [valFrom,valTo] e se valTo=null, desconsidera ficando apenas com o anterior
            $execsFinal = arrayMergeFieldsExecs($execsFinal,$m);

            //monta a mensagem de retorno de cada bloco e deixa gravado em $reg para ser listado mais abaixo na tabela 'Processamentos do Robô'
                    if($reg->process_end && !$reg->status_code){//quer dizer que teve retorno, mas o status_code é null (ocorreu algum erro)
                        $reg->status_code='err';
                    }

                    if($reg->status_code){
                        $r=[];$x=0;
                        foreach($blocks_list as $block => $label){
                            $n=array_get($m,$block.'._status_code');
                            $text = $n ? $thisClass::getStatusCode($n) : '-';
                            $r[]= '<span class="itemx1 '. ($n=='ok' || $n=='ok2'?'text-green':'text-red') .' text-truncate" title="'.$text.' ('. strtoupper($n) .')">'. $text .'</span>';
                            if($n)$x++;
                        }
                        if($x>0){
                            $reg->_msgs = join('',$r);
                        }else{
                            $n = $reg->status_code;
                            $text = $thisClass::getStatusCode($n);
                            $reg->_msgs = '<span class="itemx1 itemx1-colspan '. ($n=='ok' || $n=='ok2'?'text-green':'text-red') .' text-truncate" title="'.$text.'">'. $text .'</span>';
                        }
                    }

            $execs_count++;
            $i++;
        }else{
            //$execsArr[$i] = null;

            if($reg->status_code){
                //até aqui, quer dizer que tem um código de retorno (foi retornado pelo Robô AutoIt) e $m (texto do retorno) está vazio
                //provavelmente o erro é na busca e o robô não localizou a proposta para prosseguir, e por isto não tem os dados de cada bloco
                //portanto screve apenas os dados erro principal $reg->status_code
                $n = $thisClass::getStatusCode($reg->status_code);
                $reg->_msgs = '<span class="itemx1 text-red text-truncate" title="'. $n .'">'. $n .'</span>';
            }
        }
    }
        //verifica se existem campos com erros
        $exec_field_err = false;//erros de emissão nos campos
        if($execsFinal){
            foreach(['dados','premio','anexo','parcelas',$model->process_prod] as $block){
                if(!in_array( array_get($execsFinal,$block.'._status_code') ,['ok','ok2']))$exec_field_err=true;
            }
        }
    //dump($execsArr,$execsFinal,$exec_field_err);exit;




//prepara as arrays de dados para montar a tabela ************

    //info das colunas: (label) nomes dos campos, (quiver) valores do quiver, (pdf) valores do pdf, (manual) valores digitados manualmente pelo usuário, (execfinal) resultado mesclado de todos os processamentos do robô
    $datacol=['label'=>'Campos','quiver'=>'Dados do Quiver','apolice_check'=>'Verificado Seguradora','pdf'=>'Original da Apólice','manual'=>'Dados da Apólice','execfinal'=>'Status'];
    $countcol=count($datacol);
    $dataarr=['label'=>[],'quiver'=>[],'apolice_check'=>[],'pdf'=>[],'manual'=>[],'execfinal'=>[]];
    if(!$is_show_apolice_check)unset($dataarr['apolice_check'],$datacol['apolice_check']);
    $extracol=[
        'label'=>'Data do Processo',
        'quiver'=> '-',
        'apolice_check'=>'-',
        'pdf'=>FormatUtility::dateFormat($model->created_at,'d/M H:i'),
        'manual'=> ($model_data['fields_dtupd_manual']??false) ? FormatUtility::dateFormat($model_data['fields_dtupd_manual'] ,'d/M H:i') : '',
        'execfinal'=> $exec_last_date ? FormatUtility::dateFormat($exec_last_date ,'d/M') : '-',
    ];
    if(!$extracol['manual'])$extracol['manual']=$extracol['pdf'];
    if(!$is_show_apolice_check)unset($extracol['apolice_check']);

    //dados e premio (e anexo)
    $arr_segDados_db = $segDados_db ? $segDados_db->toArray() : [];
    $arr = $arr_segDados_db;
    //dd($arr);
    $manual_db_dados = FormatUtility::formatData($arr, $segDadosClass::fields_format(), 'view');
    $manual_form_dados = FormatUtility::formatData($arr, $segDadosClass::fields_format('form'), 'view');
    foreach($segDados_label as $field=>$label){
        $dataarr['label'][$field] = $label;
        $dataarr['pdf'][$field] = $dataPdf[$field]??'';
        $dataarr['manual'][$field] = $manual_db_dados[$field]??'';
        $dataarr['quiver'][$field] = __PRConvertField('quiver',$execsFinal['dados'][$field]??$execsFinal['premio'][$field]??'');
        $dataarr['execfinal'][$field] = __PRConvertField('execfinal',$execsFinal['dados'][$field]??$execsFinal['premio'][$field]??'');

        if($field=='anexo_upl'){ //obs: precisa repetir também com o anexo, pois estão junto de 'dados'
            $dataarr['quiver'][$field] = __PRConvertField('quiver',$execsFinal['anexo'][$field]??'');
            $dataarr['execfinal'][$field] = __PRConvertField('execfinal',$execsFinal['anexo'][$field]??'');
        }

        if($is_show_apolice_check && isset($dataApoliceCheck['dados']))$dataarr['apolice_check']=$dataApoliceCheck['dados'];
    }
    //dd($dataarr);

    if($execsModel){
        $i=0;
        foreach($execsModel as $ii => $reg){
            //if(!$execsArr[$ii])continue;
            if(!$reg->_textData)continue;

            $datacol['exec_'.($i+1)]    = '<span>Processamento '.$reg->id .'</span> <a title="Processamento '.$reg->id .'" class="fa fa-minus-square-o" style="position:relative;top:1px;margin-left:5px;" data-icons="fa-plus-square-o|fa-minus-square-o" onclick="shColExecs(\''. ($i+1) .'\',$(this));return false;"></a>';

            $n=$reg->process_end ? FormatUtility::dateDiffFull($reg->process_start,$reg->process_end) : '';
            $extracol['exec_'.($i+1)]   = FormatUtility::dateFormat($reg->process_start ,'d/M H:i') . ($n?' <small title="Tempo de Processamento" class="text-color-disable">'.$n.'</small>':'');

            //cria os dados $dataarr['exec_{N}'] para a montagem dos dados na tabela principal
            if(!isset($dataarr['exec_'.($i+1)]))$dataarr['exec_'.($i+1)]=[];

            foreach($execsArr[$i]['dados'] as $f => $v){
                if($f=='_status_code')continue;
                $dataarr['exec_'.($i+1)][$f] = __PRConvertField('exec',$v);
            }

            foreach($execsArr[$i]['premio'] as $f => $v){
                if($f=='_status_code')continue;
                $dataarr['exec_'.($i+1)][$f] = __PRConvertField('exec',$v,$f);
            }

            //obs: o anexo tem apenas 1 campo para ser exibido e por isto o código do foreach abaixo está desconsiderado
                /*foreach($execsArr[$i]['anexo'] as $f => $v){
                    if($f=='_status_code')continue;
                    if($f!='anexo_upl')continue;
                    $dataarr['exec_'.($i+1)][$f] = __PRConvertField('exec',$v,$f);
                }*/
                //apenas verifica se existe o retorno do anexo
                if(!empty($execsArr[$i]['anexo'])){
                    $dataarr['exec_'.($i+1)]['anexo_upl'] = __PRConvertField('exec', $execsArr[$i]['anexo']['anexo_upl']??'Não alterado' );
                }

            $i++;
            $countcol++;
        }
    }
    //dd($dataarr);

    if($manual_db_dados && isset($dataPdf['fpgto_n_prestacoes'])){
        $count_prestacoes = $dataPdf['fpgto_n_prestacoes'] > $manual_db_dados['fpgto_n_prestacoes'] ? $dataPdf['fpgto_n_prestacoes'] : $manual_db_dados['fpgto_n_prestacoes'];
    }else{
        $count_prestacoes = 0;
    }
    if($is_show_apolice_check && isset($dataApoliceCheck['parcelas'])){
        $i=count($dataApoliceCheck['parcelas']);
        if($count_prestacoes < $i)$count_prestacoes = $i;
    }


    //parcelas
    $manual_db_parcelas=[];
    $manual_form_parcelas=[];

    for($i=1;$i<=$count_prestacoes;$i++){
        $n = $segParcelas_db->get($i-1);
        $arr = $n ? $n->toArray() : [];

        $manual_db_parcelas[$i] = FormatUtility::formatData( $arr , $segParcelasClass::fields_format(), 'view');
        $manual_form_parcelas[$i] = FormatUtility::formatData( $arr , $segParcelasClass::fields_format('form'), 'view');

        foreach($segParcelas_label as $field=>$label){
            if($field=='num')continue;
            $field2=$field.'_'.$i;
            $dataarr['label'][$field2] = $label.' '.$i;
            $dataarr['pdf'][$field2] = $dataPdf[$field2]??'';
            $dataarr['manual'][$field2] = $manual_db_parcelas[$i][$field]??'';

            $n=($execsFinal['parcelas'][$i][$field]??'');
            $code = $execsFinal['parcelas'][$i]['_status_code']??'';
            $dataarr['quiver'][$field2] = __PRConvertField('quiver',$n) ;
            $dataarr['execfinal'][$field2] = __PRConvertField('execfinal',$n) ;

            if($is_show_apolice_check && isset($dataApoliceCheck['parcelas']))$dataarr['apolice_check'][$field2] = ($dataApoliceCheck['parcelas'][$i][$field]??'');
        }
    }
        //dd($dataarr);

        if($execsModel){
            $i=0;
            foreach($execsModel as $ii => $reg){
                if(!$reg->_textData)continue;
                if($execsArr[$i]){
                    foreach($execsArr[$i]['parcelas'] as $p => $reg2){
                        if($p=='_status_code')continue;
                        if(!isset($dataarr['exec_'.($i+1)]))$dataarr['exec_'.($i+1)]=[];
                        if(is_array($reg2)){
                            $code=$reg2['_status_code']??'ok2';//caso não definido, seta o padrão 'ok2' 'não alterado'
                            if(in_array($code,['ok','ok2'])){;
                                foreach($reg2 as $f=>$v){
                                    if($f=='_status_code')continue;
                                    $dataarr['exec_'.($i+1)][$f.'_'.$p] = __PRConvertField('exec',$v);
                                }
                            }else{//erro no retorno destes campos
                                //grava os nomes dos campos com o código do erro para ser visualizado corretamente dentro da tabela (pois provavelmente não virá pelo retorno do robô local, pois deu erro ao processo)
                                //if($ii==1)dd($reg2,$code,$execsArr);
                                foreach($segParcelasClass::fields_labels() as $f=>$label){
                                    if($f=='num')continue;
                                    $dataarr['exec_'.($i+1)][$f.'_'.$p] = $thisClass::getStatusCode($code);
                                }
                            }
                        }
                    }
                }
                $i++;
            }
        }
    //dd($dataarr);


    //produto
    $manual_db_prod=[];
    $manual_form_prod=[];
    for($i=1;$i<=$segProd_count;$i++){
        $arr = $segProd_db->count()>0 ? ($segProd_db->get($i-1)->toArray()??[]) : [];
        $manual_db_prod[$i] = FormatUtility::formatData( $arr , $segProdClass::fields_format(), 'view');
        $manual_form_prod[$i] = FormatUtility::formatData( $arr , $segProdClass::fields_format('form'), 'view');

        foreach($segProd_label as $field=>$label){
            $field2=$field.'_'.$i;

            //ajusta o campo produto (pois no caso do veículo, os campos não estão separados em '_1,_2...'
            if(!isset($dataPdf[$field2]) && isset($dataPdf[$field])){
                $dataPdf[$field2] = $dataPdf[$field];
            }

            $dataarr['label'][$field2] = $label . ($segProd_count>1?' '.$i:'');
            $dataarr['pdf'][$field2] = $dataPdf[$field2]??'';
            $dataarr['manual'][$field2] = $manual_db_prod[$i][$field]??'';

            $n=($execsFinal[$model->process_prod][$i][$field]??'');
            $dataarr['quiver'][$field2] = __PRConvertField('quiver', $n) ;
            $dataarr['execfinal'][$field2] = __PRConvertField('execfinal', $n) ;

            if($is_show_apolice_check && isset($dataApoliceCheck[$model->process_prod]))$dataarr['apolice_check'][$field2] = ($dataApoliceCheck[$model->process_prod][$i][$field]??'');
        }
    }
        if($execsModel){
            $i=0;
            foreach($execsModel as $ii => $reg){
                if(!$reg->_textData)continue;
                if($execsArr[$i]){
                    foreach($execsArr[$i][$model->process_prod] as $p => $reg2){
                        if($p=='_status_code')continue;
                        if(!isset($dataarr['exec_'.($i+1)]))$dataarr['exec_'.($i+1)]=[];
                        if(is_array($reg2)){
                            foreach($reg2 as $f=>$v){
                                $dataarr['exec_'.($i+1)][$f.'_'.$p] = __PRConvertField('exec', $v);
                            }
                        }
                    }
                }
                $i++;
            }
        }
    //dd('ok',$dataarr);
    //dd($is_show_apolice_check,$dataApoliceCheck,$dataarr);

    $datablocks=$segDadosClass::fields_layoutGroup();



    $validate_not=[];
    if(in_array($model->process_status,['e','i','c'])){//está com status de erros       //$is_user_manual_confirm==false &&

            //validação geral da classe de serviços
            $validate = $PrSegService->validateAll($manual_form_dados, $manual_form_parcelas, $manual_form_prod, $model->process_prod, ['check_pgto'=>true,'processModel'=>$model]);
            if($validate===true)$validate=['success'=>true];
            //dd(123,$validate);

            if(!$validate['success']){
                if(isset($validate['validate']))$validate_not=array_merge($validate_not,$validate['validate']);
                unset($validate_not['code']);//campo extra desnecessário aqui
                //dd($validate,$validate_not,$model_data['error_msg']);
                if(!$validate_not && $validate['msg']!=$thisClass::getStatusCode($model_data['error_msg'],false))$validate_not['Validação']=$validate['msg'];
            }


            //dd($validate_not,$model_data['error_msg'],$model->broker);
            if(!$validate_not && $model_data['error_msg']=='read01'){//quer dizer que não existem validações pendentes, mas continua na mensagem marcado como 'read01' (campos inválidos)
                //processa novamente
                //if($user_logged_level=='dev')dd($model,'D');
                //exit('<script>window.location="'. route($prefix.".app.get",["process_cad_apolice","reprocessFile"]) .'?id='.$model->id.'&rd='. urlencode(Request::fullUrl()) .'";</script>');
            }

            //dump($validate_not,$model_data['error_msg']);
    }
    if(!$model->insurer_id)$validate_not['insurer_id']=$thisClass->getStatusCode('ins01');
    if(!$model->broker_id)$validate_not['broker_id']=$thisClass->getStatusCode('bro01');

    //controle dos dados iniciais que devem estar exibidos/selecionados ao carregar a página
        $filter_rows1_selected='';
        $msg=$model_data['error_msg'];
        if($execs_count==0 && ($ctrlDados_user || $ctrlParcelas_user || $ctrlProd_user)){//existem dados alterados pelo usuário
            $filter_rows1_selected = 'manual_changed';  //campo de filtros para exibir os dados alterados pelo usuário
        }else if($execs_count==0 && empty($validate_not) && (empty($msg) || $msg=='ok' || $msg=='ok2') ){//quer dizer que é a visualização do processo sem alterações realizadas
            $is_block_collapsed=true;
        }else if(in_array($model->process_status,['p','a'])){//quer dizer que está Pronto para o robo ou em Andamento, e quer dizer que os dados da tabela estão ok, portanto pode deixar collapsado
            $is_block_collapsed=true;
        }else if(substr($msg,4)!='quiv'){//não é erro de execução do quiver, portnato indica que deve carregar a tabela colapsada
            $is_block_collapsed=true;
        }
        $msg=null;//reseta a var



    //para estes erros, adiciona os respectivos códigos na var $validate_not para marcar corretamente o campo que deve ser alterado na tabela/matriz de campos
            $custom_codes_fields_edit = [
                'read17'=>'fpgto_iof',
                //obs: os campos abaixo não existem na tabela/matriz de campos do form, mas estão listados neste array para que o texto não fique duplicado na div de msg principal
                'bro01'=>'broker_id',
            ];
            if(in_array($model_data['error_msg'], array_keys($custom_codes_fields_edit) )){
                $validate_not[   $custom_codes_fields_edit[$model_data['error_msg']]  ]=$thisClass::getStatusCode($model_data['error_msg']);
            }




tableview:  //ponto para salto na programação caso a var $dataPdf seja vazia (não executar os códigos acima)

        if(!$dataPdf){//a variável está vazia, quer dizer que ainda não foi indexado
            //cria as variáveis necessárias para o processamento abaixo que não foram criadas acima, por causa do comando goto tableview;
            $exec_last_date='';
            $filter_rows1_selected='';
            $validate_not=[];
            $exec_field_err = false;
            $blocks_list = ['dados'=>'Dados',$model->process_prod=>$segProd_title,'premio'=>'Prêmio','parcelas'=>'Parcelas','anexo'=>'Anexo'];
            $execs_count=0;
            $exec_error_msg=null;
            $custom_codes_fields_edit=[];
        }


    //******** verifica se existe o log da baixa da versão antiga, se existir será um array serializado em $log_data_v1 ********
    $log_data_v1=$model->getText('log_v1');
    if($log_data_v1 && strlen(implode('', $log_data_v1))==0)$log_data_v1=null;//todas as arrays estão vazias


    //lógica: quer dizer que existe log de baixa da versão antiga (está acessando um registro antes de ser atualizado), mas tem histório de execuções (tabela process_robot_execs),
    //    portanto, não tem nada para exibir em $dataPdf, e por isto deve ser =null para ficar oculto
    //if($log_data_v1 && $execsModel->count()==0)$dataPdf=null;





//monta os bloco de dados básicos em tabela em html ************
echo '<div class="row-max1"><div class="clearfix row">';
    echo '<div class="col-sm-3"><div class="box box-primary box-widget"><div class="box-body no-padding">
                <table class="table no-margin">
                    <tr title="Id '.$model->broker_id.'"><td>Corretora</td><td>
                        <span class="strong">';
                            if($is_change_status_data){
                                    if($model->broker){
                                        if($model->getData('broker_manual')!='s' &&  (!$is_lock_form_edit)  ){
                                            echo str_limit(($model->broker->broker_name??'-') ,20);
                                        }else{
                                            echo str_limit(($model->broker->broker_name??' <a href="#" onclick="selectBroker();return false;" class="text-red" title="Alterar Corretor">Selecionar</a>'),20) .'<a href="#" onclick="selectBroker();return false;" style="margin-left:10px;" class="text-red" title="Alterar Corretor"><i class="fa fa-pencil"></i></a>';
                                        }
                                    }else{
                                        echo '<a href="#" onclick="selectBroker();return false;" class="text-red" title="Alterar Corretor">Selecionar <i class="fa fa-pencil"></i></a>';
                                    }
                            }else{
                                echo '-';
                            }
                    echo'</span>
                    </td></tr>
                    <tr title="Id '.$model->insurer_id.'"><td>Seguradora</td><td><span class="strong">'. ($model->insurer->insurer_alias??'-') .'</span></td></tr>
                </table>
         </div></div></div>';

    echo '<div class="col-sm-4"><div class="box box-primary box-widget"><div class="box-body no-padding">
        <table class="table no-margin">
            <tr><td>Nº de Apólice</td><td><span class="strong">'. ($segDados_db ? $segDados_db->apolice_num_quiver : '-') .'</span></td></tr>
            <tr><td>Segurado</td><td><span class="strong">';

                $n = ($segDados_db ? $segDados_db->segurado_nome : '');
                if($n){
                    $i = strpos($n,'--');
                    if($n && $i!==false)$n=substr($n,$i+2);
                }
                echo $n ? $n : '-';
            echo '</span></td></tr>
        </table>
    </div></div></div>';

    echo '<div class="col-sm-3"><div class="box box-primary box-widget"><div class="box-body no-padding">
        <table class="table no-margin">
            <tr><td>Cadastro</td><td><span class="strong">'. FormatUtility::dateFormat($model->created_at,'datetime2') .'</span></td></tr>'.
            ($model->process_next_at ?
                '<tr><td class="text-teal">Processo Agendado</td><td><span class="strong text-teal" onclick="nextAtClear();return false;">'. FormatUtility::dateFormat($model->process_next_at,'datetime2') .'</span></td></tr>'
            :
                '<tr><td>Processamento</td><td><span class="strong">'. ($model->updated_at || $exec_last_date ? FormatUtility::dateFormat(($model->updated_at??$exec_last_date),'datetime2') : '-') .'</span></td></tr>'
            ).
        '</table>
    </div></div></div>';

    $ref_id=false;
    if($data_type=='apolice'){
        $ref_id = $model_data['hist_id']??false;
        $ref_data_type='Histórico';

    }else if($data_type=='historico'){
        $ref_id=\App\Models\ProcessRobot_CadApolice::whereData(['hist_id'=>$model->id])->value('id');
        if($ref_id)$ref_data_type='Apólice';
    }
    echo '<div class="col-sm-2"><div class="box box-primary box-widget"><div class="box-body text-center relative">
            <a class="btn btn-info strong" href="'.$fileInfo['file_url'].'" target="_blank" '.  ($file_pass ? 'onclick="if(!prompt(\'Senha do arquivo. Copie para avançar.\',\''. $file_pass .'\'))return false;" ' : '')  .'>Acessar '. (VarsProcessRobot::$typesApolices[$data_type]??'none') .'</a>
            '. ($ref_id?'<a href="'. route($prefix.'.app.show',['process_cad_apolice',$ref_id,'rd='. Request::input('rd')]) .'" target="_blank" style="position:absolute;left:0;top:80px;width:100%;text-align:center;font-size:12px;">'. $ref_data_type .' #'. $ref_id .'</a>':'') .'
            <br><span class="strong" title="'. $model_data['file_original_name_tmp'] .'" >'. $fileInfo['link_html'] .'</span>
    </div></div></div>';


echo '</div></div>';





//barra de botões de ações **************
echo '<div class="clearfix row row-max1" style="margin-top:10px;margin-bottom:10px;">';

    if($is_change_status_data){
        //campo status
        echo '<div class="col-sm-3">';
            echo '<span style="font-size:small;">Status Atual</span><br>';

            if($dataPdf && ($model_data['req_fill_manual']??false)=='s' && !in_array($user_logged_level,['dev','superadmin'])
                && !in_array($model->process_status,['f','w'])
            ){//se for dev|superadmin pode exibir as opções completos no "ELSE mais abaixo
                echo '<a class="btn btn-danger margin-r-10" title="Editar Dados para Emitir" onclick="editSegData();return false;"><i class="fa fa-pencil"></i> Editar Dados para Emitir</a>';
                if(in_array($model->process_status,['c']))echo '<a class="btn btn-success margin-r-10" title="Marcar como finalizado" onclick="editMarkStatusF();return false;"><i class="fa fa-check"></i></a>';
                //$is_lock_form_edit = true;//habilita a edição

            }else{
                if($dataPdf && ($model_data['req_fill_manual']??false)!='s'){//quer dizer que está indexado
                    echo '<a class="btn btn-info margin-r-10" title="Reprocessar" onclick="editOpen(\'p\');return false;"><i class="fa fa-refresh"></i></a>';
                }
                echo '<a class="btn '.$model->status_color['bg'].' margin-r-10" title="'. $model->status_long_label .'" onclick="editOpen();return false;"><i class="fa fa-pencil"></i> '.$model->status_label.'</a>';
            }

            //if($is_lock_form_edit && in_array($user_logged_level,['dev','superadmin'])){
            if(in_array($user_logged_level,['dev','superadmin'])){
                if(!in_array($model->process_status,['f','w'])){
                    echo '<span style="font-size:small;position:absolute;margin-top:-19px;">Operador</span>';
                    echo '<a class="btn bg-red-active margin-r-10" title="Enviar para o Operador Manual" onclick="editSendOperador();return false;" style="width:52px;"><i class="fa fa-arrow-up"></i></a>';
                    if(in_array($model->process_status,['c']))echo '<a class="btn btn-success" title="Marcar como finalizado" onclick="editMarkStatusF();return false;"><i class="fa fa-check"></i></a>';
                }
            }

        echo '</div>';
    }


    if($dataPdf){//quer dizer que está indexado
            //campos de filtros
            echo '<div class="col-sm-3">';
                echo '<span style="font-size:small;">Filtros<br></span>';
                $list=[];
                if($execs_count){
                    $list['quiver_changed']='Quiver Alterados';
                }
                if($ctrlDados_user || $ctrlParcelas_user || $ctrlProd_user){
                    $list['manual_changed']='Alterados Manualmente';
                }
                if($is_show_apolice_check){
                    $list['apolice_check']='Verificados da Seguradora';
                }
                $list['all']='Quiver Todos';
                $list['allx']='Todos os campos';

                if(!$filter_rows1_selected)$filter_rows1_selected='all'; //valor inicial

                if($validate_not){ //somente se houver erros de validação
                    $list=['field_err'=>'Campos para correção'] + $list;
                    $filter_rows1_selected='field_err';
                    $is_block_collapsed=false;//ajusta para manter expandido os campos com erro ao carregar a página
                }
                if($exec_field_err){
                    $list=['exec_err'=>'Erros ao emitir'] + $list;
                    $filter_rows1_selected='exec_err';
                }
                if(in_array($model->process_status,['f','w'])){//está finalizado
                    if($ctrlDados_robo || $ctrlParcelas_robo || $ctrlProd_robo){
                        $filter_rows1_selected='quiver_changed';
                    }else{
                        $filter_rows1_selected='all';//seleciona todos, pois nenhuma alteração foi realizada no quiver
                    }
                }
                echo Form::select('filter_rows1', $list, $filter_rows1_selected, ['id'=>'filter_rows1','class'=>'form-control', 'style'=>'width:180px;'] );
            echo '</div>';

            if($filter_rows1_selected=='quiver_changed'){
                $is_block_collapsed=false;//neste caso pode exibir os campos alterados
            }
    }

    //campos de ações
    echo '<div class="col-sm-3">';
        echo '<span style="font-size:small;">Ações<br></span>';

        if($dataPdf){//quer dizer que está indexado
                if($is_lock_form_edit && $is_change_status_data){
                    //atualizar dados do pdf
                    echo '<a href="#" class="btn btn-default margin-r-5" onclick="editSegData();return false;" title="Editar Dados da Apólice"><i class="fa fa-pencil"></i></a>';
                }

                //colunas da tabela
                $arr=[
                    'menu-head1'=>['title'=>'Exibir colunas','head'=>true],
                    'menu-datapdf'=>['title'=>'Original da Apólice', 'checkbox'=>true, 'onclick'=>'fDropdownMenuItem(this,\'datapdf\');'],
                    'menu-execs'=>['title'=>'Detalhar Processos do Robô', 'checkbox'=>true, 'onclick'=>'fDropdownMenuItem(this,\'execs\');'],
                ];
                if($is_show_apolice_check)$arr['menu-apolice_check']=['title'=>'Dados Verificados na Seguradora','checkbox'=>true,'onclick'=>'fDropdownMenuItem(this,\'apolice_check\');'];
                echo view('templates.components.button',['title'=>false,'icon'=>'fa-columns','alt'=>'Exibir colunas','class'=>'margin-r-5','sub'=>$arr,'id_menu'=>'menu_show_columns']);
        }

        //taxs
        $terms_ids=[];
        $taxs_list=[];
        if($termsList){
            foreach($termsList as $term){
                $terms_ids[]=$term->id;
                $taxs_list[$term->id]=$model->getTaxRelation($term->id,'cad_apolice');
                //dd($taxs_list[$term->id]);

                echo '<a href="#" class="btn btn-default margin-r-5" id="bt_box_terms_'.$term->id.'" title="Adicionar '. $term->term_title .'"><i class="fa fa-tags"></i></a>';
                echo view('templates.ui.taxs_form',[
                    'id'=>'autofield_box_terms_'.$term->id,
                    'term_id'=>$term->id,
                    'is_collapse'=>true,
                    'show_icon'=>'fa-tags',
                    'is_popup'=>true,
                    'start_collapse'=>true,
                    'taxs_start'=>$taxs_list[$term->id]->pluck('tax_id')->toArray(),
                    'class_select'=>true
                ]);
            }
        }


        //remover
        //$user_logged_level!='user' &&
        if(($is_change_status_data && (!in_array($model->process_status,['f','w']) && !in_array($user_logged_level,['dev','superadmin'])) )
            ||
            in_array($user_logged_level,['dev','superadmin'])
        ){//usuário não pode excluir
            echo view('templates.components.button',['title'=>false,'icon'=>'fa-trash','alt'=>'Remover processo','class'=>'margin-r-5',
                'post'=>[
                    'url'=>route($prefix.'.app.remove','process_cad_apolice'),
                    'data'=>['id'=>$model->id,'_method'=>'DELETE','action'=>'trash'],
                    'confirm'=>'Confirmar exclusão deste processo?',
                    'cb'=>'@function(r){ if(r.success){alert("Processo excluído com sucesso");window.location="'. route($prefix.'.app.get',['process_cad_apolice','list','?process_name='.$model->process_name]) .'";}else{alert("Erro ao excluir: "+ r.msg)} }',
                ]
            ]);

            echo view('templates.components.button',['title'=>false,'icon'=>'fa-unlock','alt'=>'Senha do Arquivo','class'=>'margin-r-5','onclick'=>'fUpdFilePass()']);
        }

        //desenvolvedor / superadmin
        if(in_array($user_logged_level,['dev','superadmin'])){
                echo '<a href="'.  route($prefix.'.app.get',['process_cad_apolice','show_data_rel',$model->id]) .'" class="btn btn-default margin-r-5" target="_blank" title="Relação de todos os processos"><i class="fa fa-dot-circle-o"></i></a>';

                $arr = [
                        'view_text' =>['icon'=>'fa-file-text-o', 'title'=>'Visualizar Extração TXT', 'alt'=>'Visualiza os textos automáticos extraídos do PDF', 'link'=> route($prefix.'.app.get',['process_cad_apolice','pageFileExtracted',$model->id,'type=txt']), 'attr'=>'target="_blank"'  ],
                        'extract' =>['icon'=>'fa-file-pdf-o', 'title'=>'Extração em XML', 'alt'=>'Visualização dos campos XML já salvos do texto do PDF', 'link'=> route($prefix.'.app.get',['process_cad_apolice','pageFileExtracted',$model->id,'type=xml']), 'attr'=>'target="_blank"'   ],
                        'extract_force' =>['icon'=>'fa-file-pdf-o', 'title'=>'Extração em XML Forçada', 'alt'=>'Visualização dos campos a partir do processamento do arquivo de indexação (não salva os dados, apenas visualiza)', 'link'=> route($prefix.'.app.get',['process_cad_apolice','pageFileExtracted',$model->id,'type=xml&force=ok']), 'attr'=>'target="_blank"', 'class'=>'strong' ],
                    ];

                if($model->process_status=='o'){
                    $arr['reprocess'] = ['icon'=>'fa-refresh', 'title'=>'Reprocessar Dados', 'alt'=>'Reprocessar Arquivo PDF sob o texto já extraído', 'class_li'=>'disabled','class'=>'text-color-disable strong'];

                }else{
                    $arr['reprocess'] = ['icon'=>'fa-refresh', 'title'=>'Reprocessar Dados', 'alt'=>'Reprocessar Arquivo PDF sob o texto já extraído', 'onclick'=>'fReprocess()','class'=>'strong'];
                    $arr['reprocess_force'] = ['icon'=>'fa-refresh', 'title'=>'Forçar Extração e Reprocessar', 'alt'=>'Faz a extração do texto e reprocessar o arquivo', 'onclick'=>'fReprocess(true);'];
                }

                if(in_array($user_logged_level,['dev'])){
                    $arr['upd_text']=['icon'=>'fa-floppy-o', 'title'=>'Atualizar Texto Base', 'onclick'=>'fUpdText()'];
                    $arr['upd_quiver_id']=['icon'=>'fa-floppy-o', 'title'=>'Atualizar Quiver ID', 'onclick'=>'fUpdQuiverId()'];
                }
                if(in_array($user_logged_level,['dev','superadmin'])){
                    $arr['upd_status_code']=['icon'=>'fa-floppy-o', 'title'=>'Atualizar Código de Erro', 'onclick'=>'fUpdStatusCode()'];
                    $arr['upd_file_pass']=['icon'=>'fa-floppy-o', 'title'=>'Atualizar Senha de Arquivo', 'onclick'=>'fUpdFilePass()'];
                }

                $arr['details']=['icon'=>'fa-info-circle', 'title'=>'Informações adicionais', 'onclick'=>'$(\'#dev-info-data\').fadeToggle();'  ];
                $arr['log']=['icon'=>'fa-list-ul', 'title'=>'Logs deste registro', 'link'=>URL::to('/') .'/super-admin/logs?area_name=process_robot&area_id='. $model->id, 'attr'=>'target=_blank'  ];
                $arr['log_manual']=['icon'=>'fa-list-ul', 'title'=>'Adicionar log manual', 'link'=>URL::to('/') .'/super-admin/logs/add?area_name=process_robot&area_id='. $model->id, 'attr'=>'target=_blank'  ];

                if($user_logged_level=='dev'){
                    $arr['dd_data']=['icon'=>'fa-code', 'title'=>'Acessar Dados Internos', 'link'=>route($prefix.'.app.get',['process_cad_apolice','dd_view']).'?id='.$model->id, 'attr'=>'target=_blank'  ];
                }

                echo view('templates.components.button',['title'=>false,'icon'=>'fa-codepen','alt'=>'Opções Desenvolvedor','class'=>'','sub'=>$arr]);
                echo '<span class="j-icon-loading fa fa-refresh fa-spin" style="margin-left:10px;display:none;"></span>';
        }

    echo '</div>';


    echo '<div class="col-sm-3">';
        if(isset($termsList)){
            $r='';
            foreach($termsList as $term){
                $taxs=$taxs_list[$term->id];
                $r.='<span style="font-size:small;display:block;margin-bottom:6px;">'. ($taxs->count()>0 ? $term->term_title : '') .' <br></span> ';
                //dd($taxs->count(),$taxs);
                $r.=view('templates.ui.tag_item_list',[
                        'taxRel'=>$taxs,
                        'term_id'=>$term->id,
                        'area_name'=>'cad_apolice'
                    ])->render();
            }
            if($r)echo $r;
        }
    echo '</div>';

echo '</div><br>';




//mensagem prinicipal de retorno **********
        $s=$model->process_status;
        $msg=[];
        if($s=='i'){//está ignorado, portanto não precisa exibir as mensagens de erro
            $msg[]=$thisClass::getStatusCode($model_data['error_msg'],false);
            $is_block_collapsed=true;

        }elseif(in_array($s,['0','o'])){//nestes status, não precisa exibir as mensagens de erro
            $msg[]=$model->status_long_label;
            $is_block_collapsed=true;

        }elseif($s=='c' && in_array($model_data['error_msg'],['extr04'])){
            //estes são erros que devem aparecer para o operador conforme descrito código de erro
            $msg[]=$thisClass::getStatusCode($model_data['error_msg'],false);
            $is_block_collapsed=true;

        }elseif($dataPdf){//quer dizer que está indexado
            //dump($execs_count,$model_data['ctrl_changes']);
            //IF ainda não ajustado ....: if($execs_count>0 || $model_data['ctrl_changes'] || true){//existem alterações para exibir
            //dump($status_color[$model->process_status]);
            //captura a msg a partir de $execsFinal
                foreach($blocks_list as $block => $label){
                    $c=array_get($execsFinal,$block.'._status_code');
                    if($c && $c!='ok' && $c!='ok2')$msg[]=$label.': '. $thisClass::getStatusCode($c).'<br>';
                }

            //captura a msg a partir do validate dos campos
                if($validate_not){
                    $msg=[];
                    $m='';
                    foreach($validate_not as $f=>$v){
                        if($v)$m.='<strong>'. __PCALabel($f,$segDados_label,$segParcelas_label,$segProd_label) .'</strong>: <span class="nostrong">'.$v.'</span><br>';
                    }

                    if(isset($custom_codes_fields_edit[$model_data['error_msg']])){//é igual aos códigos de erros setados em $custom_codes_fields_edit
                        //deve setar o título do erro em $model_data['error_msg'], mas não precisa informar a mensagem personalizada em $m (pois será o mesmo tipo de mensagem);
                        $msg[]=$thisClass::getStatusCode($model_data['error_msg'], false);

                    }else if(count($validate_not)==1 && $model_data['error_msg']!='read01'){//só tem uma msg de erro
                        $tmp = $thisClass::getStatusCode($model_data['error_msg'], false);
                        $msg[]=($tmp ? $tmp.'<br>' : '').$m;

                    }else{
                        $msg[]='Campos inválidos <div style="font-size:14px;line-height:130%;margin:5px 0 0 0;">'.$m.'</div>';
                    }
                }else{
                    if(in_array($s,['p','0','a'])){
                        $msg=[];//reseta a var
                        $msg[]=$model->status_long_label;
                    }
                }
                //dd($msg);
            //caso não tenha msg, seta o valor padrão de data error_msg
                if(!in_array($s,['f','w']) && in_array($model_data['error_msg'],['ok','ok2']))$model_data['error_msg']='';//se estiver finalizado, não precisa informar o status 'ok'
                if(!$msg && $model_data['error_msg'])$msg = [$thisClass::getStatusCode($model_data['error_msg']),false];
        }else{
            $n=$thisClass::getStatusCode($model_data['error_msg'],false);
            if(!$n)$n=$model->status_long_label;
            if($n){
                $msg[]=$n;
                $is_block_collapsed=true;
            }
        }
        //dd(11,$msg);


        if($msg)$msg=join('',$msg);
        if($msg){
            echo '<div class="row-max1 block-msg-main callout '. $status_color[$model->process_status]['bg'] .'" style="border-color:rgba(0,0,0,0.3);">
                    <h4 class="no-margin strong" title="'. ($model_data['error_msg']??false?'Código: '.$model_data['error_msg']:'') .'">'.
                        (($model_data['req_fill_manual']??false)=='s' ? '<span class="fa fa-exclamation-circle fa-red margin-r-10" title="Este arquivo só pode ser emitido com a edição manual dos dados pelo operador"></span>' : '') .
                        ($s=='a' || $s=='o' ? '<span class="fa fa-circle-o-notch fa-spin margin-r-5"></span> ' : '').
                        $msg .'</h4>';

                    if(in_array($s,['e','c']) && $model_data['error_msg'] && Error::exists($model_data['error_msg'])){
                        echo '<a href="#" onclick="$(this).next().fadeToggle(0);" style="float:right;margin:-20px 0 0 0;">veja mais</a>';
                        echo '<div class="hiddenx">'.
                                (in_array($user_logged_level,['dev','superadmin']) && $exec_error_msg ? '<br>'.view('admin.process_robot.cad_apolice.show_error_msg_execs',['status_code'=>$model_data['error_msg'],'error_msg'=>$exec_error_msg]) : '') .
                                view('errors.templates.show_error_msg',['error_code'=>$model_data['error_msg']]) .
                            '</div>';
                    }

            echo '</div>';
        }


//*** div de informaçoes para o desenvolvedor e superadmin ***
if(in_array($user_logged_level,['dev','superadmin'])){

    try{
        $pdf_engine = App::make('App\\ProcessRobot\\cad_apolice\\'. $model->process_prod .'\\'. $model->insurer->insurer_basename .'Class')->getPdfEngine();
    }catch(\Exception $e){
        $pdf_engine = '';
    }
    $pdf_engine_change = $model_data['pdf_engine_change']??'';
        //monta a exibição
        if($pdf_engine_change && $pdf_engine!=$pdf_engine_change){
            $pdf_engine =   '<span class="text-red strong">Alterado dinamicamente para '.
                                strtoupper($pdf_engine_change) .
                                ($pdf_engine ? '<span class="text-blue">(de '. strtoupper($pdf_engine) .')</span>' : '') .
                            '</span>';
        }else{
            $pdf_engine = '-';
        }


    echo '<div id="dev-info-data" class="box box-primary box-widget hiddenx row-max1"><div class="box-body no-padding row">
            <div class="col-sm-6">
                <table class="table no-margin">
                    <tr><td width="200">Cadastro</td><td>'. $model->created_at .'</td></tr>
                    <tr><td>Atualização / Processamento</td><td>'. $model->updated_at .'</td></tr>
                    <tr><td>Processado pelo Robô </td><td>'. ($robotModel ? $robotModel->robot_name .' #'.$robotModel->id : '-').'</td></tr>
                    <tr><td>Processo Agendado</td><td>'. ($model->process_next_at?'<strong class="text-teal">'.$model->process_next_at.'</strong>':'Não') .'</td></tr>
                    <tr><td>Método de Extração</td><td>'. $pdf_engine .'</td></tr>
                </table>
            </div>
            <div class="col-sm-6">
                <table class="table no-margin">
                    <tr><td class="col-label" width="200">Documento Quiver</td><td>'. array_get($model_data,'quiver_id','-') .'</td></tr>
                    <tr><td>Origem do Documento</td><td>';
                        if($model->process_auto){
                            echo 'Área de Seguradoras';
                        }else{
                            echo 'Manual';
                        }
                        //procura os ids da tabela process_robot[seguradora_files] associados
                        $prRegs = \App\Models\PrSeguradoraFiles::where('process_rel_id',$model->id)->get();
                        if($prRegs->count()>0){
                            echo '<table>';
                            foreach($prRegs as $pr){
                                echo '<br><a class="strong" href="'. route($prefix.'.app.show',['process_seguradora_files',$pr->process_id]) .'?rel_id='. $model->id .'" target="_blank">'. $pr->process_id .'</a>';
                            }
                            echo '</table>';
                        }else{
                            echo '<br>Erro: Nenhum registro associado na tabela pr_seguradora_files';
                        };

                echo'</td></tr>';


                    $n=$model_data['st_change_user']??null;
                    if($n){
                        list($xstatus,$xuser,$xdt)=explode('|',$n);//esperado: status|user_id|datetime
                        $xuser = \App\Models\User::find($xuser);
                        $str = ($xuser?$xuser->user_name:'(err)') .' - '. FormatUtility::dateFormat($xdt);
                    }else if(in_array($model->process_status,['f','w'])){
                        $str='Robô';
                    }else{
                        $str='-';
                    }
                echo'<tr><td>Finalizado '. ($n || $str=='-'?'por':'pelo') .'</td><td title="Campo st_change_user:'. $n .'">'. $str .'</td></tr>';
                    $n=$dataApoliceCheck['_model_data']??null;
                    if($n){
                        echo '<tr><td>Verificado na Seguradora</td><td>'.
                                '<a href="'. route($prefix.'.app.get',['process_seguradora_data','show']) .'?process_prod='.$n->process_prod.'&id='.$n->process_id.'" target="_blank">'. ('\App\Http\Controllers\Process\ProcessSeguradoraDataController'::$status_pr[$n->status]??'-') .' #'. $n->process_id.'</a>'.
                            '</td></tr>';
                    }


                    $n=$model_data['boleto_seg']??null;
                    if($n){
                        echo '<tr><td title="Baixa de Boletos nos Sites das Seguradoras">Baixa de Boletos Segs</td><td>'. \App\Http\Controllers\Process\ProcessSeguradoraDataController::$status_pr[$n] .'</td></tr>';
                    }


           echo '</table>
            </div>
        </div></div>';
}


//verifica se existe o padrão do log da baixa antigo (da versão 1.0 // atualização em 07/01/2021)
    if($log_data_v1){
        echo '<div class="box box-primary box-widget row-max1">
            <div class="box-header" onclick="shTableRows($(this),$(this).closest(\'.box-primary\'),\'auto\',\'.box-body\');"><span class="margin-r-5">Log da Baixa (versão 1.0)</span> <span class="fa icon-collapse fa-angle-down" data-icon="fa-angle-right|fa-angle-down"></span></div>
            <div class="box-body no-padding log_v1">';


        echo view('templates.ui.view',['class'=>'margin-bottom-none','data'=>[
                ['title'=>'Tipo','value'=>(VarsProcessRobot::$typesApolices[$data_type]??'none')],
                ['title'=>'Nº Apólice','value'=>($model->process_ctrl_id?$model->process_ctrl_id:'-')],
            ]]);

        echo view('admin.process_robot._includes.show_msg_return',[
            'robot_data' =>$log_data_v1,
            'model'=>$model,
            'user_logged_level'=>$user_logged_level
        ]);
        echo '</div></div>';
        echo '<style>
            .log_v1 .tbl_msg td{padding:3px 1px;border-bottom:1px solid #e2e2e2;}
            .log_v1 .st_R,.log_v1 .st_E{color:#888;}
            .log_v1 .is_last.st_R{color:#3399ff;}
            .log_v1 .is_last.st_E{color:red;}
            .log_v1 .is_last{font-weight:600;opacity:1;}
            </style>';
    }





//monta a tabela de histórico de execuções ************
if($execsModel->count()>0){
    echo '<div class="box box-primary box-widget row-max1">
        <div class="box-body no-padding">
        <table class="table no-margin">
        <thead>
            <tr><th colspan="2" onclick="shTableRows($(this),$(this).closest(\'table\'),\'auto\');">
                <span class="margin-r-5">Processamentos do Robô</span> <span class="fa icon-collapse fa-angle-down" data-icon="fa-angle-right|fa-angle-down"></span>
            </th><th>Tempo</th>
            <th>';
                if($execsModel->count()>0){
                    foreach($blocks_list as $block=>$label){
                        echo '<span class="itemx1">'.$label.'</span>';
                    }
                }else{
                    echo 'Retorno';
                }
    echo   '</th>
        </thead><tbody>
        </tr>';
    //echo'<tr><td>-</td><td>'. FormatUtility::dateFormat($model->created_at) .'</td><td>-</td><td>-</td></tr>';
        foreach($execsModel as $reg){
            $n = $reg->process_end ? FormatUtility::dateDiffFull($reg->process_start,$reg->process_end) : '-';
            $s = $reg->status_code;
            $m = $reg->_msgs;
            echo '<tr class="tr-status-'. ($s=='ok' || $s=='ok2' ? 'ok': ($s?'err':'none') ) .'" data-status-code="'.$s.'">
                    <td width="50">'. $reg->id .'</td>
                    <td width="200">'. FormatUtility::dateFormat($reg->process_start) .'</td>
                    <td width="150">'. $n .'</td>
                    <td width="*">'. ($m ? $m :    (!in_array($model->process_status,['p','a'])? 'Não iniciado' : '<span class="fa fa-circle-o-notch fa-spin margin-r-5"></span> Aguardando retorno do robô')     ) .'</td>
                </tr>';
        }
    echo '</tbody></table></div></div>';
}



if($dataPdf){//a variável está vazia, quer dizer que está indexado
        //monta a tabela de dados em html ************
        echo '<div class="box box-primary box-widget row-max1 hiddenx" id="table-seg-base"><div class="box-body no-padding">';


            echo '<div class="table-seg-view-wrap" id="table-seg-view-wrap-clone">'.
                    '<table class="table no-margin table-bordered table-wauto table-seg-view"></table>'.
                 '</div>';


            echo '<div class="table-seg-view-wrap table-responsive scrollmin" id="table-seg-view-wrap">'.

                '<table class="table no-margin table-bordered table-wauto table-seg-view" id="table-seg-view">';

            echo '<thead><tr>';// data-field="'.$field.'"
            foreach($datacol as $col => $label){
                //lógica: mescla as colunas execs no cabeçalho deixando apenas a primeira
                $attr='';
                if($col=='exec_1'){
                    $attr='id="head_col_exec_1"';
                }else if(substr($col,0,5)=='exec_'){
                    //$label='';
                }

                if($col=='label'){
                    echo '<td data-col="'.$col.'" class="col-'.$col.'" '. $attr .'  onclick="shTableRows($(this),$(\'#table-seg-view\'),\'auto\');"><span class="margin-r-5">'. $label .'</span> <span class="fa icon-collapse fa-angle-down" data-icon="fa-angle-right|fa-angle-down"></span></td>';
                }else{
                    echo '<td data-col="'.$col.'" class="col-'.$col.'" '. $attr .'>'. $label .'</td>';
                }
            }
            echo '</tr></thead>';


            echo '<tbody class="tbody-head"><tr>';
                foreach($extracol as $col => $label){
                    echo '<td data-col="'.$col.'" class="col-'.$col.' col-head"><div class="text-truncate">'. $label .'</div></td>';
                }
            echo '</tr></tbody>';

            echo '<tbody class="tbody-data">';


            //dados do seguro
            __PCAWriteBlockItemView($datablocks,$segDados_label,$countcol,$dataarr,$ctrlDados_user,$ctrlDados_robo,$dataPdf,'dados',$execsArr,$execsFinal,$execs_count, $validate_not,$thisClass, $segDadosClass::fields_ignore_show());


            //dados do produto/ramo
            $blockname=$model->process_prod;
            echo '<tr data-group="'.$blockname.'" class="tr-group-name tr-group-col-'.$blockname.' no-select j-collapse-rows">';
                    echo '<td data-col="label" class="col-label">
                            <span class="margin-r-5">'. $segProd_title. '</span>
                            <span class="fa fa-angle-down icon-collapse" data-icon="fa-angle-right|fa-angle-down"></span>
                        </td>';


                    echo '<td data-col="quiver" class="col-quiver"></td>';  //__PCAWriteBlockItemView_x1([$execsFinal],$blockname,'quiver');
                    echo '<td data-col="apolice_check" class="col-apolice_check"></td>';  //__PCAWriteBlockItemView_x1([$execsFinal],$blockname,'quiver');
                    echo '<td data-col="pdf" class="col-pdf"></td>';
                    echo '<td data-col="manual" class="col-manual"></td>';

                    __PCAWriteBlockItemView_x1([$execsFinal],$blockname,'execfinal',$thisClass);
                    if($execsArr)__PCAWriteBlockItemView_x1($execsArr,$blockname,'exec',$thisClass);

            echo '</tr>';
            for($i=1;$i<=$segProd_count;$i++){//obs: por enquanto existe apenas o produto / item
                foreach($segProd_label as $field=>$label){
                    echo __PCAShowItem01($blockname, $dataarr, $field, 'prod{'.$i.'}|'.$field, ($ctrlProd_user[$i-1]??null), ($ctrlProd_robo[$i-1]??null), $dataPdf, $execsArr, $execsFinal, $validate_not, $segProdClass::fields_ignore_show(), $i);
                }
            }


            //dados do premio
            __PCAWriteBlockItemView($datablocks,$segDados_label,$countcol,$dataarr,$ctrlDados_user,$ctrlDados_robo,$dataPdf,'premio',$execsArr,$execsFinal,$execs_count, $validate_not, $thisClass, $segDadosClass::fields_ignore_show());


            //dados das parcelas
            $blockname='parcelas';
            echo '<tr data-group="'.$blockname.'" class="tr-group-name tr-group-col-'.$blockname.' no-select j-collapse-rows">';
                    echo '<td data-col="label" class="col-label">
                            <span class="margin-r-5">Parcelas</span>
                            <span class="fa fa-angle-down icon-collapse" data-icon="fa-angle-right|fa-angle-down"></span>
                        </td>';

                    echo '<td data-col="quiver" class="col-quiver"></td>';  //__PCAWriteBlockItemView_x1([$execsFinal],'parcelas','quiver');
                    echo '<td data-col="apolice_check" class="col-apolice_check"></td>';
                    echo '<td data-col="pdf" class="col-pdf"></td>';
                    echo '<td data-col="manual" class="col-manual"></td>';

                    __PCAWriteBlockItemView_x1([$execsFinal],'parcelas','execfinal',$thisClass);
                    if($execsArr)__PCAWriteBlockItemView_x1($execsArr,'parcelas','exec',$thisClass);
            echo '</tr>';
            for($i=1;$i<=$count_prestacoes;$i++){
                foreach($segParcelas_label as $field=>$label){
                    if($field=='num')continue;
                    echo __PCAShowItem01($blockname, $dataarr, $field, 'parcela{'.$i.'}|'.$field, ($ctrlParcelas_user[$i-1]??null), ($ctrlParcelas_robo[$i-1]??null), $dataPdf, $execsArr, $execsFinal, $validate_not, $segParcelasClass::fields_ignore_show(), $i);
                }
            }


            //dados do anexo
            __PCAWriteBlockItemView($datablocks,$segDados_label,$countcol,$dataarr,$ctrlDados_user,$ctrlDados_robo,$dataPdf,'anexo',$execsArr,$execsFinal,$execs_count, $validate_not,$thisClass, $segDadosClass::fields_ignore_show());




            echo '</tbody>';
            echo '<tbody><tr><td colspan="'. ($execs_count+5) .'">&nbsp;</td></tr></tbody>';//gera apenas uma linha para espaço adicional no final da tabela. Motivo: deixar espaço para o recurso popover que não está sobreponto o espaço da tabela
            echo '</table>';

            echo '</div>';//end => .table-seg-view-wrap




        echo '</div></div>';
}


$boleto_seg = $model->getBoletoSeg();
//if(\Auth::user() && \Auth::user()->user_level=='dev')dd($boleto_seg);
if($boleto_seg){
    echo '<div id="boleto_seg" class="box box-primary box-widget row-max1">
            <div class="box-header with-border" onclick="shTableRows($(this),$(this).closest(\'#boleto_seg\'),\'auto\',\'.box-body\');">
                <h3 class="box-title">Boletos Disponíveis</h3> <span class="fa icon-collapse fa-angle-down" style="font-size:14px;margin-left:5px;" data-icon="fa-angle-right|fa-angle-down"></span>'.

                ( in_array($user_logged_level,['dev','superadmin']) ? '<a href="'. URL::to('/') .'/super-admin/logs?area_name=seguradora_data.boleto_seg,seguradora_data.boleto_quiver&area_id='. $model->id .'" target="_blank" style="margin-left:30px;" >Logs</a>' : '').


           '</div>
            <div class="box-body no-padding">
                <table class="table no-margin" style="width:400px;">
                    <tr><th width="5%">Parcela</th><th>Valor</th><th>Vencimento</th><th>Boleto</th></tr>
                ';
                foreach($boleto_seg as $num => $arr){
                    if($num=='all'){
                    echo '<tr>'.
                            '<td>Todos</td>'.
                            '<td>-</td>'.
                            '<td>-</td>'.
                            '<td><a href="'. $arr['url'] .'" target="_blank">Acessar</a></td></tr>';
                    }else{
                    echo '<tr>'.
                            '<td>'.$num.'</td>'.
                            '<td>'. $arr['valor'] .'</td>'.
                            '<td>'. $arr['datavenc'] .'</td>'.
                            '<td><a href="'. $arr['url'] .'" target="_blank">Acessar</a></td></tr>';
                    }
                }
          echo '</table>
            </div>
        </div>';

}


//*** div de informaçoes para o operador ***
$obs_operator = \App\Services\MetadataService::get('process_robot', $model->id, 'obs_operator');

if($obs_operator){
    echo '<div id="dev-info-data" class="box box-primary box-widget row-max1">
            <div class="box-header with-border" onclick="shTableRows($(this),$(this).closest(\'#dev-info-data\'),\'auto\',\'.box-body\');">
                <h3 class="box-title">Observações ao Operador</h3> <span class="fa icon-collapse fa-angle-down" style="font-size:14px;margin-left:5px;" data-icon="fa-angle-right|fa-angle-down"></span>'.
                (in_array($user_logged_level,['dev','superadmin']) ? '<a href="#" class="btn btn-default btn-xs" id="btn_obs_operator" style="margin-left:10px;">Editar</a>' : '').'
            </div>
            <div class="box-body ">
               <span>'. ($obs_operator ? nl2br($obs_operator) : '-') .'</span>
               <div id="obs_operator_textarea" style="display:none;">'. $obs_operator .'</div>
           </div>
        </div>';
}else{
    if(in_array($user_logged_level,['dev','superadmin'])){
        echo '<p><a href="#" class="btn btn-link btn-xs strong" id="btn_obs_operator">Adicionar Observaçao ao Operador</a></p>';
    }
}



//*** div de informaçoes para o desenvolvedor e superadmin ***
$obs_admin = $model_data['obs_admin']??false;
$obs_admin .= ($obs_admin ? chr(10) : '') . \App\Services\MetadataService::get('process_robot', $model->id, 'obs_admin');

if($obs_admin){
    echo '<div id="dev-info-data" class="box box-primary box-widget row-max1">
            <div class="box-header with-border" onclick="shTableRows($(this),$(this).closest(\'#dev-info-data\'),\'auto\',\'.box-body\');">
                <h3 class="box-title">Observações ao Suporte</h3> <span class="fa icon-collapse fa-angle-down" style="font-size:14px;margin-left:5px;" data-icon="fa-angle-right|fa-angle-down"></span>'.
                (in_array($user_logged_level,['dev','superadmin']) ? '<a href="#" class="btn btn-default btn-xs" id="btn_obs_admin" style="margin-left:10px;">Editar</a>' : '').'
            </div>
            <div class="box-body ">
               <span>'. ($obs_admin ? nl2br($obs_admin) : '-') .'</span>
               <div id="obs_admin_textarea" style="display:none;">'. $obs_admin .'</div>
           </div>
        </div>';
}else{
    if(in_array($user_logged_level,['dev','superadmin']))echo '<p><a href="#" class="btn btn-link btn-xs strong" id="btn_obs_admin">Adicionar Observaçao ao Suporte</a></p>';
}






if($is_lock_form_edit){
        //formulário de alteração da apolice  ************
        echo view('templates.ui.form',[
            'class'=>'hiddenx',
            'id'=>'form-edit-segdata',
            'url'=> route($prefix.".app.post",["process_cad_apolice","update_fields"]),
            'data_opt'=>['onSuccess'=>'@function(r){ window.location.reload(); }','onError'=>'@editSegDataOnError'],
            'bt_back'=>'Fechar',

            'content'=>function() use($model,$datablocks,$segDadosClass,$segParcelasClass,$segProdClass,$dataPdf,$segProd_title,$manual_form_dados,$manual_form_parcelas,$manual_form_prod,$is_user_manual_confirm){

                $accordion_data=[];
                echo '<input type="hidden" name="id" value="'.$model->id.'">';

                //dados *****
                $accordion_data['dados']=['title'=>$datablocks['dados']['label'],'content'=>function() use($segDadosClass, $datablocks, $manual_form_dados){
                    $fields_filtered = array_intersect_key($segDadosClass::fields_html(), array_flip($datablocks['dados']['fields']));
                    echo view('templates.ui.auto_fields',[
                            'layout_type'=>'four_column',
                            'autocolumns'=>$fields_filtered,
                            'class'=>'segdados-block segdados-dados-block',
                            'autodata'=>$manual_form_dados,
                        ]);
                }];

                //produto *****
                $accordion_data['prod']=['title'=>$segProd_title,'content'=>function() use($segProdClass, $datablocks, $manual_form_prod, $segProd_title){
                    $fields_filtered=$segProdClass::fields_html();
                        //adiciona o prefixo nos campos
                        $fields_filtered = AutoFieldsService::adjusteData($fields_filtered, 'prod{N}|', false, true, 'array');
                    echo view('templates.ui.auto_fields',[
                            'prefix'=>'prod{N}|',
                            'layout_type'=>'four_column',
                            'autocolumns'=>$fields_filtered,
                            'class'=>'segprod-block',
                            'block_dinamic'=>[
                                'mode'=>'block',
                                'remove'=>false,
                                'add'=>false,
                                'block_title'=>$segProd_title,
                            ],
                            'autodata'=>AutoFieldsService::adjusteData($manual_form_prod, 'prod{N}|'),
                        ]);
                }];


                //premio *****
                $accordion_data['premio']=['title'=>$datablocks['premio']['label'],'content'=>function() use($segDadosClass, $datablocks, $manual_form_dados){
                    $fields_filtered = array_intersect_key($segDadosClass::fields_html(), array_flip($datablocks['premio']['fields']));
                    echo view('templates.ui.auto_fields',[
                            'layout_type'=>'four_column',
                            'autocolumns'=>$fields_filtered,
                            'class'=>'segdados-block segdados-premio-block',
                            'autodata'=> $manual_form_dados,
                        ]);
                }];

                //parcelas *****
                $accordion_data['parcelas']=['title'=>'Parcelas','content'=>function() use($segParcelasClass, $datablocks, $manual_form_parcelas){
                    $fields_filtered=$segParcelasClass::fields_html();
                        //adiciona o prefixo nos campos
                        $fields_filtered = AutoFieldsService::adjusteData($fields_filtered, 'parcela{N}|', false, true, 'array');
                    echo view('templates.ui.auto_fields',[
                            'prefix'=>'parcela{N}|',
                            'layout_type'=>'four_column',
                            'autocolumns'=>$fields_filtered,
                            'class'=>'segparcelas-block',
                            'block_dinamic'=>[
                                'mode'=>'inline',
                                //'remove'=>['confirm'=>true,'ajax'=>route('admin.app.post',['example','testDelAuto'])],
                                'remove'=>['confirm'=>true],
                                //'add'=>false,
                                'numeral'=>true,
                                'block_title'=>'Parcela',
                                'remove_last'=>true,
                            ],
                            'autodata'=>AutoFieldsService::adjusteData($manual_form_parcelas, 'parcela{N}|'),
                        ]);
                }];


                //monta o accordion
                echo view('templates.ui.accordion',['data'=>$accordion_data,'default_hide'=>true,'show_arrow'=>true]);

                //campo de confirmação manual
                echo view('templates.components.checkbox',['name'=>'user_manual_confirm','list'=>['s'=>'Confirmo que os dados preenchidos estão corretos'], 'value'=>($is_user_manual_confirm?'s':''), 'attr'=>'style="margin-left:10px;font-size:1em;"','class_item'=>'strong']);  //'class_group'=>($is_user_manual_confirm?'':'hiddenx'),
            }
        ]);
}





//gera um json dos blocos dos processos
$json=[];
foreach($configProcessNames['cad_apolice']['products'] as $prod => $opt){
    if($prod == $model->process_prod)$json[$prod] = ['title'=>$opt['title'],'blocks'=>explode(',',$opt['blocks']) ];
}


Form::loadScript('sticky');

$cell_width=180;
$cell_exec_width=180;
$cell_execfinal_width=80;
@endphp



<script>
var configProcessNames={!!json_encode($json)!!};
var configBlockNames={!! isset($model_data['block_names']) ? json_encode(explode(',',$model_data['block_names'])) : 'null' !!};//já processados
var oTblSegBase=$('#table-seg-base').removeClass('hiddenx');
(function(){
    var oTblViewWrap=$("#table-seg-view-wrap");

    //ajuste scroll da tabela de dados
    var o1=oTblViewWrap.doubleScroll({scrollCss:{height:8}, resetOnWindowResize:true})
            .on('scroll',function(){ oT.css({left:-$(this).scrollLeft()}); });
    //ajuste scroll
    var o2=$('#table-seg-view-wrap-clone');
    var oT=o2.find('table').addClass('relative');
        o1.find("thead:eq(0)").appendTo(oT);
        o2.sticky({topSpacing:50});
    var f1=function(){
        o2.find('.doubleScroll-scroll-wrapper').remove();//remove caso exista
        setTimeout(function(){
            var s1=o2.parent().next();//=.doubleScroll-scroll-wrapper
            if(s1.hasClass('doubleScroll-scroll-wrapper'))s1.appendTo(o2);//move double-scroll in wrap clone
        },100);
    };
    f1();
    $(window).on('resize.doubleScroll',f1);


    //funções nos cabeçalhos
    var oHead=oTblSegBase.find('thead:eq(0)');
    $('<a href="#" class="fa fa-plus-square-o pull-right" title="Detalhar Processos do Robô" style="margin-top:4px;" id="lnk1-show-cols-execfinal" data-icons="fa-plus-square-o|fa-minus-square-o" onclick="fDropdownMenuItem(\'auto\',\'execs\',true);return false;"></a>').appendTo( oHead.find('[data-col=execfinal]') );
    //$('<a href="#" class="fa fa-minus-square-o pull-right" title="Exbir Coluna Resumida" style="margin-top:4px;" onclick="fDropdownMenuItem(false,\'execs\',true);return false;"></a>').appendTo( $('#head_col_exec_1') );
}());


@if($terms_ids)
    //terms
    var terms_id=[{{join(',',$terms_ids)}}],term_id,o,p;
    for(var i in terms_id){
        term_id = terms_id[i];
        o=$('#bt_box_terms_'+term_id);
        p=[o.offset().left,o.offset().top+o.outerHeight()];
        awTaxonomyToObj({
            tax_form:'#autofield_box_terms_'+term_id,
            area_name:'cad_apolice',
            area_id:{{$model->id}},
            tags_item_obj: {term_id:term_id},
            button:o,
            button_pos:p,
        });
    };
@endif


//adiciona classes marcadores nas linhas
function fClsRows(tgr,trs){//tgr - each tr group  //trs - all trs not group
    trs = trs.filter(':visible').removeClass('tr-last');
    var oTr;
    tgr.filter(':visible').each(function(){
        $(this).prevAll(':visible:eq(0)').addClass('tr-last');
    });
    //adiciona na última linha
    trs.filter(':visible:last').addClass('tr-last');
};

//botões de filtros da tabela de dados
//param v = '', manual_changed, quiver_changed, field_err   //mais de um valor separar por virgula
function fFilterRows1(v){
    v=v && v!='allx'?v.split(','):null;
    var table=$('#table-seg-view');
    var tgr=table.find('>tbody.tbody-data>tr.tr-group-name').addClass('hidden-filter1');
    var trs=table.find('>tbody.tbody-data>tr:not(.tr-group-name)').addClass('hidden-filter1');
    var items=$();
    //console.log('v',v)
    //tgr.each(function(){ console.log(this) })
    if(v && $.inArray('manual_changed',v)!==-1)items=items.add( trs.filter('.tr-changed-manual').removeClass('hidden-filter1') );
    if(v && $.inArray('quiver_changed',v)!==-1)items=items.add( trs.filter('.tr-changed-quiver').removeClass('hidden-filter1') );
    if(v && $.inArray('apolice_check',v)!==-1)items=items.add( trs.filter('.tr-apolice_check').removeClass('hidden-filter1') );
    if($.inArray('field_err',v)!==-1)items = items.add( trs.filter(function(){ return $(this).find('td[data-col=manual].code-err').length>0; }).removeClass('hidden-filter1') );
    if($.inArray('exec_err',v)!==-1)items = items.add( trs.filter(function(){ return $(this).find('td.col-quivexec.code-err').length>0; }).removeClass('hidden-filter1') );//.td.col-quivexec.code-err.td-changed
    if(v && $.inArray('all',v)!==-1)items = items.add( trs.filter(':not(.tr-igshow-not_quiver)').removeClass('hidden-filter1') );
    //console.log('items',items.length)
    //exibe as linhas dos grupos das respectivas linhas visíveis
    var groups={};
    items.each(function(){
        var o=$(this);
        var g=o.attr('data-group');
        if(!groups[g]){
            o.prevAll('.tr-group-name:eq(0)').removeClass('hidden-filter1');
            groups[g]=true;
        }
    })

    if(!v){//all
        trs.removeClass('hidden-filter1');
        tgr.removeClass('hidden-filter1');
    };

    fClsRows(tgr,trs);
};
var oFilterRows = $('#filter_rows1').on('change',function(){fFilterRows1(this.value);});
fFilterRows1(oFilterRows.val());//inicializa



//ajustes na tabela de dados
(function(){
    var oTblSeg=$('#table-seg-view');

    //botão de colapsar os blocos
    var oTGrsCollpase=oTblSeg.find('.j-collapse-rows').on('click collapse-in',function(e){
        var td=$(this);
        var icon=td.find('.icon-collapse');
        var ics=icon.attr('data-icon').split('|');
        icon.removeClass(ics[0]+' '+ics[1]);

        var tr=icon.closest('tr');
        var rows=tr.nextAll('[data-group='+ tr.attr('data-group') +']');
        //console.log(rows);return;

        if(e.type!='collapse-in' && tr.hasClass('is-collapsed')){
            rows.removeClass('hidden-collapse');
            tr.removeClass('is-collapsed');
            icon.addClass(ics[1]);
        }else{
            rows.addClass('hidden-collapse');
            tr.addClass('is-collapsed');
            icon.addClass(ics[0]);
        }
    });
    @if($is_block_collapsed)
    oTGrsCollpase.trigger('collapse-in');
    @endif

    //clique na linha
    var oLastTd=null;
    var oTds=oTblSeg.find('.tbody-data > tr:not(.tr-group-name) td');
    oTds
        .on('click',function(e){console.log()
            if($(this).attr('data-content') && !$(e.target).attr('data-dismiss')){
                e.stopPropagation();
                if(oLastTd==null || oLastTd!=this){
                    oLastTd=this;
                    oTds.not(this).popover('hide');
                    $(this).popover({container:this}).popover('show');
                }
            }
        });
    oTds.filter('.col-manual').prepend('<span class="bt-edit btn btn-xs"><i class="fa fa-pencil no-events"></i></span>')
        //.popover({container:'body'})
        .on('click',function(e){
            var td=$(this);
            if($(e.target).hasClass('bt-edit') && !$(e.target).hasClass('fa')){
                var tr=td.closest('tr');
                if(tr.hasClass('j-disable-edit'))return;
                var field=tr.attr('data-field-to-form');
                td.popover('hide');
                editSegData(field,td);
            }
        });
    $(document.body)
        .on('click', '.popover .close',function(){
            $(oLastTd).popover('hide');
        }).on('click',function(e){
            $(oLastTd).popover('hide');
            oLastTd=null;
        });
}());

//exibe/oculta somente as linhas da tabela deixando o cabeçalho
function shTableRows(oThis,oTable,sh,sel_content){//sh = true,false,auto
    var os=oTable.find(sel_content ? sel_content : 'tbody');
    if(sh=='auto')sh=os.hasClass('hidden-tablerows');

    var icon=oThis.find('.icon-collapse');
    var ics=icon.attr('data-icon').split('|');
    icon.removeClass(ics[0]+' '+ics[1]);

    if(sh){
        os.removeClass('hidden-tablerows');
        icon.addClass(ics[1]);
    }else{
        os.addClass('hidden-tablerows');
        icon.addClass(ics[0]);
    };
};

//exibe/oculta a coluna de execuções
function shColExecs(num,icon){
    var oTbl=$('#table-seg-base');
    var ics=icon.attr('data-icons').split('|');
    icon.removeClass(ics[0]+' '+ics[1]);
    var os=oTbl.find('[data-col=exec_'+num+']');
    sh=os.eq(0).hasClass('exec-col-hide');
    if(sh){
        os.removeClass('exec-col-hide');
        icon.addClass(ics[1]);
    }else{
        os.addClass('exec-col-hide');
        icon.addClass(ics[0]);
    };
};

//form: Função que abre o formulário
function editSegData(field,oTd){//oTd - cell td from table-seg
@if($is_lock_form_edit)
    var oHtml=null;
    var oModal=awModal({
        //zIndex:99999,
        title:false,
        btClose:false,
        removeClose:false,
        hideBg:false,
        height:'hmax',
        width:'lg',
        padding:40,
        html:function(obj){
            if(!oHtml){
                var f=$('#form-edit-segdata').appendTo(obj).removeClass('hiddenx');
                f.find('.bt-back').on('click',function(){ oModal.modal('hide'); });
                oHtml=obj;

                var fxv=function(n){//converte de name{1}|field para field_1
                    var a=n.match(/\{\d+\}/);
                    if(a)n=n.substr(n.indexOf('|')+1)+'_'+a[0].replace('{','').replace('}','');
                    return n;
                };

                var oCtrlDiv = obj.find('.control-div');
                $('<span class="fa fa-info-circle text-muted form-icon-info1"><span>').prependTo(oCtrlDiv)
                    .on('click',function(){
                        var o=$(this).closest('.form-group');
                        var input=o.find('.form-control');
                        var v1=$('tr[data-field-to-form="'+ input.attr('name') +'"]').attr('data-col-pdf');
                        var r='Original da Apólice: <b>'+ (v1?v1:'Não existe') +'</b>';
                        awModal({title:o.find('.control-label').text(), descr:r});
                    });
                /*var oLastIc=null;
                oCtrlDiv.on('mouseenter',':input',function(){
                    if(oLastIc)oLastIc.hide();
                    oLastIc=$(this).closest('.control-div').find('.form-icon-info1').show()
                });*/
            };

            //expande o acoordion com o focus no campo
            var o=oHtml.find(field ? '[name="'+field+'"]' : '.form-group :input:eq(0)');
            var a=o.closest('.panel-collapse').prev();if(a.hasClass('collapsed'))a.click();
            setTimeout(function(){ o.focus(); },500);

            if(oTd){
                var m=oTd.attr('data-validate');
                if(m)o.trigger('msg',m).next().addClass('text-truncate');
            }
        }
    });
@endif
};

//Marca o registro como finalizado
function editMarkStatusF(){
    if(confirm('Marcar este registro como Finalizado')){
        awAjax({
            url: '{{route($prefix.".app.post",["process_cad_apolice","changeAllStatus"])}}',data:{'ids[]':['{{$model->id}}'],status:'f'},processData:true,
            success: function(){window.location.reload();}
        });
    }
}

function editSegDataOnError(r){
    var field=$.type(r.msg)=='object' ? Object.keys(r.msg)[0] : null;
    var oTbl=$('#form-edit-segdata');
    if(field){
        //localiza o campo dentro do form accordion e expande caso esteja oculto
        var o=oTbl.find('[name="'+field+'"]');
        if(o.length==0)return;
        var a=o.closest('.panel-collapse').prev();//if(a.hasClass('collapsed'))a.click();
        if(a.hasClass('collapsed')){//está oculto o respectivo painel do acoordion
            a.click();
            setTimeout(function(){ o.focus(); },300);
        }

    };
    if(r.allow_manual_confirm){
        setTimeout(function(){ oTbl.find('#form-group-user_manual_confirm').hide().fadeIn(); },500);
    };
}



//executa o reprocessamento/extração
function fReprocess(force,xconfirm,cb){//force=true
    if(xconfirm!==false){
        if(!confirm(force===true ? 'Forçar extração e reprocessar apólice PDF no Quiver' : 'Reprocessar apólice PDF no Quiver?'))return;
    }
    var icon_loading=$('.j-icon-loading').show();
    awBtnPostData({
        url:'{{route($prefix.".app.post",["process_cad_apolice","reprocessFile"])}}',
        data:{id:'{{$model->id}}',force:(force===true?'s':'')},
        cb:function(r){
            icon_loading.hide();
            if(r.success){
                if(cb){
                    cb.call(null,r);
                }else{
                    alert("Dados reprocessados com sucesso");
                    window.location.reload();
                }
            }else{
                if(cb){
                    cb.call(null,{success:false,msg:r.msg});
                }else{
                    alert("Erro ao processar: "+ r.msg);
                    if(!r.data)window.location.reload(); // se r.data existir, quer dizer contém dados de retorno de erro que precisam estarem visíveis (teste de programador)
                }
            };
        }
    });
};


//janela de atualização de status
function editOpen(status_open){
    var oModal = awModal({
        title:'Alterar Status',
        form:'method="POST" action="{{route($prefix.".app.post",["process_cad_apolice","changeAllStatus"])}}"',
        html:function(oHtml){

            var r='<div class="form-group">'+
                    '<label class="control-label">Novo Status</label>'+
                    '<div class="control-div">'+
                        '{!! Form::select("status",[""=>""]+$status_list_change,"",["class"=>"form-control","id"=>"field-status"]) !!}'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'+
                '<div class="form-group hiddenx" id="div-fields-process">'+
                     @if(in_array($user_logged_level,['dev','superadmin']))
                    '<label class="control-label" title="Para limpar o campo, digite: 00/00/0000 00:00">Agendar processo <span class="fa fa-info" style="margin-left:5px;"></label>'+
                    '<div class="control-div">'+
                        '{!! Form::text("next_at","",["placeholder"=>"dd/mm/aaaa hh:mm","class"=>"form-control","data-mask"=>"99/99/9999 99:99"]) !!}'+
                        '<span class="help-block"></span>'+
                    '</div>'+
                    '<br>'+
                    @endif
                    '<label class="control-label">Processar Blocos</label>'+
                    '<div class="control-div">';

                    var i,x,a,b,checked;
                    for(i in configProcessNames){
                        a=configProcessNames[i];
                        r+='<strong class="margin-r-5">'+a.title+':</strong> ';
                        for(x in a.blocks){
                            b=a.blocks[x];
                            checked = configBlockNames===null || $.inArray(b,configBlockNames)===-1;
                            r+='<label class="nostrong margin-r-5"><input type="checkbox" name="block_names[]"'+ (checked?' checked':'') +' value="'+ b +'"><span class="checkmark" style="transform:scale(0.9);"></span> '+ b +'</label> ';
                        }
                        r+='<br>'
                    }

                    r+= '<span class="help-block"></span>'+
                    '</div>'+
                '</div>'+
                '';
            oHtml.html(r);
            oHtml.find('#field-status').on('change',function(){
                var o=oHtml.find('#div-fields-process').hide();
                var v=this.value;
                if(v=='0' || v=='p')o.show();//0 indexação, p pronto robo
            });
            if(status_open)oHtml.find('select [value='+status_open+']').prop('selected',true).change();
        },
        btSave:'Alterar',
        form_opt:{
            dataFields:{'ids[]':[{{$model->id}}]},
            @if(in_array($user_logged_level,['admin','user']))
            onBefore:function(opt){
                if($('#field-status').val()=='f' && !confirm('Ao confirmar como FINALIZADO, não poderá ser mais alterado. Deseja continuar?')){window.location.reload();return false;}
            },
            @endif
            onSuccess:function(opt){
               window.location.reload();
            }
        }
    });
};

//Limpa o campo de agendamento
function nextAtClear(){
    if(confirm('Remover o agendamento deste processo?'))
    awAjax({
        url: '{{route($prefix.".app.post",["process_cad_apolice","clear_next_at"])}}',data:{id:'{{$model->id}}'},processData:true,
        success: function(){window.location.reload();}
    });
};


//eventos do menu
function fDropdownMenuItem(th,cmd,markMenuChkBox){
    //th    - (object) this menu checkebox | (boolean) | auto
    //cmd   - (string) datapdf, execs
    //markMenuChkBox - boolean
    var t= (typeof th === "boolean") ? th : (th=='auto' ? !$('#menu-'+cmd).prop('checked') : $(th).find('input').prop('checked') );
    if(t){
        oTblSegBase.addClass('cols-view-'+cmd);
    }else{
        oTblSegBase.removeClass('cols-view-'+cmd);
    };
    if(markMenuChkBox)$('#menu-'+cmd).prop('checked',t);
    $(window).resize();

    //icon plus/minus in col execfinal
    var o=$('#lnk1-show-cols-execfinal');
    var ic=o.attr('data-icons').split('|');
    o.removeClass(ic[0]+' '+ic[1]).addClass(ic[ t?1:0 ]);
};


@if(in_array($user_logged_level,['dev']))
    function fUpdText(){
        var oModal=awModal({
            title:'Atualizar texto da apólice',
            html:function(oHtml){
                oHtml.html(
                    '<input type="hidden" name="process_id" value="{{$model->id}}" />'+
                    '<div class="row">'+
                        '<div class="form-group col-sm-12">'+
                            '<div class="control-div">'+
                                '<textarea class="form-control" name="file_text" placeholder="" rows="10" style="resize: none;"></textarea>'+
                            '</div>'+
                        '</div>'+
                        '<div class="form-group col-sm-12">'+
                            '<div class="control-div">'+
                                '<label class="nostrong margin-r-5"><input type="checkbox" name="upd_status" checked value="s"><span class="checkmark" style="transform:scale(0.9);"></span> Atualizar status para <strong>{{ $thisClass::$status[0] }}</strong> e <strong>reprocessar dados</strong></label> '+
                            '</div>'+
                        '</div>'+
                    '</div>'+
                    ''
                );
            },
            btClose:false,
            btSave:'Salvar',
            form:'method="POST" action="{{route($prefix.'.app.post',['process_cad_apolice','upd_file_text'])}}" accept-charset="UTF-8" ',
            form_opt:{
                onBefore:function(){
                    if(!confirm('Confirmar atualização?'))return false;
                },
                onSuccess:function(r){console.log(r);
                    if(r.success){
                        if(r._form.find('[name=upd_status]').prop('checked')){
                            fReprocess(false,false);
                        }else{
                            window.location.reload();
                        }
                    }
                },
                //fields_log:false
            }
        });
    };
    function fUpdQuiverId(){
        var qid=prompt('Digite o Quiver ID ou "remove" para apagar','{{ $model_data['quiver_id']??'' }}');
        if(!qid)return;
        awAjax({
            url: '{{route($prefix.".app.post",["process_cad_apolice","update_custom_fields"])}}',data:{process_id:'{{$model->id}}',quiver_id:qid},processData:true,
            success: function(){alert('Dados salvos com sucesso');window.location.reload();}
        });
    };
@endif



function fUpdFilePass(){
    var c=prompt('Insira a senha do arquivo','');
    if(!c)return;
    awAjax({
        url: '{{route($prefix.".app.post",["process_cad_apolice","update_file_pass"])}}',data:{process_id:'{{$model->id}}',pass:c},processData:true,
        success: function(){alert('Dados salvos com sucesso');window.location.reload();}
    });
};


@if(in_array(Auth::user()->user_level,['dev','superadmin']))
    function fUpdStatusCode(){
        var c=prompt('Digite o novo código de erro (6 caracteres)','');
        if(!c)return;
        awAjax({
            url: '{{route($prefix.".app.post",["process_cad_apolice","update_status_code"])}}',data:{process_id:'{{$model->id}}',code:c},processData:true,
            success: function(){alert('Dados salvos com sucesso');window.location.reload();}
        });
    };



    //abre a janela para envio do registro para status de Operador Manual
    function editSendOperador(){
        var oModal = awModal({
            title:'Reportar erro na apólice para o Operador Manual',
            form:'method="POST" action="{{route($prefix.".app.get",["process_cad_apolice","sendToOperator"])}}"',
            msg_type:'danger',
            html:function(oHtml){
                 var r=''+
                    '<label class="nostrong"><input type="checkbox" name="req_fill_manual" value="s" {{ ($model_data['req_fill_manual']??false)=='s'?'checked':'' }}><span class="checkmark"></span> Bloquear para emissão da apólice <br><small style="margin-left:24px;">somente após alteração manual dos dados pelo operador</small></label>'+
                    '<br><br>'+
                    '<div class="form-group" id="div-fields-process">'+
                        '<label class="control-label nostrong">Alterar código do erro </label>'+
                        '{!! Form::select("code",[],null,["class"=>"form-control select2","data-type"=>"select2",
                            "data-ajax-url"=> route($prefix.'.app.get',['process_cad_apolice','listStatusCode','?mode=select2&first_blank=s']),
                            "data-ajax-once"=> 'true',
                            "data-allow-clear"=> 'true',
                        ]) !!}'+
                    '</div>'+
                    '<div class="form-group">'+
                        '<label class="control-label nostrong">Adicionar observação para o operador</label>'+
                        '<div class="control-div">'+
                            '<textarea class="form-control" name="obs_operator" placeholder="" rows="5" style="resize: none;"></textarea>'+
                        '</div>'+
                    '</div>'+
                    '';
                oHtml.html(r);
            },
            btSave:'Enviar',
            form_opt:{
                dataFields:{'id':{{$model->id}}},
                onSuccess:function(opt){ window.location.reload(); }
            }
        });
    };
@endif


//**** admin msg ****
@if(in_array($user_logged_level,['dev','superadmin']))
    $('#btn_obs_admin,#btn_obs_operator').on('click',function(e){
        var area = $(this).attr('id')=='btn_obs_admin' ? 'admin' : 'operator';
        e.preventDefault();
        awModal({
            title:'Editar Observação',
            html:function(oHtml){
                oHtml.html(
                    '<input type="hidden" name="area" value="'+ area +'" />'+
                    '<input type="hidden" name="process_id" value="{{$model->id}}" />'+
                    '<div class="row">'+
                        '<div class="form-group col-sm-12">'+
                            '<div class="control-div">'+
                                '<textarea class="form-control" name="obs" placeholder="" rows="10" style="resize: none;">'+ $.trim($('#obs_'+area+'_textarea').html()) +'</textarea>'+
                            '</div>'+
                        '</div>'+
                    '</div>'+
                    ''
                );
            },
            btClose:false,
            btSave:'Salvar',
            form:'method="POST" action="{{route($prefix.'.app.post',['process_cad_apolice','obs_edit'])}}" accept-charset="UTF-8" ',
            form_opt:{
                onSuccess:function(r){
                    if(r.success)window.location.reload();
                },
                //fields_log:false
            }
        });
        return false;
    });
@endif

function selectBroker(){
    awModal({
        title:'Alterar Corretor',
        html:function(oHtml){
            oHtml.html(
                '<input type="hidden" name="process_id" value="{{$model->id}}" />'+
                '<input type="hidden" name="account_id" value="{{$model->account_id}}" />'+
                '<div class="form-group">'+
                    '<div class="control-div">'+
                        '{!! Form::select("broker_id",[],null,["class"=>"form-control select2","data-type"=>"select2",
                            "data-ajax-url"=> route($prefix.'.app.get',['brokers','list_ajax','?mode=select2&account_id='.$model->account_id]),
                            "data-ajax-once"=> 'true',
                            "data-allow-clear"=> 'true',
                        ]) !!}'+
                    '</div>'+
                '</div>'+
                ''
            );
        },
        btClose:false,
        btSave:'Salvar',
        form:'method="POST" action="{{route($prefix.'.app.post',['process_cad_apolice','update_broker'])}}" accept-charset="UTF-8" ',
        form_opt:{
            onBefore:function(opt){
                if($.trim(opt.oForm.find('[name=broker_id]').val())=='')return false;//interrompe o submit se vazio
            },
            onSuccess:function(r){
                if(r.success){
                    window.location.reload();
                    /*//!descartado
                    fReprocess(false,false,function(r2){
                        //console.log('xxx',r2);
                        //atualiza a tela independente do retorno
                        window.location.reload();
                    });*/
                }
            },
            fields_log:false
        }
    });
};
</script>
@endsection


@push('head')

<style>
.row-max1{max-width:1341px;}
.table-seg-view{background:#fff;}
.table-seg-view-wrap{overflow:hidden;}
.table-seg-view .tbody-data td{position:relative;vertical-align:middle;}
.sticky-wrapper{z-index:99999 !important;}
.popover{width:250px;}

.table-wauto{width:auto;}
.col-label{min-width:{{$cell_width}}px;max-width:{{$cell_width}}px;}
.col-apolice_check,.col-pdf,.col-quiver,.col-manual{min-width:{{$cell_width}}px;max-width:{{$cell_width}}px;}
[data-col^=exec_]{min-width:{{$cell_exec_width}}px;max-width:{{$cell_exec_width}}px;}
.col-execfinal{min-width:{{$cell_execfinal_width}}px;max-width:{{$cell_execfinal_width}}px;}

.tr-group-name td{font-weight:600;background:#f2f2f2;border-bottom:1px solid #e2e2e2 !important;}
.tr-group-name:hover td{background:#ededed;}

.table-seg-form .form-group{margin-bottom:0;margin:-5px;}
.table-seg-form td{vertical-align:middle !important;}

tr:not(.tr-group-name) .col-manual .bt-edit{display:none;float:right;font-size:14px;margin:-2px 0 -3px 0;position:relative;z-index:9;right:0;border-color:#ccc;}
.tr-changed-manual .col-manual{color:#3366ff;}
.tr-changed-manual .col-manual .bt-edit{display:block;}
.col-manual.code-err{color:#dd4b39;box-shadow:inset 0 0 1px 1px #dd4b39;}

/*colunas execs*/
.col-quivexec.code-err,.group-col-quivexec.code-err{border-right-color:#dd4b39;}
.col-quivexec.code-err:before,.group-col-quivexec.code-err:before{position:absolute;height:50px;left:0;top:0;width:1px;border-left:1px solid #dd4b39;content:'';display:block;}
.group-col-quivexec.code-err{box-shadow:inset 1px 1px 1px 0px #dd4b39;}
.tr-last .col-quivexec.code-err{border-bottom-color:#dd4b39;}
tr.is-collapsed .group-col-quivexec.code-err{border-bottom-color:#dd4b39 !important;}
.exec-col-hide{max-width:28px !important;min-width:28px !important;width:28px !important;}
.exec-col-hide > div,.exec-col-hide  > span{display:none;}
.exec-col-hide > a{left:-4px;}


.tbody-data{border:0 !important;}
.tbody-data tr:hover{background:#f8f8f8;}
{{$is_lock_form_edit?'.tbody-data .col-manual:hover .bt-edit{display:block;} .tbody-data .col-manual:hover .text-truncate:before{display:none;}':''}}
.tbody-data tr:not(.tr-group-name) .col-manual .bt-edit:hover{color:#3366ff;border-color:#3366ff;}

.j-disable-edit .col-manual .bt-edit,.j-disable-edit .col-manual:hover .bt-edit{display:none;}
.j-disable-edit .col-manual:hover{cursor:default;border:0;}

.hidden-collapse{display:none;}
.hidden-filter1{display:none;}
.hidden-tablerows{display:none;}

/*form*/
.form-group{position:relative;}
.form-icon-info1{cursor:pointer;position:absolute;display:none;right:-5px;margin-top:9px;z-index:9;}
.form-group:hover .form-icon-info1{display:block;}

/*td styles status code*/
.td-changed.code-ok .text-truncate:before,.td-changed.code-err .text-truncate:before{font-family:'FontAwesome';font-size:0.8em;position:absolute;z-index:1;left:0;}
.td-changed.code-ok .text-truncate:before{content:'\f00c';color:#00a65a;}
.td-changed .text-truncate:before{margin:2px 0 0 {{$cell_exec_width-27}}px;}
.col-execfinal{text-align:center;}
.td-changed.col-execfinal .text-truncate:before{margin:-10px 0 0 calc(50% - 8px);font-size:14px;}
.td-changed.code-err .text-truncate:before{content:'\f071';color:#dd4b39;}
.col-execfinal.code-err.diff-value .text-truncate:before{font-family:'FontAwesome';font-size:14px;position:absolute;z-index:1;left:calc(50% - 8px);top:8px;content:'\f071';color:#dd4b39;;}

td[data-status-code]{color:#dd4b39;}
td[data-status-code=ok],td[data-status-code=ok2]{color:inherit;}

/*exibir colunas - defaults*/
td[data-col=apolice_check],td[data-col=pdf]{display:none !important;}
td[data-col=execfinal]{text-align:center;}
td[data-col^=exec_]{display:none !important;}
@if($execs_count==0)
thead .col-execfinal a,.dropdown-menu-item[data-id=menu-execs]{display:none;}
@endif

/*exibir colunas - filtros*/
.cols-view-datapdf td[data-col=pdf],
.cols-view-apolice_check td[data-col=apolice_check],
.cols-view-execs td[data-col^=exec_]{display:table-cell !important;}

/*valor das colunas execs*/
td .col-from{display:block;font-size:11px;line-height:11px;color:#999;}
td .col-span-x{display:none;font-size:1.1em;}
td .col-to{display:block;}
/*td .col-from:before,td .col-to:before{content:'de: ';}
td .col-to:before{content:'para: ';}*/

/*coluna do quiver*/
.tbody-data .col-quiver{color:#ccc;}
.tr-changed-quiver .col-quiver{color:inherit;}

/*historico execs*/
.tr-status-ok{color:#008d4c;}
.tr-status-err{color:#dd4b39;}
.itemx1{display:inline-block;width:150px;padding-right:5px;}
.itemx1-colspan{width:auto;}

@if($user_logged_level!='dev')
/*oculta por os campos com permissão apenas para o admin*/
.tr-igshow-hide_admin{display:none !important;}
@endif

</style>
@endpush
