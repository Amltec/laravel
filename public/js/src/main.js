/*Funções do arquivo.

//Funções para todos os casos
dashboardSet()             - seta a informação global que existem processamentos pendentes
dashboardGet()             - captura a informação global que existem processamentos pendentes

awBtnPostData()
awProgressCircle()
awSerializeToJson()
awModal()
awModalSetMaxHeight()
awLoading()
awCount()
addQS()
removeAccent()
convertToSlug()
trimx()
uniqueArr()
qsToJSON()
getFieldQS()
callfnc()
fnNumber()
formatCurrency()
goToUrl()
awFncJsInit()       - Dispara as funções a partir do nome em string
awAddScriptJs()     - Adiciona um comando global js a ser executado.
awLogin()
awTokenUpd()
awCookieAdd()
awCookieRead()
awCookieDel()
awJCookie()
awComponent()
awImport()
fnExists()


Variáveis globais:
    jqDocScroll     - (object) scroll
    aw_script_js    - (array) variável global de inicialização

Padronizações de plugins
    awAjax() - função para substituir o $.ajax()
    ajaxSetup

*/


var jqDocScroll;
var dashboard_vars={
    navbar_height:50,
    save_arr:0,//controle de post save automático (deve ter o número pedidos, ex: 2 - corresponde a 2 registros sendo salvos)
    upl_arr:0, //de controle de upload automático
    modal_zindex:0,//last zindex modal
    exit_page:false,//se true quer dizer que o usuário executou comando para sair da tela
    scripts_loaded:[],
    modal_delay:500,//tempo de transição default boostrap modal
    auth_jx_reload_count:0,//conta quantas requisições de login ajax falharam no reload
    forms:[],//lista de forms
}
/*Seta a informação global que existem processamentos pendentes.
Parâmetro json opt:
    is_saving|is_uploading      - (boolean) se true, indica que o registro está sendo salvo, false já foi salvo
*/
function dashboardSet(opt){
    if(opt.is_saving===true){dashboard_vars.save_arr++;}
    else if(opt.is_saving===false){dashboard_vars.save_arr--;if(dashboard_vars.save_arr<0)dashboard_vars.save_arr=0;}
    
    if(opt.is_uploading===true){dashboard_vars.upl_arr++;}
    else if(opt.is_uploading===false){dashboard_vars.upl_arr--;if(dashboard_vars.upl_arr<0)dashboard_vars.upl_arr=0;}
}
/*Captura a informação global que existem processamentos pendentes
Parâmetro string cmd - valores:
    is_saving|is_uploading      - return boolean indica existe o registro está sendo salvo (true saving, false not saving)
*/
function dashboardGet(cmd){
    if(cmd=='is_saving'){
        return !dashboard_vars.save_arr==0;
    }else if(cmd=='is_uploading'){
        return !dashboard_vars.upl_arr==0;
    }
}



/** Executa um postdata para um controller via ajax a partir dos dados json
 * @param json opt: url(str), data(json), string(method), confirm(bool|str), cb(fnc)
 * @param object html button
 * Ex de uso: <a href='#' onclick='awBtnPostData(json param);'>text</a>
 * Obs: é sempre esperado um json de retorno do ajax
 */
function awBtnPostData(opt,button){
    if(!(opt.url || opt.data)){alert('awBtnPostData(): Parâmetros incorretos');return;};
    if(opt.confirm)if(!confirm(opt.confirm===true?'Confirmar ação?':opt.confirm))return;
    
    var bt;
    if(button){
        bt=$(button);
        bt.attr('data-textdef',bt.html());
        bt.html( (bt.text()!=''? bt.html()+'<span style="margin-left:7px;"></span>' : '') + '<span class="fa fa-circle-o-notch fa-spin"></span>').prop('disabled',true);
    };
    awAjax({
        url: opt.url,
        data:opt.data,
        type:opt.method=='GET'?'GET':'POST',
        processData: true,
        success: function(r){
            if(bt)bt.html(bt.attr('data-textdef')).prop('disabled',false);
            r.oBt=bt;
            if(opt.cb)callfnc(opt.cb,r);
            //xxx bt.trigger('onRemove',r);
        },
        error:function (xhr, ajaxOptions, thrownError){
            if(bt)bt.html(bt.attr('data-textdef')).prop('disabled',false);
            var j={status:'E',msg:'Erro interno de servidor',data:xhr.responseText,oBt:bt};
            if(opt.cb)callfnc(opt.cb,j);
            awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
            //xxx bt.trigger('onRemove',j);
        }
    });
};


/*** obs: está funcionando, mas como foi não está sendo utilizado, foi temporariamente desativado ***
/*Progressbar circle
Event: 
    progress()  - ex: obj.trigger('progress',67);
    
Return obj
* /
function awProgressCircle(opts){
    var opt = $.extend(true,{
        base:null,   //append jquery
        progress:0,  //progress init
        html:false,  //se true, return only html
        number:true, //se false hide humber
        size:40,    //size width
        border:2,    //size border
        fontSize:11,
    },opts);
    
    var html='<div class="radial-progress-bar" style="width:'+opt.size+'px;height:'+opt.size+'px;">'+
            '<div class="right-half"></div>'+
            '<div class="left-half-mask"></div>'+
            '<div class="left-half"></div>'+
            '<div class="inner-circle" style="width:'+(opt.size - opt.border*2)+'px;height:'+(opt.size - opt.border*2)+'px;margin: '+opt.border+'px;">'+
                (opt.number ? '<span class="percentage" style="font-size:'+opt.fontSize+'px;line-height:'+(opt.size - opt.border*2)+'px;"></span>' : '')+
            '</div>'+
        '</div>';
        
    if(opt.html){
        return html;
        
    }else{
        var o=$(html);
        
        if(opt.base)opt.base.append(o);

        var items = {
            leftHalf: o.find(".left-half"),
            rightHalf: o.find(".right-half"),
        };
        if(opt.number)items.indicator = o.find(".percentage");
        
        o.on('progress',function(e,p){
            e.stopPropagation();
            if (p > 100)p = 100;
            if (p < 0)p = 0;
            if (p <= 50) {
                items.leftHalf.css('visibility','hidden');
                items.leftHalf.css('transform','rotate(180deg)');
                items.rightHalf.css('transform','rotate(' + (p * 3.6) + 'deg)');
            } else {
                items.leftHalf.css('visibility','visible');
                items.rightHalf.css('transform','rotate(180deg)');
                items.leftHalf.css('transform','rotate(' + (p * 3.6) + 'deg)');
            };
            console.log(items.indicator)
            if(items.indicator)items.indicator.text(Math.round(p) + '%');
        });

        if(opt.progress)o.trigger('progress',opt.progress);

        return o;
    }
};
*/


//Converte campos Jquery para Json
function awSerializeToJson(objs){
    var o = {};
    var a = objs.serializeArray();
    $.each(a, function() {
        if (o[this.name]) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
}



/*Cria uma janela modal em js.
Retorna ao objeto da janela.
Eventos: .modal(eventname): show, hide, toogle, handleUpdate
Eventos para personalização: hidden.bs.modal, shown.bs.modal, hide.bs.modal, hidden.bs.modal, loaded.bs.modal
Obs: por padrão, ao fechar é apenas o ocultado o objeto
Mais informações em https://getbootstrap.com/docs/3.4/javascript/
Eventos adicionais:
    resize.modal    - se options.height='hmax'
Eventos:
    shown.bs.modal  - on show...
*/
function awModal(opts){
    var opt = $.extend(true,{
        id: Math.random().toString(36).substr(2, 9),
        title:'Title',      //string título, boolean false para ocultar
        descr:'Description',//string descrição, boolean false para ocultar
        html:null,          //string html | function(oHtml,oTitle,oHeader,oFooter){oHtml.html(...);} | json {msg{field1:msg1,...}}   (se definido substitui o param descr)
        iconClose:true,     //boolean
        btClose:'Fechar',   //string rótulo, boolean false para ocultar
        btSave:false,       //string rótulo, boolean false para ocultar
        esc:true,           //boolean se false desativa o esc
        hideBg:true,        //boolean se false não oculta ao clicar no fundo da janela
        msg_type:'',        //tipos padrões de mensagens: valores: danger, info, warning, success, '' (padrão)
        removeClose:true,   //indica se irá remover no evento close
        class:'',           //classe da janela. Valores já programados: modal-vcenter (vertical align)
        form:false,         //atributos da tag form para criação do formulário, ex: 'method='...' action='...' (obs: a tag envolve todo o modal)
                                //Obs: este recurso irá ativar a função awFormAjax() dentro desta janela modal
        form_opt:null,     //válido somente se informado o parâmetros 'form' - são os mesmos parâmetros awFormAjax(..,params)
        onClose:null,       //função ao clicar no botão fechar. Recebe os parâmetro: oHtml, oBase.
        onSave:null,         //função ao clicar no botão salvar. Recebe os parâmetro: oHtml, oBase. 
                                //Obs: este parâmetro apenas dispara o evento no 'onclick', mas se estiver com formulário, considere usar o parâmetro form_opt:{onSuccess:,onError}, para capturar o retorno do form
        padding:true,       //se false desativa o padding da janela
        width:'',           //largura da janela interna. Valores: (string) '', sm, lg, wmax     ou (int) max-width
        height:'',          //altura da janela interno. Valores: (string) '', h100 ou hmax,
        zIndex:null,        //zindex
    },opts);
    //Obs: para ocultar a janela, pode-se utilizar o comando oModal.modal('hide')
    
    var bg_clr='',bt_clr=(opt.msg_type!=''?opt.msg_type:'primary');
    switch(opt.msg_type) {
        case 'danger'   : bg_clr = 'bg-red';  break;
        case 'info'     : bg_clr = 'bg-aqua';  break;
        case 'warning'  : bg_clr = 'bg-yellow';  break;
        case 'success'  : bg_clr = 'bg-green';  break;
    };
    
    //if(!opt.padding)opt.class+=' no-padding';
    
    if(!opt.zIndex)opt.zIndex=dashboard_vars.modal_zindex;
    var class_dialog = opt.width && typeof(opt.width)=='string' && $.inArray(opt.width,['sm','lg','wmax'])!==-1 ? ' modal-'+opt.width : '';
    if(opt.height && typeof(opt.height)=='string' && $.inArray(opt.height,['h100'])!==-1)class_dialog+=' modal-'+opt.height;
    
    var r='<div class="modal fade '+ opt.class +'" role="dialog" id="modal_'+opt.id+'" '+ (opt.zIndex?'style="z-index:'+opt.zIndex+';"':'') +'>'+
        (opt.form?'<form '+opt.form+'><input name="_token" type="hidden" value="'+admin_vars.token+'">':'')+
        '<div class="modal-dialog'+ class_dialog +'" role="document" style="'+  (opt.width && typeof(opt.width)=='number'?'max-width:'+opt.width+'px;':'') +'">'+
            '<div class="modal-content">'+
                (opt.title !==false ? 
                    '<div class="modal-header '+bg_clr+'">'+
                        (opt.iconClose ? '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' : '')+
                        '<h4 class="modal-title"> '+ opt.title +'</h4>'+
                    '</div>'
                :
                    (opt.iconClose ? '<div class="modal-header" style="position:absolute;z-index:9;right:0;border:0;"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>' : '')
                );
                
                if(opt.html){
                    r+= '<div class="modal-body'+ (opt.height=='hmax' || opt.height=='h100'?' scrollmin':'') +'"'+ (!opt.padding?' style="padding:0;"':'') +'>';
                        if($.type(opt.html)=='object'){//json
                            var z=[];
                            for(var i in opt.html){
                                z.push(i.toUpperCase() +': '+opt.html[i]);
                            };
                            r+='<div><p>'+z.join('<br>')+'</p></div>';
                            
                        }else if($.type(opt.html)!='function'){//esperado 'string'
                            r+='<div>'+opt.html+'</div>';
                        }
                    r+= '</div>';
                }else if(opt.descr){
                     r+= '<div class="modal-body'+ (opt.height=='hmax' || opt.height=='h100'?' scrollmin':'') +'"'+ (!opt.padding?' style="padding:0;"':'') +'><p>' + opt.descr + '</p></div>';
                };
                
                if(opt.btClose!==false || opt.btSave!==false){
                    r+='<div class="modal-footer">'+
                            (opt.btClose!==false ? '<button type="button" class="btn btn-default pull-left" data-dismiss="modal">'+ opt.btClose +'</button>' : '')+
                            (opt.btSave!==false ? '<button type="'+ (opt.form?'submit':'button') +'" class="btn btn-'+bt_clr+' pull-right j-btn-save last-focus" >'+ opt.btSave +'</button>' : '')+
                       '</div>';
                };
                
               //estrutura html parcial das mensagens do form (o mesma view.templates.components.alert-structure.blade)
               if(opt.form)
                   r+='<div style="padding: 10px;">'+
                        '<div class="alert alert-danger alert-form hiddenx" style="margin-bottom:0;">'+
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>'+
                            '<p class="alert-msg"></p>'+
                            '<a href="#" class="small alert-link-content" onclick="$(this).next().fadeToggle(\'fast\');return false;" style="display: none;">mais</a>'+
                            '<div class="hiddenx alert-content"></div>'+
                        '</div>'+
                       '</div>';
               
         r+='</div>'+
        '</div>'+
         (opt.form?'</form>':'')+
    '</div>';
    var oBase=$(r).appendTo(document.body).modal({
        keyboard:opt.esc,
        backdrop:opt.hideBg
    });
    
    var oHtml=oBase.find('.modal-body');
    if(typeof(opt.html)=='function'){
        opt.html.call(null,oHtml,oBase.find('.modal-title'),oBase.find('.modal-header'),oBase.find('.modal-footer'));
    };
    
    if(opt.removeClose){
        oBase.on('hidden.bs.modal',function(){
            oBase.data('bs.modal',null).remove();
            if(opt.onClose)callfnc(opt.onClose);
        });
    };
    //função callback ao fechar
    if(typeof(opt.onClose)=='function'){
        oBase.on('hidden.bs.modal',function(){ opt.onClose.call(null,oHtml,oBase) });
    };
    
    //função ao salvar
    if(typeof(opt.onSave)=='function'){
        oBase.on('click','.j-btn-save',function(){opt.onSave.call(null,oHtml,oBase);});
    };
    
    if(opt.form){
        //adiciona demais parâmetros para o awFormAjax
        if(opt.form_opt && !opt.form_opt.btAutoUpload && opt.btSave===false)opt.form_opt.btAutoUpload=oBase.find('[name=file]');
        awFormAjax(oHtml.closest('form'),opt.form_opt);
    };
    
    dashboard_vars.modal_zindex = parseInt(oBase.css('z-index'))+1;
    
    if(opt.height=='hmax' || opt.height=='h100'){
        $(window).on('resize.modal_'+opt.id,function(){awModalSetMaxHeight(oBase,opt);});
        oBase.on({
            'resize.modal':function(){awModalSetMaxHeight(oBase,opt);},
            'hide.bs.modal':function(){$(window).off('resize.modal_'+opt.id);},
            //'show.bs.modal':function(){oBase.show();awModalSetMaxHeight(oBase,opt);},
        });
        oBase.show();//necessário para o correto resize abaixo na inicialização do objeto
        awModalSetMaxHeight(oBase,opt);
    };
    
    return oBase;
}

//set maximize modal (função complementar de awModal()0
function awModalSetMaxHeight(element,opt){
  this.$element     = element;  
  this.$content     = this.$element.find('.modal-content');
  var borderWidth   = this.$content.outerHeight() - this.$content.innerHeight();
  var dialogMargin  = $(window).width() < 768 ? 20 : 60;
  var contentHeight = $(window).height() - (dialogMargin + borderWidth);
  var headerHeight  = opt.title !==false ? (this.$element.find('.modal-header').outerHeight() || 0) : 0;
  var footerHeight  = opt.btClose!==false || opt.btSave!==false ? (this.$element.find('.modal-footer').outerHeight() || 0)  : 0;
  var maxHeight     = contentHeight - (headerHeight + footerHeight);
  
  this.$content.css({'overflow': 'hidden'});
  var css={'overflow-y': 'auto'};
  if(opt.height=='h100'){css['height']=maxHeight;}else{css['max-height']=maxHeight;};
  this.$element.find('.modal-body').css(css);
};






/* Janela de carregando em DIV
 * @param object container - div que conterá o loading
 * @param json opt (opcional)
 *   msg - mensagem
 *   isAbsolute - (boolean) se true utiliza js para calcular as dimensões do objeto
 *   fixed - (boolean) se true fica o objeto em toda a tela
 *   marginTop - (int) margem do topo
 * Eventos: 
 *      remove  - remove o loading
 *      msg     - altera a mensagem
 */
function awLoading(container,opt){
    if(!opt)opt={};
    var o=$('<div class="aw-loading no-select'+ (opt.fixed?' fixed':'') +'">'+
        '<div class="aw-loading-in">'+
            '<span class="fa fa-circle-o-notch fa-spin" style="margin-right:7px;"></span>'+
            '<span class="aw-loading-msg">'+(opt.msg?opt.msg:'processando')+'</span>'+
            '</div>'+
        '</div>')
    .prependTo(container).on({
        'remove':function(e){
            e.stopPropagation();
            e.preventDefault();
            $(this).fadeOut('fast',function(){$(this).remove();});
        },
        'msg':function(e,msg){
            e.stopPropagation();
            $(this).find('.aw-loading-msg').hide().fadeIn().html(msg);
        }
    });
    
    if(opt.isAbsolute){
        var p=container.position();
        if(!opt.marginTop)opt.marginTop=0;
        o.css({height:container.height(),top:p.top+opt.marginTop});
    };
    
    return o;
};







/**
 * Função auxiliar do componente tax_form com um padrão para setar taxs selecionados.
 * Return void
 * Methods: onClickItem
 * Trigger: cb_clickItem    - após adicionar ou remover um item
 */
function awTaxonomyToObj(opts){
    var opt = $.extend(true,{
        tax_form:null,     //object jquery ou id seletor tax form
        area_name:null,
        area_id:null,
        tags_item_obj:null, //object jquery ou id selector do objeto .row-taxonomy-items para inserção das tags itens   // também é possível informar o json term_id para busca automática, ex: {term_id:1}
        onClickItem:null,   //função a ser executada após clicar e salvar item selecionado. Parâmetros: os mesmos do padrão tax_form
        button:null,        //object jquery ou id selector do botão para exibir do tax_form
        button_pos:null     //array [x,y] - posição do botão. Caso não informado será capturado automaticamente a partir de 'button'
    },opts);
    if(! (opt.tax_form || opt.area_name || opt.area_id ))return;
    
    var oObject=typeof(opt.obj)=='string' ? $(opt.obj) : opt.obj;
    
    var oTaxBox=(typeof(opt.tax_form)=='string' ? $(opt.tax_form) : opt.tax_form).on({
        'onClickItem':function(e,opt2){
            var item=opt2.item;
            awTaxPostRelation({
                action:(opt2.sel?'add':'del'),
                tax_id:item.attr('data-tax_id'),
                area_name:opt.area_name,
                area_id:opt.area_id,
                cb:function(r){
                    if(r.success){
                        var objTagItem=opt.tags_item_obj;
                        if(typeof(objTagItem)=='object'){
                            if(objTagItem.term_id)objTagItem = $('.row-taxonomy-items[data-term_id='+objTagItem.term_id+']');
                        }else{//string
                            objTagItem = $(objTagItem);
                        };
                        if(objTagItem){
                            if(opt2.sel){//add
                                opt2.oTagItem = awTagItem({
                                    title:item.attr('data-tax_title'),
                                    term_id:term_id,
                                    tax_id:item.attr('data-tax_id'),
                                    color:item.attr('data-tax_color'),
                                    icon:item.attr('data-tax_icon'),
                                }).appendTo( objTagItem );
                            }else{//del
                                objTagItem.find('.ui-tagitem[data-id='+item.attr('data-tax_id')+']').remove();
                            };
                        };
                        oTaxBox.trigger('close');//fecha o box da taxonomia
                        
                        if(opt.onClickItem)opt.onClickItem.call(null,opt2);
                        
                        oTaxBox.trigger('cb_clickItem',opt2);
                    }
                }
            });
        }
    });
    
    if(opt.button){
        (typeof(opt.button)=='string' ? $(opt.button) : opt.button).on('click',function(e){
            e.preventDefault();
            oTaxBox.trigger('show',{position: (opt.button_pos ?? opt.button) });
        });
    };
};





//Conta o total de elementos de uma varável de qualquer tipo
function awCount(v){
    var r=0,t=$.type(v);
    if(t=='object'){
        r=Object.keys(v).length;
    }else if(t=='number'){
        r=String(v).length;
    }else{
        r=v.length;
    };
    if(!r)r=0;
    return r;
};



function addQS(urlOrig,qsa,retType){//adiciona uma nova querystring na url/querystring já existente // atualize e/ou insere novos valores. 
	/*	
        Esta função retorna a url modificada no formato JSON: {url:url,qs:qs}.
        parametros
                urlOrig 	- url original. Pode estar completa 'arquivo?querystring' (ex: load.asp?pag=123), somente arquivo (ex: load.asp) ou apenas query string (ex: '?pag=123' - o parametro interrogação (?) é necessário neste caso (se não será considerado nome de arquivo)).
                                    aceita o valor null|false - que indica que deverá caputrar a url atual: admin_vars.url_current+'?'+admin_vars.querystring
                qsa 		- corresponde ao querystring adicional/substitúido da url original. Este parametro é requerido. Caso não informado, retorna a url original.
                retType 	- Valoroes: json(default) | string - se =string, indica que deve retornar a função a uma única string.
        Obs.:	Os parametros querystring para comparação são sensitivos, ou seja diferenciam maiúsculas e minúsculas
	*/
	//a var 'qs' armazena o qs original, e a var 'qsa' contém o qs modificado
        if(!urlOrig)urlOrig = admin_vars.url_current+'?'+admin_vars.querystring;
        
	var url=urlOrig.split('?')[0];
	var qs=urlOrig.split('?')[1];if(qs==undefined)qs='';
        if(typeof(qsa)=='object')qsa=$.param(qsa);//convert json to string
	if(qsa!=undefined && qsa!=''){
		//divide o querystring atual e original e compara para checar qual foi alterado
		var qxOrig=qs.split('&');//toLowerCase().
		var qxParam=qsa.split('&');//toLowerCase().
		var qsMod='',a='';//query string modificado
		var i=0,x=0,s=0;
		
                var _fx1=function(fv){//add qs se o valor não for null
                    var n=fv.split('=');//0 field, 1 value
                    if($.trim(n[1])!='' && $.trim(n[1])!='null'){
                        return fv+'&';
                    }else{
                        return '';
                    }
                };
                
		for(i=0;i<qxOrig.length;i++){//verifica e modifica os querystring correspondentes
			s=0;
			for(x=0;x<qxParam.length;x++){
				if(qxOrig[i].split('=')[0]==qxParam[x].split('=')[0]){//encontrou os campos
					if(qxOrig[i].split('=')[1]!=qxParam[x].split('=')[1]){//ocorreu modificações nos valores
                                                a=_fx1(qxParam[x]);
                                                if(a)qsMod+=a;//caso !a, então não adiciona por estar vazio
                                                s=1;
                                                break;//pula para o próximo parametro no querystring
					}
				}
			};
			if(s==0)qsMod+=_fx1(qxOrig[i]);//+'&';//não encontrou o parametro, então mantém o original
		};
		
		for(x=0;x<qxParam.length;x++){//verifica e adiciona os querystring não correspondentes (novos querystring...)
			s=0;
			for(i=0;i<qxOrig.length;i++){
				if(qxOrig[i].split('=')[0]==qxParam[x].split('=')[0]){//encontrou os campos
					s=1;break;
				}
			};
			if(s==0)qsMod+=_fx1(qxParam[x]);//+'&';//não encontrou o parametro, então adiciona o novo
		};
		if(qsMod!='')qsMod=qsMod.substring(0,qsMod.length-1);
		if(qsMod.substring(0,1)=='&')qsMod=qsMod.substring(1,qsMod.length);

		//console.log(qsa,'*',qs,'*',qsMod);	
		qs=qsMod;
		
		qxOrig=null;
		qxParam=null;
		qsMod=null;
		i=null;
		x=null;
		s=null;
	};
	
	if(retType=='string'){//retorno em string
		return url+'?'+qs;
	}else{//retorno em json
		return {url:url,qs:qs};
	};
}

//Retira o acento
function removeAccent(text){       
    return text.toLowerCase()
        .replace(new RegExp('[ÁÀÂÃ]','gi'), 'a')
        .replace(new RegExp('[ÉÈÊ]','gi'), 'e')
        .replace(new RegExp('[ÍÌÎ]','gi'), 'i')
        .replace(new RegExp('[ÓÒÔÕ]','gi'), 'o')
        .replace(new RegExp('[ÚÙÛ]','gi'), 'u')
        .replace(new RegExp('[Ç]','gi'), 'c');
}
//Converte string to slug
function convertToSlug(Text){
    var v=removeAccent(Text.toLowerCase());
    return trimx(v.replace(/[^\w ]+/g,'-').replace(/ +/g,'-').replace(/\-+/g,'-'),'-');
}
//Trim com caracteres
function trimx(s,c){
    if(c==="]")c="\\]";
    if(c==="\\")c="\\\\";
    return s.replace(new RegExp("^[" + c + "]+|[" + c + "]+$", "g"), "").trim();
}

//Remove índices duplicados do array
function uniqueArr(a){
    var uniqueNames = [];
    $.each(a, function(i, el){
        if($.inArray(el, uniqueNames) === -1) uniqueNames.push(el);
    });
    return uniqueNames;
}

//Querystrin to Json //Param url_qs - url + querystring
function qsToJSON(url_qs){
    if(url_qs.indexOf('?')===-1){
        return {_baseurl:url_qs};
    }else{
        let pairs = (url_qs?url_qs.substr(url_qs.indexOf('?'),url_qs.length):location.search).slice(1).split('&');
        let result = {};
        pairs.forEach(function(pair) {
            pair = pair.split('=');
            result[pair[0]] = decodeURIComponent(pair[1] || '');
        });
        result._baseurl=url_qs.substr(0,url_qs.indexOf('?'));
        return JSON.parse(JSON.stringify(result));
    }
}

//Captura o campo de um campo querystring da uma url
//Se definido 'url', então irá capturar o campo a partir da url informada
//Return string value or null se não encontrado
function getFieldQS(name,url){
    var reParam = new RegExp( '(?:[\?&]|&)' + name + '=([^&]+)', 'i' );
    var match = (url?url:window.location.search).match( reParam );
    return ( match && match.length > 1 ) ? match[1] : null;
};

/*  O mesmo da função 'call()' mas verifica se é uma 'string funcion' e converte-a com o eval antes.
 * Irá verificar e converter somente se o primeiro caractere for o '@'.
 * Exemplos: 
 *      callfnc('@function(){..}')  //ok
 *      callfnc('@function_name')   //ok (a função precisa existir na variável)
 *      callfnc(function(){..})     //ok
 *      callfnc('x@function(){..}') //error - return same text
 *      callfnc('function(){..}')   //error - return same text
 *      callfnc('897897')           //error - return same text
 *      callfnc([fnc1,fn2....])     //ok - return array de cada função
 */
function callfnc(fnc,params){
    if($.type(fnc)=='array'){
        var r=[];
        for(var i in fnc){
            r.push( callfnc(fnc[i],params) );
        }
        return r;
    }else{

        if(typeof(fnc)=='string' && fnc.substr(0,1)=='@'){// && fnc.substr(1,9)=='function('
            fnc=eval('('+fnc.substr(1,fnc.length)+')');
            return fnc.call(null,params);
        }else if(typeof(fnc)=='function'){
            return fnc.call(null,params);
        }else{//return same fnc
            return fnc;
        }
    }
}

//converte nmero de '0,0' para '0.0'
function fnNumber(v){
    var n=parseFloat(String(v).replace('r$','').replace('R$','').replace(' ','').replace(/\./g,'').replace(',','.'));
    if(!$.isNumeric(n))n=0;
    return n;
}
//formato o número como moeda. Ex de 1000,55 para 1.000,55.
function formatCurrency(numx,nDec){
    if(numx==undefined)return '';
    var num = numx.toString().replace(/\$|\,/g,'');
    if(isNaN(num))num = "0";
    var sign = (num == (num = Math.abs(num)));
    num = Math.floor(num*100+0.50000000001);
    var cents = num%100;
    num = Math.floor(num/100).toString();
    if(cents<10)cents = "0" + cents;
    for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++)
    num = num.substring(0,num.length-(4*i+3))+'.'+ /*em '+'.'+ seria o ponto para formatacacao'*/
    num.substring(num.length-(4*i+3));
    return (((sign)?'':'-') + '' + num +(nDec?','+cents:''));
}





//Redireciona uma url
function goToUrl(url){
    var e=window.event;
    e.preventDefault();
    if(e.ctrlKey || e.shiftKey){
        window.open(url);
    }else{
        window.location=url;
    }
};



/*
Função que carrega funções a partir do nome em string. Ex: "fnc1,fnc2..." //dispara cada uma das funções.
 */
function awFncJsInit(fnc_names,scripts_names){
    var n,f=fnc_names.split(',');
    for(var i in f){
        n=window[f[i]];
        if(n)n();
    };
    
    //atualiza os nomes scripts já carregados
    if(scripts_names){
        f=scripts_names.split(',');
        for(i in f){
            if($.inArray(f[i], dashboard_vars.scripts_loaded)===-1)dashboard_vars.scripts_loaded.push(f[i]);
        }
    };
};



/**
 * 
 * Função que chama a janela de login
 */
var awLoginObj=null;
function awLogin(callback,modalOpt){
    var opt_modal=$.extend(true,{btSave:false,btClose:false,width:380,padding:false,iconClose:false,esc:false,hideBg:false,class:'modal-vcenter modal-login'},modalOpt);
    if(awLoginObj)awLoginObj.modal('hide');//caso a janela esteja aberta, remove-a para gerar novamente
    
    $.ajax({
        type:'GET',dataType:'html',url:admin_vars.url+'/login-ajax?prefix='+admin_vars.prefix+'&account_id='+admin_vars.account_id,
        success:function(data){
            opt_modal.title=false;
            opt_modal.html=function(oHtml){
                    oHtml.html(data);
                    oHtml.closest('.modal-content');
                    var f=oHtml.find('#form-login-ajax');
                    awFormAjax(f,{
                        dataFields:{account_login:admin_vars.account_login},
                        onSuccess:function(r){
                            //console.log('R',r)
                            admin_vars.token = r.csrf_token;//atualiza o token
                            awLoginObj.modal('hide');
                            awLoginObj=null;
                            dashboard_vars.auth_jx_reload_count=0;
                            callfnc(callback);
                        },
                        onError:function(r){
                            //console.log('E1',r)
                            if(r.reload){//indica que a página de login deve ser recarregada
                                dashboard_vars.auth_jx_reload_count++;
                                if(dashboard_vars.auth_jx_reload_count>5){
                                    alert('Muitas tentativas de login\nAguarde alguns minutos e tente novamente.');
                                    window.location.reload();
                                    return false;
                                }else{
                                    alert(r.msg);
                                }
                                awLogin(callback);
                            }else{
                                dashboard_vars.auth_jx_reload_count=0;
                                f.find('#senha').val('').focus();
                            }
                        }
                    });
                    setTimeout(function(){ awFormFocus(f);},500);
                };
            awLoginObj=awModal(opt_modal);
        },
        error:function(xhr, ajaxOptions, thrownError){
            //console.log('E2',r)
            opt_modal.title='Sessão desconectada';
            opt_modal.html=function(o){
                                        o.html('<br><p><a href="#" class="btn btn-primary">Fazer login novamente</a></p><br>')
                                            .find('a').on('click',function(){
                                                awLoginObj.modal('hide');
                                                awLoginObj=null;
                                                awLogin(callback);
                                                return false;
                                            });
                                    };
            awLoginObj=awModal(opt_modal);
        }
    });
};

//Faz a atualização do token
function awTokenUpd(){
    awAjax({type:'GET',url:admin_vars.url+'/get/token',success(r){//obs: esta rota GET é autenticada
        admin_vars.token = r.token;
        $('[name=_token]').val(r.token);
    }});
};

//Cookies
function awCookieAdd(name,value,days) {
    var expires = "";
    if(days){
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    };
    if(typeof(value)=='object')value=JSON.stringify(value);
    document.cookie = name + "=" + value + expires + "; path=/";
};
function awCookieRead(name,def=null){
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        //if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        if(c.indexOf(nameEQ) == 0){
            c = c.substring(nameEQ.length,c.length);
            try{c=jQuery.parseJSON(c);}catch(e){}
            return c=='' || c==undefined || c==null?def:c;
        }
    };
    return def;
};
function awCookieDel(name){awCookieAdd(name,"",-1);};
//Função padrão de cookie para json - leitura e gravação
var awJCookieVar={};
function awJCookie(name,v,days){//json v //se v===false, então limpa o cookie
    var j=awJCookieVar[name]=awCookieRead(name,{});
    if(typeof(v)=='object'){
        j = $.extend(true,j,v);
        awCookieAdd(name,j,days);
    }else if(v===false){
        awCookieDel(name);
        j=null;
    };
    return j;
};


const aw_components={}; //sintaxe {name:{js:[],css[],cb:function(opcional)}} //obs: nenhum componente implementado até o momento
/**
 * Importa um componente
 */
function awComponent(name,cb){
    let a=aw_components[name];
    if(!a)return;
    
    let c=0;
    const _fcb=function(){
        c++;
        if(c==a.length)if(cb)cb.call();
    };
    
    for(let n in a){
        if(a[n].css)awImport('css' ,a[n].css, _fcb);
        if(a[n].js)awImport('js', a[n].js, _fcb);
    };
};

//Carrega dinamicamente arquivos js/css
//@param type - css,js
//@param file - string,array    //ex url: 'http://...' ou '/...'
//@param cb - function after all files loadead
//Obs: o carregamento js não é executado de forma assíncrona
var aw_import_script=[];
function awImport(type,file,cb){
    if(aw_import_script.length==0){//first function call
        //update aw_import_script with all files
        $('script[src]').each(function(){
            aw_import_script.push($(this).attr('src'));
        });
        $('link[rel=stylesheet]').each(function(){
            aw_import_script.push($(this).attr('href'));
        });
    };
    
    const _fcb=function(){
        if(c==files.length)if(cb)cb.call();
    };
    
    let files = Array.isArray(file) ? file : [file];
    let c=0,js=[];
    for(let f in files){
        file=files[f];
        if(file.substring(0,1)=='/')file = admin_vars.url + file;
        if($.inArray(file,aw_import_script)!==-1){
            c++;_fcb();
        }else{
            //get absolute path
            var link = document.createElement("a");
            link.href = file
            file = link.href;
            if(type=='js'){
                js.push($.getScript(file));
            }else if(type=='css'){
                $("<link/>", {rel: "stylesheet",type: "text/css",href:file}).appendTo("head");
                c++;_fcb();
            }
        };
    };
    if(js.length>0){
        js.push(
            $.Deferred(function(deferred){ $( deferred.resolve ); })
        );
        $.when.apply($, js).done(function(){
            c+=js.length-1;
            _fcb();
        });;
    };
};



//executa a função somente após existir
function fnExists(fnstr,c){
    if(!$.isArray(fnstr))fnstr=[fnstr];
    var x=0;
    for(var i in fnstr){
        if(eval("typeof "+fnstr[i]+" !== 'undefined'"))x++;
    }
    if(x==fnstr.length){c.call();}else{setTimeout(function(){ fnExists(fnstr,c);},10);};
};
function fnExistsReady(fnstr,c){$().ready(function(){fnExists(fnstr,c);});};





/*
 Padronizações de plugins
 */
$.ajaxSetup({
    type:'POST',
    dataType:'json',
    processData: false,
    beforeSend: function(xhr, settings) {
        if(!settings.crossDomain){
            xhr.setRequestHeader('X-CSRF-Token',admin_vars.token);
        };
        xhr.setRequestHeader('x-scripts-loaded', dashboard_vars.scripts_loaded.join(','));//adiciona os scripts já carregados
    },
    error:function (xhr, ajaxOptions, thrownError){
        awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
    }
    //,statusCode:{419: function(){...}}
});


/**
 * Função para substituir o $.ajax()
 * Esta função valida todo retorno json para verificar status de usuário logado, etc
*/
function awAjax(opt){
    var _success = opt.success;
    var _error = opt.error;
    
    if(opt.type=='GET' && opt.data){//mescla os parâmetros querystring em data
        let d=qsToJSON(opt.url);
        opt.url=d._baseurl;
        delete d._baseurl;
        opt.data=$.extend(true,d,opt.data);
    };
        
    opt.success = function(data,textStatus,jqXHR){
        //console.log(data)
        var auth=true;
        if((opt.dataType=='json' && data.authenticated===false) || data=='[authenticated=false]'){
            auth=false;
        };
        if(!auth){
            awLogin(function(){
                awAjax(opt);//callback ajax
            });
        }else{
            if(_success)_success.call(null,data,textStatus,jqXHR);
        }
    };
    opt.error = function(xhr, ajaxOptions, thrownError){
        if(_error)_error.call(null,xhr, ajaxOptions, thrownError);
    };
    $.ajax(opt);
};



/*
Adiciona um comando global js a ser executado
Executado na inicialização da página (comando jQuery().ready().
Utiliza a var global aw_script_js;
*/
var aw_script_js = [];
function awAddScriptJs(cmd){
    aw_script_js.push(cmd);
};
$().ready(function(){
    jqDocScroll=$([document.documentElement, document.body]);
    
    //inicializar os scripts automáticos
    for(var j in aw_script_js){
        if(typeof(aw_script_js[j])=='function')aw_script_js[j].call();
    };
    
    //manter token ativo
    setInterval(awTokenUpd,1000*60*4);//4min
});



//verifica e exibe o alerta ao sair da janela
window.onbeforeunload=function(e){
    //console.log(dashboardGet('is_uploading'), dashboardGet('is_saving'), dashboard_vars.exit_page)
    if(dashboardGet('is_uploading') || dashboardGet('is_saving')){
        return 'Tem certeza que deseja sair';
    };
    if(dashboard_vars.exit_page)return 'Tem certeza que deseja sair da página? Você pode perder alguns dados caso não estejam salvos.';
};


$(window).on({
    //bloqueia o comportamente padrão de arrastar arquivos
    'dragover drop':function(e){
        var o=$(e.target);
        var n=o.prop('nodeName');
        if(!(n=='INPUT' || n=='TEXTAREA')){e.preventDefault();return false;}
    },
    'keydown':function(e){
        var k=e.keyCode;
        //bloqueia o reload/exit da página nos casos: key F5, Ctrl+R, Alt+left
        if(e.altKey && (k==37 || k==39)){
            dashboard_vars.exit_page=true;
            setTimeout(function(){ dashboard_vars.exit_page=false; },100);
        };
        
        if(e.ctrlKey & k==83){//ctrl+s
            e.preventDefault();
            for(var i in dashboard_vars.forms){
                dashboard_vars.forms[i].filter('[data-save-key=on]').trigger('save');
            }
        };
    }
});



//bloqueia o reload da página nos casos: key F5 e Ctrl+R
$(document).on("keydown",function(e){
    if ((e.which || e.keyCode) == 116 || ((e.ctrlKey || e.altKey) && (e.which || e.keyCode)) == 82) e.preventDefault();
});
