/*
Funções do arquivo:
    awTaxonomyInit()
    awTaxPostRelation()
    awTaxonomyToList()
    awTagItem()
    awTagItem_events()
Requer:
    
*/


/* Executa as funções de inicialização da interface de taxonomia.
 */
function awTaxonomyInit(oContainer){
    var baseDivTaxUI=oContainer ? oContainer.find('[ui-taxform=on]') : $('[ui-taxform=on]');
    if(baseDivTaxUI.length==0)return;
    
    baseDivTaxUI.each(function(){
        var base=$(this).attr('ui-taxform','ok');
        var opt=$.parseJSON(base.attr('data-opt'));
        
        var _fEventsItemsList=function(os){
            if(opt.show_check){//com checkbox
                os.on('click',function(e){
                    if($(e.target).attr('type')=='radio'){//dispara o click do campo radio (seleção única)
                        if(oLastItem)base.trigger('onClickItem',{sel:false,input:oLastItem.find('[type=radio]'),item:oLastItem});
                        base.trigger('onClickItem',{sel:true,input:this_input,item:$(this)});
                        if(opt.class_select){
                            if(oLastItem)oLastItem.removeClass('selected');
                            $(this).addClass('selected');
                        };
                        oLastItem = $(this);
                    };
                    if($(e.target).attr('type')!='checkbox')return;//dispara o click somente no campo checkbox
                    
                    if(oLastItem)oLastItem.removeClass('clicked');
                    var this_input=$(this).find('input');
                    var sel=false;
                    if(this_input.prop('checked')){
                        oLastItem = $(this).addClass('clicked');
                        if(opt.class_select)oLastItem.addClass('selected');
                        oSelNew.val(oLastItem ? oLastItem.parent().attr('data-id'): '');
                        sel=true;
                    }else{
                        if(opt.class_select)$(this).removeClass('selected');
                        oSelNew.val('');
                    };
                    
                    //atualiza a lista dos campos que estavam marcados ao carregar, mas foram desmarcados depois
                    var ids_uncheck=oItems.filter(function(){ var o=$(this).find('input'); return o.attr('data-load-select')=='on' && o.prop('checked')===false; }).map(function(){ return $(this).attr('data-tax_id') }).get();
                    base.find('[data-name=autofield_taxs_term_uncheck]:eq(0)').val(ids_uncheck);
                    
                    var b=$(this);
                    setTimeout(function(){//precisa do timeout para capturar corretamente todos os checkeds marcados dinamicamente
                        base.trigger('onClickItem',{sel:sel,input:this_input,item:b});
                    },0);
                });
            }else{//sem checkbox
                os.on('click',function(e){
                    if(opt.class_select){
                        if(oLastItem)oLastItem.removeClass('selected');
                        $(this).addClass('selected');
                    };
                    if(oLastItem)oLastItem.removeClass('clicked');
                    oLastItem = $(this).addClass('clicked');
                    oSelNew.val(oLastItem ? oLastItem.parent().attr('data-id'): '');
                    base.trigger('onClickItem',{sel:null,input:null,item:$(this)});
                });
            };
           
            
            
            //select level
            if(opt.is_multiple && opt.show_check){
                var _fUnCheckLevel=function(othis,oInput){
                    var n=othis.find('>.ui-taxform-sub >.ui-taxform-gr >.ui-taxform-item input:checked').length;
                    if(n==0){
                        var tmp=othis.find('>.ui-taxform-item input').prop('checked',false);
                        if(opt.class_select)tmp.parent().removeClass('selected');
                    }
                };
                os.on('click',function(e){
                    if($(e.target).attr('type')!='checkbox')return;//dispara o click somente no campo checkbox
                    var o=$(this);
                    var check=!o.find('input').prop('checked');
                    
                    //ascendente
                    o.parents('.ui-taxform-gr').each(function(index){
                        if($(this).find('>.ui-taxform-item')[0]!=o[0]){//pula o item atual
                            var a=$(this);
                            var oInput=a.find('>.ui-taxform-item input');
                            if(check){//desmarca
                                setTimeout(function(){ _fUnCheckLevel(a,oInput); }, 1*index);
                            }else{//marca
                                oInput.prop('checked',true);
                                if(opt.class_select)oInput.parent().addClass('selected');
                            }
                        };
                    });

                    //descendente
                    var sub=o.parent().find('>.ui-taxform-sub');
                    if(sub.length>0){
                        var tmp=sub.find('input').prop('checked', !check);
                        if(opt.class_select)if(!check){tmp.parent().addClass('selected');}else{tmp.parent().removeClass('selected');}
                    }
                });

            }else if(!opt.show_check && opt.is_collapse){//não exibe o campo radio/checkbox e tem collapso
                os.on('click',function(){$(this).prev().click();});//collapse onclick text
            };
        };
        
        
        
        var oLastItem = null;
        var oSelNew=base.find('.ui-taxform-new');
        var oItems=base.find('.ui-taxform-item').filter(function(){ return $(this).parent().attr('data-id')!='new'; });
        //aplica os eventos
        _fEventsItemsList(oItems);
        
        
        
        
        //search
        if(opt.is_search){
            var oSearch=base.find('.ui-taxform-search input').on({
                'focus':function(){
                    oSearch.closest('.ui-taxform-search').addClass('focus');
                },
                'focusout':function(){
                    oSearch.closest('.ui-taxform-search').removeClass('focus');
                },
                'keyup':function(e){
                    e.stopPropagation();
                    var k=e.keyCode;
                    var v=$.trim(oSearch.val().toLowerCase());
                    if(k==27 || v==''){//esc
                        if(opt.is_collapse)oItems.nextAll('.ui-taxform-sub.collapse').removeClass('show');
                        oItems.show();
                        oItems.prev().show();//icon
                        oSearch.val('');
                        
                    }else{
                        if(opt.is_collapse)oItems.nextAll('.ui-taxform-sub.collapse').addClass('show');
                        oItems.hide();
                        oItems.prev().hide();//icon
                        oItems.each(function(){
                            if($.trim($(this).text().toLowerCase()).indexOf(v)>-1){
                               $(this).show();
                               $(this).prev().show();//icon
                            }
                        });
                    }
                }
            });
        };
        
        if(opt.is_add){
            //add
            var oItemGrDefNew =  base.find('[data-id=new]').remove();
            var oBoxAdd;
            base.find('.ui-taxform-addlnk').on('click',function(e){
                e.preventDefault();
                if(!oBoxAdd){
                    oBoxAdd=$(this).next();////.ui-taxform-addbox
                    var oBtAdd=oBoxAdd.find('button').on('click',function(){
                        //add taxonomy
                        var data={
                            tax_title:$.trim(oBoxAdd.find('input').val()),
                            tax_id_parent:oBoxAdd.find('select').val()
                        };
                        
                        data.tax_title=$.trim(oBoxAdd.find('input').val());
                        if(data.tax_title==''){oBoxAdd.find('input').focus();return false;}
                        oBtAdd.button('loading');
                        
                        if(base.triggerHandler('onBeforeAdd',$.extend(true,data,{term_id:opt.term_id}))==false){
                            oBtAdd.button('reset');
                            return false;
                        };
                        
                        var p={
                                url: opt.route_add,
                                processData:true,
                                data:data,
                                success: function(r){
                                    oBtAdd.button('reset');
                                    oBoxAdd.find('input').val('').focus();
                                    //console.log('ok',p,r);
                                    if(r.success){
                                            var tmp;
                                            //atualiza a var tax_title (pois o nome pode ter sido alterado)
                                            data.tax_title = r.data.tax_title;
                                            
                                            //duplica o objeto e atualiza no dom
                                            oNew = oItemGrDefNew.clone().removeClass('hiddenx');
                                                 if(data.tax_id_parent){
                                                     if( $('#ui-taxform-sub-'+ data.tax_id_parent).length==0 ){//não tem submenu
                                                         tmp = base.find('.ui-taxform-gr[data-id='+data.tax_id_parent+']').append('<div class="ui-taxform-sub collapse in" id="ui-taxform-sub-'+ data.tax_id_parent +'"></div>');
                                                         if(opt.is_collapse)tmp.find('>.ui-taxform-collapse-icon').attr({'data-target':'#ui-taxform-sub-'+ data.tax_id_parent,'data-toggle':'collapse'}).html('<span class="fa fa-caret-down"></span>');//adiciona a seta collapse
                                                     };
                                                     base.find('#ui-taxform-sub-'+ data.tax_id_parent).append(oNew);
                                                 }else{
                                                     oNew.appendTo(base.find('.form-group'));
                                                 };

                                            oNew.attr('data-id',r.data.id);
                                            oNew.attr('data-parent-id',data.tax_id_parent);
                                            oNew.find('.ui-taxform-itemtitle').html(data.tax_title);
                                            var oNewItem=oNew.find('.ui-taxform-item');
                                            oNewItem.attr('data-tax_id',r.data.id);
                                            oNewItem.attr('data-tax_title',data.tax_title);
                                            
                                            //aplica os eventos
                                            _fEventsItemsList(oNewItem);
                                            //atualiza as variáveis
                                            oItems=base.find('.ui-taxform-item');

                                            //adiciona no item select
                                            tmp=oBoxAdd.find(data.tax_id_parent ? 'select option[value='+data.tax_id_parent+']' : 'select option:eq(0)');
                                            if(data.tax_id_parent){
                                                var z=(tmp.html().match(/\—/g) || []).length;//conta quantos '-' existem
                                                data.tax_title = String('&#151; ').repeat(z+1) + data.tax_title;
                                            };
                                            tmp.after('<option value="'+r.data.id+'">'+data.tax_title+'</option>');
                                            
                                            if(opt.is_add_checked){//marca o item adicionado
                                                oNewItem.find('input').click();
                                            };
                                            
                                            //custom event
                                            base.triggerHandler('onAfterAdd',$.extend(true,data,{success:r.success,term_id:opt.term_id,item:oNewItem,input:oNewItem.find('input')}));
                                            
                                    }else{
                                            awModal({title:'Erro',html:r.msg,msg_type:'danger'});
                                            base.triggerHandler('onAfterAdd',$.extend(true,data,{success:r.success,term_id:opt.term_id}));
                                    };
                                },
                                error:function (xhr, ajaxOptions, thrownError){
                                    oBtAdd.button('reset');
                                    awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger'});
                                    base.triggerHandler('onAfterAdd',$.extend(true,data,{success:false,term_id:opt.term_id}));
                                }
                        };
                        awAjax(p);
                    });
                    oBoxAdd.find('input').on('keydown',function(e){if(e.keyCode==13)oBtAdd.click();});
                };
                if(oBoxAdd.css('display')=='none'){oBoxAdd.show().find('input').focus();}else{oBoxAdd.hide();};
            });
        };
        
        
        if(opt.is_popup){//resize da janela
            var _fResize=function(){
                if(!base.is(':visible'))return;
                var o=base.find('.form-group').css({maxHeight:'none'});
                var h1=base.offset().top + base.height();
                var h2=$(window).height();
                if(h1 >= h2){
                    var margin=120;//margin default
                    var n=h2-base.offset().top;
                    var d=base.height() - o.height();//difedença entre o .form-group com a base
                    n=n-d-margin;
                    o.css({maxHeight:n});
                };
                //console.log(h1,h2)
            };
            $(window).on({
                'resize load':_fResize,
                'mousedown':function(){base.hide();}
            });
            
            base.on({
               'mousedown':function(e){
                    e.stopPropagation();  
               },
               'close':function(e){
                   e.stopPropagation();
                   base.hide();
               },
               'show':function(e,opt2){
                   //json opt2 - position: (object) ref| (array) integer x,y
                   e.stopPropagation();
                   setTimeout(function(){
                       var p=(opt2 ? opt2.position : null);
                       var t=$(window).scrollTop();
                       if($.type(p)=='object'){
                           var n=p.offset();
                           base.css({left:n.left,top:n.top+p.outerHeight()-t});
                       }else if($.type(p)=='array'){
                           base.css({left:p[0],top:p[1]-t});
                       };
                       base.show();
                       _fResize();
                       if(opt.is_search)base.find('.ui-taxform-search input:eq(0)').focus();
                   },1);
               } 
            });
        };
        
        
        //eventos gerais
        base.on({
            'click':function(e){
                if(e.isTrigger){e.stopPropagation();return false;}
            },
            'get_select':function(e,ret){//retorna aos selecionados (triggerHandler)
                e.stopPropagation();  
                var o=oItems.filter(function(){ return $(this).find('input').prop('checked')===true; });
                if(ret=='obj'){
                    return o;
                }else{
                    return o.map(function(){ return $(this).attr('data-tax_id') }).get();
                }
            },
            'select':function(e,opt2){//json opt: id, select
                e.stopPropagation();
                e.preventDefault();
                if(!opt2)opt2={};
                var o=(opt2.id ? oItems.filter('[data-tax_id='+opt2.id+']') : oItems);
                if(o.length && typeof opt2.select !== 'undefined'){//caco contrário nenhuma ação
                    o.find('input').prop('checked',opt2.select);//obs: esta linha é obrigatória para funcionar o trigger click abaixo
                    o.trigger('click');
                    if(opt.class_select)if(opt2.select){o.addClass('selected');}else{o.removeClass('selected');}
                }
            }
        });
    });
}


/*
 * Registra uma relação de taxonomia
 * @param json opt action: 
 *      action - valores: add|del|edit (remove todos e adiciona o atual)
 *      tax_id,area_name,area_id (area_id pode ser: string|int|array - ex: '1,2,3' | 1 | [1,2,3]
 *      function cb - parâmetros json informados: status, msg...
 *      boolean alert_error - se false desativa o alerta padrão em caso de erro. Default true
 */
function awTaxPostRelation(opt){
    var data=$.extend(true,{},opt);
    //console.log('#',{action:opt.action+'_relation',tax_id:opt.tax_id,area_name:opt.area_name,area_id:opt.area_id})
    awAjax({
        url: admin_vars.route_tax_post,
        data:{action:opt.action+'_relation',tax_id:opt.tax_id,area_name:opt.area_name,area_id:opt.area_id},
        processData: true,
        success: function(r){
            if(opt.cb)opt.cb.call(null,r);
        },
        error:function (xhr, ajaxOptions, thrownError){
            if(opt.cb)opt.cb.call(null,{status:'E',msg:'Erro interno de servidor',data:xhr.responseText});
            if(opt.alert_error!==false)awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
        }
    });
}


/**
 * Função auxiliar de awListDataInit() e awTaxonomyInit() que aplica as funções do box da taxonomia junto a lista.
 * Return void
 * Obs: pode ser usado manualmente, mas também é instanciado automaticamente pela função awListDataInit() no caso de haver taxonomias padrões configuradas
 * Requer lists.js -> awListDataInit()
 */
function awTaxonomyToList(opts){
    var opt = $.extend(true,{
        oList:null,     //object ou id seletor da lista
        taxs_start:null,//ex? '1,2,3' | 1 | [1,2,3]
        /*  Configuração abaixo dos termos - json sintaxe: {term_id:{button:,tax_form} }
                term_id     - id do termo
                button      - object ou id seletor do botão que aciona a taxonomia (opcional)
                tax_form    - object ou id seletor do box da taxonomia (requerido)
                tax_form_type- tipo da lista da taxonomia. Valores (caso não definido permite ambas as opções): 
                            'list' - permite apenas filtrar as lista pela taxonomia
                            'set' - permite apenas aplicar as taxonomia na lista (neste caso o botão que exibe o box é exibido apenas quando houver registros)
                area_name   - (requerido)
        */
        terms:null,
    },opts);
    if(!opt.oList)return;
    if(!opt.terms)return;
    
    var oList=typeof(opt.oList)=='string' ? $(opt.oList) : opt.oList;
    
    var _fInitTerms=function(term_id,term_opt){
        var oTaxBox=(typeof(term_opt.tax_form)=='string' ? $(term_opt.tax_form) : term_opt.tax_form).on({
            'onClickItem':function(e,opt2){
                if(oTrsSelecteds.length>0){//add/remove
                    var item=opt2.item;
                    var ids_tr = oTrsSelecteds.map(function(){ return $(this).attr('data-id'); }).get().join(',');
                    awTaxPostRelation({
                        action:(opt2.sel?'add':'del'),
                        tax_id:item.attr('data-tax_id'),
                        area_name:term_opt.area_name,
                        area_id:ids_tr,
                        cb:function(r){
                            if(r.success){
                                oTrsSelecteds.each(function(){
                                        var tr=$(this);
                                        if(opt2.sel){//add
                                            awTagItem({
                                                title:item.attr('data-tax_title'),
                                                term_id:term_id,
                                                tax_id:item.attr('data-tax_id'),
                                                color:item.attr('data-tax_color'),
                                                icon:item.attr('data-tax_icon'),
                                            }).appendTo( tr.find('.row-taxonomy-items[data-term_id='+term_id+']') );
                                        }else{//del
                                            tr.find('.row-taxonomy-items[data-term_id='+term_id+']').find('.ui-tagitem[data-id='+item.attr('data-tax_id')+']').remove();
                                        };
                                });
                                oTaxBox.trigger('close');//fecha o box da taxonomia
                            }
                        }
                    });
                    
                    oTaxBox.trigger('close');
                    
                }else{//list
                    //filtra a lista (neste caso o clique será sempre para 1 item da lista)
                    var taxs_id=opt2.sel ? opt2.item.attr('data-tax_id') : '';
                    if(oList.data('options').route_load){
                        oList.trigger('load',{'taxs_id':taxs_id});
                        oTaxBox.trigger('close');
                        opt.taxs_start=taxs_id;//atualiza a var para ficar sempre selecionado ao exibir
                    }else{
                        var u=addQS(null,'taxs_id='+taxs_id,'string');
                        goToUrl(u);
                    }
                }
            }
        });
        
        var oBt;
        if(term_opt.button){
            oBt = (typeof(term_opt.button)=='string' ? $(term_opt.button) : term_opt.button).on('click',function(){
                var a=oTaxBox.find('.box-tools');
                var b=oTaxBox.find('.box-title');
                var tt=b.attr('data-title');
                var x=oTrsSelecteds.length;
                oTaxBox.trigger('select',{select:false});//deseleciona todos
                
                if(x>0){
                    b.html('<em>Adicionar</em> '+tt);
                    a.html('<em>'+x+' selecionado'+(x>0?'s':'')+'</em>');
                    oTaxBox.find('.checkmark,.box-footer').show();
               
                    //*** seleção dentro do tax_form *** 
                    //carrega as tags já adicionadas na lista //return array
                    var idsTagSel = oTrsSelecteds.find('.ui-tagitem').map(function(){ return $(this).attr('data-tax_id'); }).get();
                    idsTagSel=uniqueArr(idsTagSel);//elimite a duplicação dos ids
                    
                    if(idsTagSel.length>0){//existem tags
                        //marca os selecioandos
                        var tag_id;
                        for(var i in idsTagSel){
                            tag_id=idsTagSel[i];
                            oTaxBox.trigger('select',{id:tag_id,select:true});
                        };
                    };
                }else{//obs: se term_opt.tax_form_type for definido então o bloco abaixo não é executado
                    b.html('<em>Selecionar</em> '+tt);
                    a.html('');
                    oTaxBox.find('.checkmark,.box-footer').hide();
                    
                    if(opt.taxs_start){
                        //*** seleção dentro do tax_form *** 
                        var tag_ids=typeof(opt.taxs_start)=='string'?opt.taxs_start.split(','):opt.taxs_start;
                        for(var i in tag_ids){
                            oTaxBox.trigger('select',{id:tag_ids[i],select:true});
                        }
                    }
                };
                
                //exibe o metabox
                oTaxBox.trigger('show',{position:$(this)});
            });
            if(term_opt.tax_form_type=='set')oBt.hide();//oculta ao carregar
        };
        
        /*XXX descartado
        //exibe o botão somente quando houver registros da lista selecionados
        if(oBt && term_opt.tax_form_type){//tax_form_type==list|set
            oList.on({
                'onSelect':function(e,opt2){
                    if(term_opt.tax_form_type=='set'){
                        if(opt2.select){oBt.show();}else{oBt.hide();};
                    }else{//list
                        var n=oList.triggerHandler('get_select').length;//captura os selecionados
                        if(n==0){oBt.show();}else{oBt.hide();};
                    }
                },
                'load':function(){oBt.hide();}
            });
        };*/
    };
    
    for(var term_id in opt.terms){
        _fInitTerms(term_id,opt.terms[term_id]);
    };

    //eventos na lista
    var oTrsSelecteds=$();
    oList.on({
        'onSelect':function(e,opt2){
            oTrsSelecteds = opt2.select?opt2.oTrs:$();
        },
        'load':function(){oTrsSelecteds=$();}//reseta a var
    });
    
    
};




/* Adiciona um item html tag
 * Return object html
 * Informações de parâmetros e eventos em: view/templates/components/tag_item.blade
 */
function awTagItem(opts){
    var opt = $.extend(true,{
        id:'',          //identificador - opcional
        title:'Tag',    //indica o modo visual de controle do bloco
        link:null,      //link ao ser clicado
        class:'',       //classe adicional
        color:'',      //valores: green, yellow, teal, aqua, red, purple, maroon, navy, olive, black, gray ou hexadecimal (ex #ffcc99)
        icon:null,      //ex: fa-edit
        attr:'',        //atribute hmtl
        type:'badge',   //tipo do elemento utilizado, valores: badge ou btn
        btClose:false,  //exibe o botão fechar
        confirmClose:false,//true exibe a janela de confirmação, (string) exibe com mensagem personalizada, false não exibe
        events:false,   //se true aplica os eventos na tag
        //os parâmetros abaixo são para uma padronização das do recurso de taxonomias
        term_id:null,
        tax_id:null
    },opts);
    if(!opt.color)opt.color='gray';//default
    
    var c=opt.color.toLowerCase();
    var cls='margin-r-5 ',style='';
    if(opt.type=='badge'){cls+='badge ';}else{cls+='btn btn-xs ';};
    
    if($.inArray(c,['green','yellow','teal','aqua','red','purple','maroon','navy','olive','black','gray'])!=-1){//classe padrão de cor
        cls+='bg-'+c+' ';
    }else if(c.substr(0,1)=='#' || c.substr(0,3)=='rgb'){//cor hex ou rgb
        style+='background-color:'+c;
    };
    if(!opt.link)cls+='cursor-default '+ (!opt.btClose?'no-events ':'') ;
    cls+=opt.class;
    
    if(opt.term_id)opt.attr+=' data-term_id="'+opt.term_id+'"';
    if(opt.tax_id)opt.attr+=' data-tax_id="'+opt.tax_id+'"';
    opt.attr+='data-id="'+ (opt.id?opt.id:opt.tax_id) +'"';//será sempre igual a tax_id
    
    
    var os=$('<a '+ (opt.events?'ui-tagitem="on" ':'') +'class="ui-tagitem nostrong '+cls+'" '+ (opt.link?'href="'+opt.link+'"':'') + (style?'style="'+style+'"':'') +' '+opt.attr+'>'+
                '<span class="'+ (opt.link?'ui-tagitem-hover':'') +'">'+
                    (opt.icon?'<span class="fa '+opt.icon+' margin-r-5" style="font-size:0.8em;"></span>':'')+
                    opt.title+
                '</span>'+
                (opt.btClose?'<span title="Fechar" class="ui-tagitem-hover j-close fa fa-close cursor-pointer" style="margin-left:5px;font-size:0.8em;"></span>':'')+
            '</a>');
    if(opt.events)awTagItem_events(os,opt);
    return os;
};
//aplica os eventos do objeto da função awTagItem()
//pode ser chamada diretamente sem informar o parâmetro 'os' (undefined|false|null) , e neste caso pesquisa todos pela tag [ui-tagitem=on]
function awTagItem_events(os,opts){//opt = awTagItem()->opt  
    var _fGetJson=function(xthis){
        var j=xthis.attr('data-opt');
        return opts?opts:(j?$.parseJSON(j):{});
    };
    if(!os){
        os=$('[ui-tagitem=on]');
    }else if(os && os.eq(0).attr('ui-tagitem')!='on'){//foi informado um container
        os=os.find('[ui-tagitem=on]');
    };//else //foi informado diretamente a tag_item
    os.on({
        'click':function(e){
            var opt=_fGetJson($(this));
            if(opt.btClose){
                if($(e.target).hasClass('j-close')){
                    $(this).trigger('close');
                }
            }
        },
        'close':function(e){
            e.stopPropagation();
            var opt=_fGetJson($(this));
            if(opt.confirmClose){
                if(!confirm(opt.confirmClose===true?'Confirmar removação':opt.confirmClose))return false;
            };
            var o=$(this);
            if(o.triggerHandler('onBeforeClose')===false)return false;
            o.fadeOut('fast',function(){ o.trigger('onClose'); });
        }
    });
};