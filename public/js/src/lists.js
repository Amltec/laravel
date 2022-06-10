/*
Funções do arquivo:
    awListDataInit()

Requer:
    
*/


/* Executa as funções de inicialização da interface de lista de dados.
 Informações de parâmetros e eventos em: view/templates/ui/auto_list.blade
 */
function awListDataInit(oContainer){
    var baseListUI=oContainer ? oContainer.find('[ui-listdata=on]') : $('[ui-listdata=on]');
    if(baseListUI.length==0)return;
    
    baseListUI.each(function(){
        
        //aplica os eventos nas linhas
        var fRowsEvents=function(oNewTrs){
                if(options.field_group){
                    var oNewTrsGroup = oNewTrs.filter('.row-group');
                    oNewTrs = oNewTrs.filter('.row-item');
                };
            
                //seleções da linha
                if(options.select_type>0){//permite selecionar a linha
                    /*Params json opt:
                     *  select - (boolean) seleciona ou desseleciona a linha
                     */
                    oNewTrs.on('click.select',function(e,opt){
                        var tr=$(this);
                        if(tr.hasClass('row-lock-del'))return;//não deixa marcar
                        var isTrigger=typeof e.isTrigger !== 'undefined';
                        var tmp;

                        if(!isTrigger && (options.checkbox && $(e.target).attr('type')!='checkbox'))return; //clique pelo usuário e fora do campo checkbox
                        if(!opt)opt={};
                        var t=(typeof opt.select !== 'undefined' ? opt.select : !tr.hasClass('row-sel'));

                        if(options.checkbox){
                            tr.find('td[data-name=checkbox] input').prop('checked',t);
                        }


                        if(options.select_type===1){//permite apenas uma seleção
                            tmp=oTrs.filter('.row-sel');
                            if(tmp[0]!=tr[0])tmp.trigger('click',{select:false});//deseleciona
                        }
                        if(t){
                            tr.addClass('row-sel info');
                            if(options.collapse)tr.next().addClass('row-sel info');
                        }else{
                            tr.removeClass('row-sel info');
                            if(options.collapse)tr.next().removeClass('row-sel info');
                        }

                        var oTrItems;
                        if(options.select_type===2){//permite várias seleções
                            oTrItems=oTrs.filter('.row-item:not(.row-lock-del)');
                            
                            //verifica se todas as linhas forma marcadas
                            if(options.checkbox && !isTrigger){
                                oDivheader.find('[data-name=select-all]:eq(0)').prop('checked',oTrItems.filter('.row-sel').length==oTrItems.length);
                            }
                            //permite seleção múltiplica com o SHIFT
                            if(oTrClickI && oTrClickF && oTrClickI[0]==oTrClickF[0])oTrClickF=null;
                            if(typeof opt.select === 'undefined'){//se!==undefined, indica que disparado pelo trigger
                                if(!oTrClickI){//clique inicial
                                    oTrClickI=tr;
                                }else{//clique final
                                    oTrClickF=tr;
                                    //seleciona as linhas intermediárias
                                    if(e.shiftKey){
                                        //define o intervalo
                                        var a=oTrItems.index(oTrClickI);
                                        var b=oTrItems.index(oTrClickF);
                                        if(a>b){var z=b;b=a;a=z;};
                                        oTrItems.slice(a,b).trigger('click',{select:t});
                                    }
                                    oTrClickI=oTrClickF;oTrClickF=null;
                                }
                            }else{
                                oTrClickF=null;
                            }
                        };
                        
                        if(oTrItems && !isTrigger){
                            var oTrSelecteds=oTrItems.filter('.row-sel');
                            var count=oTrSelecteds.length;
                            base.trigger('onSelect',{select:t,count:count,oTrs:oTrSelecteds});
                            
                            //exibe / oculta elementos conforme ocorrer a selação da linha
                            _fSHItensOnRowSel(count);
                        };
                    });
                };
                
                
                //agrupamento de subtotal das linhas
                if(options.field_group){
                    oNewTrsGroup.each(function(){
                        var oGrTr=$(this);
                        oGrTr.on('click',function(e){
                            if(!$(e.target).hasClass('j-group-collapse'))return;
                            var os=oGrTr.nextAll('[data-group="'+oGrTr.attr('data-group')+'"]');
                            var oic=oGrTr.find('[data-name=group-collapse] .fa');//icon
                            if(oGrTr.hasClass('row-group-collapse')){
                                if(options.collapse){
                                    //caso exista blocos collapse, exibe somente os que já estão aparecendo
                                    os.filter(function(){
                                        var a=$(this);
                                        if(a.hasClass('row-collapse')){
                                            return a.hasClass('collapse-show');//exibe somente se houver a classe que indica que já estava aprecendo
                                        }else{//linha normal
                                            return true;
                                        }
                                    }).show();
                                }else{
                                    os.show();
                                };
                                oGrTr.removeClass('row-group-collapse');
                                oic.removeClass('fa-plus').addClass('fa-minus');
                            }else{
                                os.hide();
                                oGrTr.addClass('row-group-collapse');
                                oic.removeClass('fa-minus').addClass('fa-plus');
                            }
                        });
                    });
                };
                
                
                //collpsa mais opões da linha
                if(options.collapse){
                    oNewTrs.find('>.col-collapse:eq(0)').on('click',function(e,opt){//json opt: show==true|false
                        var isTrigger=typeof e.isTrigger !== 'undefined';
                        if(!opt)opt={};
                        var td=$(this);
                        var tr=td.parent();
                        var oTrCollapse=tr.next();
                        var t=(typeof opt.show !== 'undefined' ? opt.show : !oTrCollapse.hasClass('collapse-show'));
                        if(isTrigger)e.stopPropagation();

                        var icon=td.find('>a>.fa');
                        if(!t){
                            oTrCollapse.removeClass('collapse-show');
                            icon.removeClass('fa-minus').addClass('fa-plus');
                        }else{
                            oTrCollapse.addClass('collapse-show');
                            icon.removeClass('fa-plus').addClass('fa-minus');
                        };

                        if(t){
                            oTrCollapse.show();
                            if(!oTrCollapse.hasClass('loaded')){
                                var oContent=oTrCollapse.find('>.col-collapse-content > div:eq(0)');
                                oContent.html('<span class="fa fa-circle-o-notch fa-spin"></span> carregando');
                                awAjax({
                                    url: oTrCollapse.attr('data-url-collapse'),
                                    success: function(r){
                                        oTrCollapse.addClass('loaded');
                                        oContent.html(r.html);
                                        base.trigger('onCollapse',{oTr:tr,oTrCollapse:oTrCollapse,});
                                    },
                                    error:function (xhr, ajaxOptions, thrownError){
                                        oContent.html('Erro ao carregar: '+xhr.responseText);
                                    }
                                });
                            }
                        }else{
                            oTrCollapse.hide();
                        };

                    });
                };
                
                
                //clique na linha
                if(options.field_click=='' && options.field_click!==false){
                    var lockLastclicked=false;//obs: esta var controla se o click com shift no campo checkbox, que não pode disparar o click na linha
                    oNewTrs.on('click',function(e){
                        if(base.hasClass('table-read'))return;
                        var text_sel='';
                        
                        if(window.getSelection){
                            text_sel = window.getSelection().toString();
                        }else if(document.selection && document.selection.type != "Control"){
                            text_sel = document.selection.createRange().text;
                        };
                        if(text_sel==''){//quer dizer que não foi selecionado um texto, e somente neste caso é que dispara o click
                            var o=$(e.target);
                            var n=o.prop('nodeName');
                            var n2=o.parent().prop('nodeName');//node parent
                            var nds=['INPUT','SELECT','TEXTAREA','BUTTON','A'];
                            if($.inArray(n,nds)>-1 || $.inArray(n2,nds)>-1 || o.hasClass('col-collapse') || o.hasClass('col-check')){
                                //nenhuma ação
                                lockLastclicked=true;
                                setTimeout(function(){ lockLastclicked=false; },1000);
                            }else{
                                var tr=$(this);
                                var u=tr.attr('data-url-click');
                                if(u){
                                    setTimeout(function(){
                                        if(!lockLastclicked){
                                            if(base.triggerHandler('onOpen',{url:u,id:tr.attr('data-id'),oTr:tr})!==false){//anula o click se ==false
                                                if(e.shiftKey || e.ctrlKey){window.open(u);}else{window.location=u;}
                                            }
                                        }
                                    },10);
                                }
                            }
                        }
                    });
                }else{//click no link
                    oNewTrs.on('click','.row-lnk-click',function(e){
                        if(base.hasClass('table-read'))return;
                        var o=$(this);
                        var tr=o.closest('tr');
                        if(base.triggerHandler('onOpen',{url:o.attr('href'),id:tr.attr('data-id'),oTr:tr})===false)e.preventDefault();//anula o click se ==false
                    })
                };
                
                
                //footer
                if(oDivFooter.length>0 && options.route_load){//rota ajax, ajusta os links da páginação
                    oDivFooter.find('.pagination:eq(0) a')
                    .each(function(){
                        //atualiza com o link da rota, pois por padrão vem com o link página carregada
                        var n=qsToJSON($(this).attr('href'));
                        n=addQS(options.route_load,'page='+n.page,'string');
                        $(this).attr('href',n);
                    })        
                    .on('click',function(e){
                        e.preventDefault();
                        var qs=qsToJSON($(this).attr('href'));
                        //console.log(options.route_load,qs);return
                        delete qs._baseurl;
                        base.trigger('load',qs);
                    });
                };
                
                
                //atualiza as variáveis
                oTrs=(oTrs ? oTrs.add(oNewTrs) : oNewTrs);
                oTrsGroup=(oTrsGroup ? oTrsGroup.add(oNewTrsGroup) : oNewTrsGroup);
                //console.log(oTrs.filter('.row-item'))
                oTrClickI=null;
                oTrClickF=null;
        };
        
        var oDivLoading;
        var fBaseLoading=function(msg,idRow){//msg = 'remove' ou custom msg    //idRow opcional (update row)
            if(msg=='remove'){
                if(idRow){
                    //nenhuma ação
                }else{
                    if(oDivLoading){oDivLoading.trigger('remove');oDivLoading=null;}
                }
            }else{
                if(idRow){//obs: aqui pode modificar o html pois a linha será toda substituída via ajax
                    var tr=oTrs.filter('[data-id='+idRow+']');
                    var td=tr.addClass('row-processing no-events').find('td').eq(0);
                    td.html('<div style="opacity:1;position:absolute;width:30px;z-index:9;" title="Processando..."><span class="fa fa-circle-o-notch fa-spin"><span></div>');
                }else{
                    var opt={isAbsolute:true};
                    if(msg)opt.msg=msg;
                    oDivLoading=awLoading(base.find('.table-responsive:eq(0)'),opt);
                };
            };
        };
        
        
        var base=$(this).attr('ui-listdata','ok');
        var options=$.parseJSON(base.attr('data-opt'));
        
        var oHead=base.find('thead:eq(0)');
        var oDivheader = base.find('>.listdata-header:eq(0)');
        var oDivFooter = base.find('>.listdata-footer:eq(0)');
        
        //captura todas as linhas da tabela
        var oTBody = base.find('tbody:eq(0)');
        var oTrs = oTBody.find('>tr');
        if(options.field_group)var oTrsGroup;
        if(options.select_type>0)var oTrClickI,oTrClickF;//permite selecionar a linha
        
        //aplica os eventos na linha
        fRowsEvents(oTrs);
        
        //exibe / oculta elementos conforme ocorrer a selação da linha
        var _fSHItensOnRowSel=function(count_rows){//count_rows = total de linhas selecionadas
            if(!count_rows)count_rows=oTBody.find('>tr').filter('.row-item:not(.row-lock-del)').filter('.row-sel').length;
            //console.log(count_rows)
            var os=oh=$();
            if(oDivheader.length){
                oh=oh.add(oDivheader.find('.j-hide-on-select'));
                os=os.add(oDivheader.find('.j-show-on-select'));
            };
            if(oDivFooter.length){
                oh=oh.add(oDivFooter.find('.j-hide-on-select'));
                os=os.add(oDivFooter.find('.j-show-on-select'));
            };
            if(count_rows>0){
                os.show();oh.hide();
            }else{
                oh.show();os.hide();
            };
        };

        
        //barra de ferramentas
        if(options.toolbar){
            
            //atualiza a barra de ferramentas de acordo com os variáveis is_trash, etc
            //utilizado principalmente para os carregamentos ajax que não fazem reload na página
            var _fUpdHeader=function(){
                //botões de excluir da barra de ferramentas
                var os;
                os=oDivheader.find('.btns-group').hide();
                os.filter('.btns-'+ (options.is_trash?'trash':'normal') ).show();
                
                //menus de opções
                os=oMenuOpt.find('a[data-id=list_remove],a[data-id=list_remove2]').hide();
                os.filter('[data-id=list_remove'+ (options.is_trash?'2':'') +']').show();
            };
            var oMenuOpt=oDivheader.find('#'+base.attr('id')+'_menu_opt');
            if(options.list_remove){//Listar / sair da lixeira
                    oMenuOpt.find('a[data-id=list_remove],a[data-id=list_remove2]').on('click',function(){
                        var u=addQS(null,'page=&is_trash='+(options.is_trash?'n':'s'),'string');
                        if(options.route_load){//rota ajax
                            var qs=qsToJSON(u);
                            delete qs._baseurl;
                            base.trigger('load',qs);
                            options.is_trash=!options.is_trash;//atualizar a var que indica se está ou não na lixeira
                            _fUpdHeader();
                        }else{
                            goToUrl(u);
                        };
                    });
            };
            
            if(options.remove){//excluir / restaurar
                oDivheader.find('.j-uilist-remove').on('click',function(e){
                    if($(this).hasClass('j-uilist-destroy')){
                        base.trigger('remove',{destroy:true});
                    }else{
                        base.trigger('remove');//move para a lixeira
                    }
                });
                oDivheader.find('.j-uilist-restore').on('click',function(e){
                    base.trigger('remove',{restore:true});
                });
            }
            
            if(options.regs){//registros por página
                oDivheader.find('.j-uilist-regs').on('click',function(e){
                    var n=prompt('Digite o número de registros por páginas',options.perpage);
                    if($.trim(n)){
                        var u=addQS(null,'regs='+n,'string');
                        if(options.route_load){//rota ajax
                            var qs=qsToJSON(u);
                            //console.log(options.route_load,qs);//return
                            delete qs._baseurl;
                            base.trigger('load',qs);
                        }else{
                            goToUrl(u);
                        }
                    }
                });
            }
            
            if(options.columns_sel){//exibição de colunas
                oDivheader.find('.j-uilist-colsel').on('click',function(e){
                    var n=$(this).closest('.dropdown-menu').find(':checked').map(function(){ return $(this).parent().attr('data-id') }).get().join(',');
                    var u=addQS(null,'columns_show='+n,'string');
                    goToUrl(u);
                });
            }
            
            if(options.search){//campo de pesquisa
                 oDivheader.find('.j-uilist-search').on({
                    'select':function(e){e.stopPropagation();},
                    'keyup':function(e){
                        e.stopPropagation();
                        var k=e.keyCode;
                        var v=$.trim(this.value.toLowerCase());
                        if(k==27 || v==''){//esc
                            this.value='';
                        };
                        
                        if(k==13){
                            if(options.route_load){
                                base.trigger('load',{q:v});
                            }else{
                                var u=addQS(null,'q='+v,'string');
                                goToUrl(u);
                            }
                        };
                    }
                 });
            }
            
        };
        
        
        //dados
        base.data('options',options);
        
        
        //taxomonias padrões
        if(options.taxs){
            awTaxonomyToList({
                oList:base,
                terms:options.taxs,
                taxs_start:options.taxs_start,
            });
        };
        
        
        //eventos base
        base.on({
            'get_select':function(e,ret){//retorna aos selecionados (triggerHandler)
                var o=oTrs.filter('.row-sel.row-item');
                if(ret=='obj'){
                    return o;
                }else{
                    return o.map(function(){ return $(this).attr('data-id') }).get();
                }
            },
            'load':function(e,opt){//recarrega a lista
                e.stopPropagation();
                e.preventDefault();
                if(!opt)opt={};
                
                var rowPageOpt = oTBody.attr('data-row-page')?$.parseJSON(oTBody.attr('data-row-page')):{};
                
                /*var data=$.extend(true,{
                    page:options.page
                },opt);*/
                
                //ajusta os parâmetro access=public|private
                if(opt.access){
                    if(opt.private=opt.access=='private');
                    delete opt.access;
                }
                var data={};
                if(options._qs){//existe um querystring para mesclar os parâmetros
                    data = $.parseJSON(options._qs);
                    //console.log(options._qs,data);
                    //data = qsToJSON('?'+options._qs);
                    delete data.load_type;
                    delete data._baseurl;
                };
                data.page=options.page;
                data=$.extend(true,data,opt);
                
                //ajusta os parâmetros abaixo
                if(opt.id)data.filter_id=opt.id;
                if(opt.page){
                    if(opt.page=='next'){
                        data.page++;
                        if(data.page>rowPageOpt.totalpage)return;
                    }else if(opt.page=='prev'){
                        data.page--;
                        if(data.page<1)return;
                    }else{
                        data.page=opt.page;
                    };
                }else{
                    opt.page='';
                };
                
                data.is_trash=opt.is_trash ? opt.is_trash : (options.is_trash?'s':'');
                if(!data.q && options.search)data.q = oDivheader.find('.j-uilist-search').val();
                //console.log('list',data) 
                
                fBaseLoading(null,opt.id);
                
                //atualiza a url da rota load com os parâmetros de busca
                //options.route_load = addQS(options.route_load,data,'string'),  
                //console.log('x',data,options.route_load)
                //console.log(data)
                
                //console.log('x1',options.route_load,data)
                awAjax({
                    type:'GET',processData: true,//to GET
                    dataType:'html',
                    url: options.route_load,
                    data:data,
                    success: function(r){//return html <tboby>...</tbody>
                        r=r.split( String.fromCharCode(27)+'--divider--'+String.fromCharCode(27) );//0 tbody, 1 div footer
                        //console.log('*',r)
                        var oNewTBody = $(r[0]);
                        var oNewDivFooter = $(r[1]);
                        r = oNewTBody.html();
                        //console.log(oNewDivFooter.html());
                        options.page = data.page;
                        
                        fBaseLoading('remove');
                        //console.log('*',r,r=='')
                        if(r==''){
                            if(options.field_group)oTrsGroup.remove();
                            oTrs.remove();
                            if(oDivFooter.length>0)oDivFooter.html('');
                            return;
                        };
                        
                        //if($.trim(r)!=''){
                            var oNewTrs,x_ac='add';
                            if(opt.id){//update row
                                var tmp=oTrs.filter('[data-id='+opt.id+']');
                                if(tmp.length>0){//atualiza a linha
                                    oNewTrs=$(r).filter('[data-id='+opt.id+']');
                                    if(options.collapse)oNewTrs=oNewTrs.add(oNewTrs.next());
                                    oNewTrs=oNewTrs.insertBefore(tmp);
                                    oTrs=oTrs.not(tmp);//remove da var oTrs
                                    if(options.collapse)tmp.next().remove();
                                    tmp.remove();
                                    oNewTrs.css({opacity:0}).animate({opacity:1});
                                    x_ac='edit';
                                }
                            };
                            
                            if(x_ac=='add'){//é adição ou não não encontrou a linha para atualizar
                                if(opt.pos=='before'){
                                    oNewTrs=$(r).prependTo(oTBody);
                                }else if(opt.pos=='after'){
                                    oNewTrs=$(r).appendTo(oTBody);
                                }else{//load
                                    if(options.field_group)oTrsGroup.remove();
                                    oTrs.remove();
                                    oNewTrs=$(r).appendTo(oTBody);
                                    oTrs=null;
                                };
                            };
                            
                            //footer
                            if(!opt.id)if(oDivFooter.length>0)oDivFooter.html(oNewDivFooter.html());
                            
                            //aplica os eventos nas novas linhas
                            fRowsEvents(oNewTrs);
                        //};
                    },
                    error:function (xhr, ajaxOptions, thrownError){
                        fBaseLoading('remove');
                        awModal({title:'Erro ao carregar',html:xhr.responseText,msg_type:'danger',btSave:false});
                    }
                });
            },
            'select':function(e,opt){//json opt: id, select
                e.stopPropagation();
                e.preventDefault();
                if($(e.target).prop('nodeName')=='INPUT' || $(e.target).prop('nodeName')=='TEXTAREA')return;//estes campos disparam o um 'select' ao selecionar o texto
                if(!opt)opt={};
                if(options.select_type<2 && !opt.id)return;//não informado a linha
                var oTrItems=oTrs.filter('.row-item:not(.row-lock-del)');
                var oTrsFilter = (opt.id ? oTrItems.filter('[data-id='+opt.id+']') : oTrItems);
                var t=(typeof opt.select !== 'undefined' ? opt.select : !oTrsFilter.eq(0).hasClass('row-sel'));
                
                oTrsFilter.trigger('click.select',{select:t});
                
                var oTrSelecteds=oTrItems.filter('.row-sel');
                base.trigger('onSelect',{select:t,count:oTrSelecteds.length,oTrs:oTrSelecteds});
                
                //verifica se todas as linhas forma marcadas
                //if(options.checkbox && options.select_type==2)oDivheader.find('[data-name=select-all]:eq(0)').prop('checked',oTrItems.filter('.row-sel').length==oTrItems.length);
                if(options.checkbox && options.select_type==2)oDivheader.find('[data-name=select-all]:eq(0)').prop('checked',oTrSelecteds.length==oTrItems.length);
                
                //exibe / oculta elementos conforme ocorrer a selação da linha
                _fSHItensOnRowSel(oTrSelecteds.length);
            },
            'collapse':function(e,opt){//json opt: id, show
                e.stopPropagation();
                if(!opt)opt={};
                var oTrsFilter = (opt.id ? oTrs.filter('[data-id='+opt.id+']') : oTrs);
                oTrsFilter.find('>.col-collapse:eq(0)').trigger('click',{show:opt.show});
            },
            'remove':function(e,opt){//json opt: (int,array,obj) id, confirm (boolean), restore (boolean), destroy (boolean)
                e.stopPropagation();
                e.preventDefault();
                if(!opt)opt={};
                
                var oTrsFilter;
                
                if($.isNumeric(opt.id)){
                    oTrsFilter=oTrs.filter('[data-id='+opt.id+'].row-item');
                    
                }else if($.isArray(opt.id)){
                    oTrsFilter=oTrs.filter(function(){
                        var n=String($(this).attr('data-id'));
                        for(var i in opt.id){
                            if(n==String(opt.id[i]))return true;
                        }
                        return false;
                    }).filter('.row-item');
                    
                }else if(typeof opt.id === 'object'){
                    oTrsFilter=opt.id.filter('.row-item');
                    
                }else{//selecionados
                    oTrsFilter=base.triggerHandler('get_select','obj');
                };
                if(oTrsFilter.length==0)return false;
                
                
                if(typeof opt.confirm === 'undefined')opt.confirm=options.confirm_remove;
                if(opt.confirm){
                    if(!confirm(opt.confirm===true? (opt.restore?'Deseja restaurar?':(opt.destroy?'Deseja remover para sempre? Não é possível dezfazer esta ação.':'Deseja remover?')) :opt.confirm))return false;
                };
                
                
                
                if(oTrsFilter && oTrsFilter.length>0){
                    var _fRemove=function(){
                        var tr=oTrsFilter.eq(_iRem);
                        
                        if(base.triggerHandler('onBeforeRemove',{success:false,id:tr.attr('data-id'),oTr:tr,total:oTrsFilter.length,index:_iRem+1})===false){
                            //pula para o próximo item
                            _iRem++;
                            if(_iRem<oTrsFilter.length)_fRemove();//continua
                            return false;
                        };
                        
                        var oTmp=tr.addClass('row-processing').find('td').addClass('no-events').eq(0).removeClass('no-events no-padding').off()
                                .html('<div style="opacity:1;position:absolute;width:30px;z-index:9;" title="Processando..."><span class="fa fa-circle-o-notch fa-spin"><span></div>');
                        if(options.collapse)tr.next().remove();
                        
                        var __fE1=function(msg){
                            oTmp.find('>div').attr('title','Informações do erro').html('<span class="fa fa-warning"><span>');
                            tr.off('click').removeClass('row-item').addClass('row-error danger').on('click',function(e){
                                e.stopPropagation();
                                awModal({title:'Erro ao '+ (opt.restore?'restaurar':'excluir') +' registro #'+tr.attr('data-id'),html:msg,msg_type:'danger',btSave:false});
                            });
                        };
                        
                        var action='';
                        if(options.route_remove){
                            if(options.allow_trash==false){ action='remove'; }else{ action=(opt.destroy?'remove':(opt.restore?'restore':'trash')); }
                            var url_rem = options.route_remove.replace(':id',tr.attr('data-id'));//obs: este replace é apenas para o caso da rota estar assim ex: '/admin/user/destroy/:id'
                            
                            //console.log('a',url_rem,$.extend(true, options.post_data, {id:tr.attr('data-id'),action:action,_method:'DELETE'}))
                            awAjax({
                                data: $.extend(true, options.post_data, {id:tr.attr('data-id'),action:action,_method:'DELETE'}),
                                url: url_rem,
                                processData: true,
                                success: function(r){
                                    if(r.success){
                                        tr.fadeOut('fast',function(){tr.remove();});
                                    }else{
                                        __fE1(r.msg);
                                    };
                                    _iRem++;
                                    if(_iRem<oTrsFilter.length)_fRemove();//continua

                                    base.trigger('onRemove',{success:r.success,id:tr.attr('data-id'),oTr:tr,total:oTrsFilter.length,index:_iRem});
                                    _fSHItensOnRowSel();//exibe / oculta elementos conforme ocorrer a selação da linha
                                },
                                error:function (xhr, ajaxOptions, thrownError){
                                    __fE1(xhr.responseText);
                                    _iRem++;
                                    if(_iRem<oTrsFilter.length)_fRemove();//continua
                                    base.trigger('onRemove',{success:false,id:tr.attr('data-id'),oTr:tr,total:oTrsFilter.length,index:_iRem});
                                    _fSHItensOnRowSel();//exibe / oculta elementos conforme ocorrer a selação da linha
                                }
                            });
                        }else{
                            //apenas remove do dom
                            tr.fadeOut('fast',function(){tr.remove();});
                            _iRem++;
                            if(_iRem<oTrsFilter.length)_fRemove();//continua
                            base.trigger('onRemove',{success:true,id:tr.attr('data-id'),oTr:tr,total:oTrsFilter.length,index:_iRem});
                            _fSHItensOnRowSel();//exibe / oculta elementos conforme ocorrer a selação da linha
                        }
                    };
                    var _iRem=0;
                    _fRemove();
                }else{
                    //console.log('Nenhum registro selecionado');
                }
            }
            
        });
    });
};
