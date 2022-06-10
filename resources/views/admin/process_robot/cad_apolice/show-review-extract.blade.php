@php
/*********************************
    
    Tela de revisão manual 
    Criada com o objetivo de simplificar a revisão dos dados extraídos junto da apólice de modo manual.
    Data: 29/06/2021
    
*********************************/
@endphp

@extends('templates.admin.index',[
    'dashboard'=>[
        'menu_collapse'=>true,
        'padding'=>false,
    ]
])

@php
use App\Services\PrSegService;
use App\Utilities\FormatUtility;

@endphp


@section('content-view')
@php
/*  Parâmetros esperados:
        $id
        $pr_process
        $prCadApoliceService
        $model
        
*/
    $ids = Request::input('ids');
    if($ids){
        $ids=explode(',',$ids);
        //filtra os valores
        foreach($ids as $i=>$idx){
            if(!is_numeric($idx) || $idx==$model->id)unset($ids[$i]);
        }
        if($ids){
            $tmp = $ids;
            $first_key = array_keys($tmp)[0];
            $idnext = $tmp[$first_key];
            ///*unset($tmp[$first_key]);
            $ids = join(',',$tmp);
        }
    }
    
    
    
    $fields_allow = [
        //dados
        //'apolice_prod_ref',
        //'corretor_susep',
        //'corretor_nome',
        //'data_type',
        //'seguradora_doc',
        'proposta_num',
        //'apolice_num',
        'apolice_num_quiver',
        'data_emissao',
        //'apolice_re_num',
        'inicio_vigencia',
        'termino_vigencia',
        //'segurado_nome',
        'segurado_doc',
        //'tipo_pessoa',
        'fpgto_premio_total',
        'fpgto_tipo',
        'fpgto_tipo_code',
        //'fpgto_n_prestacoes',
        'fpgto_premio_liquido',
        'fpgto_premio_liq_serv',
        'fpgto_custo',
        'fpgto_adicional',
        'fpgto_iof',
        'fpgto_juros',
        'fpgto_juros_md',
        'fpgto_desc',
        //'fpgto_1_prestacao_valor',
        //'fpgto_1_prestacao_venc',
        //'fpgto_dem_prestacao_valor',
        'fpgto_venc_dia_1parcela',
        'fpgto_venc_dia_2parcela',
        //'fpgto_avista',

        //parcelas
        'fpgto_datavenc',
        'fpgto_valorparc',

        //automovel
        'prop_nome',
        'segurado_pernoite_cep',
        'prop_nome',
        'veiculo_zero',
        //'veiculo_data_saida',
        //'veiculo_nf',
        'veiculo_fab',
        'veiculo_fab_code',
        'veiculo_combustivel',
        'veiculo_combustivel_code',
        'veiculo_placa',
        'veiculo_ano_fab',
        'veiculo_ano_modelo',
        //'veiculo_tipo',
        'veiculo_ci',
        'veiculo_chassi',
        'veiculo_modelo',
        'veiculo_cod_fipe',
        'veiculo_classe',
        //'veiculo_n_lotacao',
        //'veiculo_n_portas',
        
        //residencial
        'residencial_endereco',
        'residencial_numero',
        'residencial_compl',
        'residencial_bairro',
        'residencial_cidade',
        'residencial_uf',
        'residencial_cep',
    ];
    
    
    
    
    $PrSegService = new PrSegService;
    $prefix = Config::adminPrefix();
    
    $segDadosClass = $PrSegService::getSegClass('dados');
    $segDados_label = $segDadosClass::fields_labels();
    $segParcelasClass = $PrSegService::getSegClass('parcelas');
    $segParcelas_label = $segParcelasClass::fields_labels();
    $segProdClass = $PrSegService::getSegClass($model->process_prod);
    $segProd_label = $segProdClass::fields_labels();
    $prod_name = $model->process_prod;
    
    $data_extract = $PrSegService->getDataPdf($model);
    //dd($data_extract);
    
    
    //monta a lista de campos a exibir
    $labels=[
        'dados'=>['title'=>'Dados','labels'=>$segDados_label],
        $prod_name=>['title'=>$configProcessProd['title'],'labels'=>$segProd_label],
        'parcelas'=>['title'=>'Parcelas','labels'=>$segParcelas_label],
    ];
    
    
    //campos adicionais
    $extra_fields=[
        'corretor'=>['Corretor',$model->broker->broker_alias],
        //'seguradora'=>['Seguradora',$model->insurer->insurer_alias],
    ];
    
    
    //monta a tabela de dados
    $x=0;
    $r='<table class="tdata" id="tdata">';
    if($extra_fields){
        $r.='<tr><td colspan="3">&nbsp;</td></tr>';
        $r.='<tr class="tdata-head"><td colspan="3"><strong>Gerais</strong></td></tr>';
        foreach($extra_fields as $f=>$n){
            $lb=$n[0];
            $v=$n[1];
            $r.='<tr class="tdata-row field-'.$f.'" data-field="'.$f.'">'.
                    '<td class="tdata-check"><input type="checkbox"><span class="checkmark small2"></span></td>'.
                    '<td class="tdata-label">'. $lb .'</td>'.
                    '<td class="tdata-value"><span class="tdata-text-val">'. ($v==''?'-':$v) .'</span></td>'.
                '</tr>';
            $x++;
        }
    }
    foreach($labels as $gr => $item){
        if($x>0)$r.='<tr><td colspan="3">&nbsp;</td></tr>';
        $r.='<tr class="tdata-head"><td colspan="3"><strong>'. $item['title'] .'</strong></td></tr>';
        foreach($item['labels'] as $f=>$label){
            if($fields_allow && !in_array($f,$fields_allow))continue;
            
            $count=0;
            foreach($data_extract as $f2 => $v){
                //lógica: retira o último caractere {n} caso seja númerico na sintaxe 'field_a_b_{n}'
                $n=trim($f2);
                $n=explode('_',$n);
                $i=$n[count($n)-1];
                if(is_numeric($i))unset($n[count($n)-1]);
                $f2 = trim(join('_',$n));
                
                if($f==$f2){
                    $count++;
                    $lb = $gr==$prod_name || $gr=='parcelas' ? $label.' '.$count : $label;
                    $r.='<tr class="tdata-row field-'.$f.'" data-field="'.$f.'">'.
                        '<td class="tdata-check"><input type="checkbox"><span class="checkmark small2"></span></td>'.
                        '<td class="tdata-label">'. $lb .'</td>'.
                        '<td class="tdata-value"><span class="tdata-text-val">'. ($v==''?'-':$v) .'</span></td>'.
                    '</tr>';
                }
            }
        }
        $x++;
    }
    $r.='</table>';
    
    

    $link_pdf = route('super-admin.app.get',['process_cad_apolice','file_load',$model->id]);
    $link_show = route('super-admin.app.show',['process_cad_apolice',$model->id]);
    
    echo '<table class="table-sty1" id="table-sty1">
            <tr class="tr-head">
                <td colspan="2">
                    <div class="td-margin-left">
                        <div class="pull-left">
                            <h4 class="inlineblock">Revisão manual da extração de dados <small style="margin-left:15px;">#'. $model->id .'</small></h4>
                            
                            <table class="inlineblock" style="margin-left:50px;position:relative;top:5px;"><tr>
                                ';
                                //taxs
                                $terms = $configProcessNames['cad_apolice']['terms']??null;
                                $termsList = \App\Models\Term::whereIn('id',$terms)->get();
                                $terms_ids=[];
                                $taxs_list=[];
                                if($termsList){
                                    echo '<td>';
                                    foreach($termsList as $term){
                                        $terms_ids[]=$term->id;
                                        $taxs_list[$term->id]=$model->getTaxRelation($term->id,'cad_apolice');
                                        //dd($taxs_list[$term->id]);
                                        
                                        echo '<a href="#" class="btn btn-info margin-r-10" id="bt_box_terms_'.$term->id.'" title="Adicionar '. $term->term_title .'"><i class="fa fa-tags"></i></a>';
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
                                    echo '</td>';
                                }
                                
                                if(isset($termsList)){
                                    echo '<td style="padding-left:20px;padding-right:20px;">';
                                    foreach($termsList as $term){
                                        $taxs=$taxs_list[$term->id];
                                        //$r.='<span style="font-size:small;display:block;margin-bottom:6px;">'. ($taxs->count()>0 ? $term->term_title : '') .' <br></span> ';
                                        //dd($taxs->count(),$taxs);
                                        echo view('templates.ui.tag_item_list',[
                                                'taxRel'=>$taxs,
                                                'term_id'=>$term->id,
                                                'area_name'=>'cad_apolice'
                                            ])->render();
                                    }
                                    echo '</td>';
                                }
                                
                                if($ids)echo '<td><a href="#" class="btn btn-info margin-r-10" onclick="fNextReg();return false;"><i class="fa fa-forward"></i> Próximo</a></td>';
                                
                                
                            echo'
                            </tr></table>
                        </div>
                        <div class="pull-right margin-r-10" style="margin-top:4px;">
                            <a href="#" class="btn btn-link strong margin-r-10" onclick="fInsertIds();return false;"><i class="fa fa-edit"></i> IDs</a>
                            <a href="'.$link_show.'" target="_blank" class="btn btn-link margin-r-10"><i class="fa fa-dot-circle-o"></i> Log da Baixa</a>
                            <a href="'.$link_pdf.'" target="_blank" class="btn btn-link"><i class="fa fa-file-pdf-o"></i> Nova janela</a>
                            <a href="#" class="btn btn-link" onclick="showKeyboards();return false;"><i class="fa  fa-keyboard-o"></i></a>
                        </div>
                    </div>
                </td>
            </tr>
            <tr class="tr-body">
                <td valign="top" class="td-margin-left td-fields">
                    <div id="scroll-data" class="scroll-data scrollmin"><div class="td-margin-left">'. $r .'<br><br></div></div>
                </td><td class="td-frame " valign="top" id="table-sty1-td2">
                    <iframe src="'. $link_pdf .'" id="iframe-view" class="iframe-view" style="background:#e2e2e2;"></iframe>
                </td>
            </tr>
    </table>';
    

    
@endphp


<style>
.content:before,.content:after{display:none;}
.table-sty1{width:100%;height:calc(100vh - 50px);margin-top:-15px;}
.tr-head{height:50px;}
.td-margin-left{margin-left:15px;}
.iframe-view{width:100%;border:0;height:100%;}
.td-fields{width:400px;}
.td-frame{}
.scroll-data{background:#fff;height:100%;overflow:auto;position:relative;}

/*tabela de dados*/
.tdata{user-select:none;}
.tdata::-webkit-selection{color:red;}

.tdata-row:hover{background:#f2f2f2;}
.tdata-row-checked td{text-decoration:line-through;opacity:0.7;}
.tdata-check{width:30px;}
.tdata-check{width:30px;position:relative;overflow:hidden;}
.tdata-check .checkmark{margin-top:0px !important;}
.tdata-label{font-size:12px;}
.tdata-value{user-select:all;padding-left:5px;}
.tdata-value:hover{text-decoration:underline;}
.tdata-text-val{display:inline-block;padding:0px 5px;border-radius:3px;}
.tr-selected .tdata-text-val::selection, .tr-selected .tdata-text-val{background:#0073ea;color:#fff;}



</style>
<script>
//desativa o evento do menu lateral
$('#main-sidebar').addClass('no-events');

//eventos na tabela
$('.tdata-row').on('dblclick check',function(){
    $(this).find('[type=checkbox]').click();
});

var iCurrTd=0;
var allTds=$('.tdata-value').each(function(i){ $(this).data('i',i) });
var allTrs=allTds.closest('tr');

allTds
    .on('click',function(){
        selectText(this);
        allTrs.removeClass('tr-selected');
        $(this).closest('tr').addClass('tr-selected');
        iCurrTd=$(this).data('i');
    })
    .on('set-focus',function(){
        allTrs.removeClass('tr-selected');
        iCurrTd=$(this).data('i');
        var o=allTds.eq(iCurrTd);
        _fFocus(o);
    })
    .on('next-focus',function(){
        allTrs.removeClass('tr-selected');
        iCurrTd=$(this).data('i')+1;
        var o=allTds.eq(iCurrTd);
        if(iCurrTd>=allTds.length){
            iCurrTd=allTds.length-1;
            o=allTds.eq(iCurrTd);
            //console.log('next',o[0],o.parent().find('input:eq(0)'))
            o.parent().find('input:eq(0)').focus();
        };
        _fFocus(o);
    })
    .on('prev-focus',function(){
        allTrs.removeClass('tr-selected');
        iCurrTd=$(this).data('i')-1;
        var o=allTds.eq(iCurrTd);
        if(iCurrTd<0){
            iCurrTd=0;
            o=allTds.eq(0);
        }
        _fFocus(o);
    });
    
    var oScrollData=$('#scroll-data');
    var _fFocus=function(o,pos){
        o.click();
        o.closest('tr').addClass('tr-selected');
        selectText(o[0]);
        oScrollData.scrollTop(0);//set to top
        oScrollData.scrollTop(o.position().top - oScrollData.height() + 100);
    };
    

$('#tdata [type=checkbox]').on('click',function(e){
    e.stopPropagation();
    var t=$(this).prop('checked');
    var o=$(this).closest('tr');
    if(t){
        o.addClass('tdata-row-checked');
    }else{
        o.removeClass('tdata-row-checked');
    }
});

var oIfr=$('#iframe-view');
oIfr.on('load', function() {
    var ifrDoc = oIfr.contents().find('body');
    ifrDoc.on('keydown',function(e){
        var k=e.keyCode;
        if(k==27){//esc
            window.focus();
            allTds.eq(iCurrTd).trigger('set-focus');
        }
    });
});

$(document.body).on('keydown',function(e){//adiciona o evento nesta página e no frame
    var k=e.keyCode;
    //console.log(k)
    if(k==39 && (e.ctrlKey || e.altKey)){//ctrl|alet + arrow right
        e.preventDefault();
        fNextReg();
        
    }else if($.inArray(k,[13,9,37,38,39,40])!==-1){//enter, tab, left, up, right, down
        e.preventDefault();
        var n=k==37 || k==38 || e.shiftKey ? 'prev' : 'next';
        allTds.eq(iCurrTd).trigger(n+'-focus');
        
    }else if(k==32){//space
        e.preventDefault();
        allTds.eq(iCurrTd).closest('tr').trigger('check');
        
    }else if(k==27){//esc
        console.log(777)
        allTds.eq(iCurrTd).trigger('set-focus');//foca no registro atual
        
    }else if((e.ctrlKey && k==70) || k==114){//ctrl + F, F3
        //allTds.eq(iCurrTd).trigger('set-focus');//foca no registro atual
        setTimeout(function(){
            oIfr.click().contents().find('embed').focus();
        },100);
        
    }else if(e.altKey){//alt        // || k==117 || (e.ctrlKey && k==76) , (117)F6, ctrl + (76)L
        e.preventDefault();
    }
});

//ao carregar a página
$(document).ready(function(){
    $(window).on('focus',function(){
        allTds.eq(iCurrTd).trigger('set-focus');
    });
    setTimeout(function(){
        $(document.body).focus();
        allTds.eq(0).trigger('set-focus');
    },100);    
});



function selectText(element){
	//Seleciona o texto em uma div
    var doc = document;
    if (doc.body.createTextRange) {
        var range = document.body.createTextRange();
        range.moveToElementText(element);
        range.select();
    } else if (window.getSelection) {
        var selection = window.getSelection();        
        var range = document.createRange();
        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
    }
};


@if($terms_ids)
    //terms
    var terms_id=[{{join(',',$terms_ids)}}],term_id,o,p;
    for(var i in terms_id){
        term_id = terms_id[i];
        o=$('#bt_box_terms_'+term_id);
        p=[o.offset().left,o.offset().top+o.outerHeight()];
        let oBoxItems=$('#autofield_box_terms_'+term_id);
        awTaxonomyToObj({
            tax_form:oBoxItems,
            area_name:'cad_apolice',
            area_id:{{$model->id}},
            tags_item_obj: {term_id:term_id},
            button:o,
            button_pos:p,
        });
        
        oBoxItems.on('cb_clickItem',function(e,opt2){
            if(opt2.sel){
                setTimeout(fNextReg,500);
            }
        })
    };
@endif


//captura os ids
function fInsertIds(){
    var v=prompt('Insira os ids separados por virgula');
    if($.trim(v)=='')return;
    var first_id = v.split(',')[0];
    var url = addQS(admin_vars.url_current.replace('/{{$model->id}}/','/'+ first_id +'/')  +'?'+admin_vars.querystring,'ids='+v,'string');//obs: sempre irá setar page='' para iniciar da primeira página
    window.location = url;
}


function fNextReg(){
@if($ids)
    //avança para o próximo registro
    if(confirm('Avançar para o próximo registro?')){
        var url = addQS(admin_vars.url_current.replace('/{{$model->id}}/','/{{$idnext}}/')  +'?'+admin_vars.querystring,'ids={{$ids}}','string');//obs: sempre irá setar page='' para iniciar da primeira página
        window.location = url;
        return false;
    };
@endif
    setTimeout(function(){ allTds.eq(iCurrTd).trigger('set-focus'); },100); 
}


function showKeyboards(){
    var list={
        'CTRL + P'      : 'Pesquisar',
        'ESC'           : 'Fecha a janela de pesquisa',
        '↑ ou ←'        : 'Sobe um item',
        '↓ ou →'        : 'Desce um item',
        'SHIFT + TAB'   : 'Sobe um item',
        'TAB'           : 'Desce um item',
        'ESPAÇO'        : 'Marca um item',
        'CTRL + C'      : 'Copiar',
        'CTRL + V'      : 'Colar',
        'ALT + →'       : 'Avança para o próximo registro',
    };
    var r='';
    for(var n in list){
        r+='<tr><td class="margin-r-10" nowrap><code>'+ n +'</code></td><td width="90%" style="padding-left:20px;">'+ list[n] +'</td></tr>';
    }
    var oModal=awModal({title:'Teclas de Atalho',html:'<table class="table table-condensed table-borderless">'+r+'</table>'})
    oModal.on('hide.bs.modal', function(){
        allTds.eq(iCurrTd).trigger('set-focus'); 
    });
}

</script>
@endsection
