/*
Funções do arquivo:
    awFormFocus()
    awFieldFocus()
    awFncFields()
    awFncTableType()
    awFncBlocDinamic()
    awFormAjax()
    awUploadModal()
    awUploadProgress()
    awUploadZone()
    awUploadFieldBox()
    awFilterBar()
    $.fn.maskAlphanumeric()
Requer:


Coreções de plugins
    1) fix allowClear $.fn.select

*/


//Dispara o focus automaticamente no primeiro campo visível do form ou o campo com a classe .first-field-focus
function awFormFocus(form_sel){
    if(!form_sel)return;
    var o=$(typeof(form_sel)=='string' ? $(form_sel) : form_sel);
    var delay = o.parent().hasClass('modal-body')?dashboard_vars.modal_delay:0;//ajuste o tempo do focus de acordo o form estar dentro de janela modal
    setTimeout(function(){ 
        var a=o.find('.first-field-focus:visible:eq(0)');
        if(a.length==0)a=o.find('input,select,textarea').filter(':visible:not([tabindex=-1],[disabled],[readonly]):eq(0)');
        if(a.length>0)a.focus();
    },delay);
}
//Dispara o focus automaticmanete no campo do form considerando o scroll da página. Ex de uso: no retorno do validate do form
function awFieldFocus(oField){
    if(oField.length==0)return;
    var marginTop=10;
    jqDocScroll.animate({scrollTop:oField.offset().top - dashboard_vars.navbar_height - marginTop},'fast',function(){ oField.focus() });
}


//Aplica as funções padrões por tipos de campos (auto_fields)
function awFncFields(fields){//(jquery) fields
    if(fields){
        var oForm=fields.eq(0).closest('form');
        //eventos padrão do formulário
        fields.on({
            'change':function(){
                $(this).trigger('msg',false);
            },
            'msg':function(e,msg){//se msg==false: clear
               e.stopPropagation();
               var a=$(this);
               var o=a.closest('.form-group');
               if(msg){
                   o.addClass('has-error');
                   o.find('.help-block').html(msg);
               }else{
                   o.removeClass('has-error');
                   o.find('.help-block').html('');
               }
           }
        });
        fields.each(function(){
            var o=$(this);

            //máscara nos campos
            var m=o.attr('data-mask');
            if(m=='currency'){
                o.inputmask('currency', {prefix:'',rightAlign:false,radixPoint:',',groupSeparator:'.'});
            }else if(m=='decimal'){
                o.inputmask('decimal', {rightAlign:false,radixPoint:','});
            }else if(m){
                o.inputmask(m);
            };


            //ajustes personalizados nos campos
            switch(o.attr('data-type')) {
                case 'select2':
                  var opt=o.data('data-select2');
                  if(!opt){opt=o.attr('data-select2');if(opt)$.parseJSON(opt);};
                  if(!opt)opt={};
                  var ajax_url=o.attr('data-ajax-url');
                  if(ajax_url && o.attr('data-ajax-once')){//carrega apenas uma única vez
                        o.on('select2:open.aw001',function(){
                            var o=$(this);
                            setTimeout(function(){ o.select2({"data":[{id:'0',text:'Aguarde...'}]}); },0);
                            awAjax({
                                  url: ajax_url,
                                  type:'GET',
                                  dataType:'json',
                                  success: function(r){
                                      o.empty();
                                      o.select2({"data":r}).select2('open');
                                  }
                            });
                            o.off('select2:open.aw001');
                        }).removeAttr('data-ajax-url');
                  }else{//lista carregada ao digitar
                        if(ajax_url && !opt.ajax){
                              opt.ajax={
                                  url: ajax_url,
                                  dataType: 'json',
                                  type: 'GET',
                                  processData: true,
                                  data:function(term){
                                      return {q:term.term};//envia sempre o parâmetro 'q' como campo de busca
                                  },
                                  processResults: function (data){
                                      return {results: data};
                                  }
                              };
                        };
                  };
                  o.select2(opt);
                  break;
                //case 'editorcode':break;
                //case 'editor':break;
                case 'date':
                  if(o.attr('data-picker')=='on')o.datepicker({autoclose:true,format:'dd/mm/yyyy'});
                  break;
                /*case 'date':
                  if(o.attr('data-picker')=='on')o.datepicker({autoclose:true,format:'dd/mm/yyyy'});
                  break;*/
                case 'color':
                  if(o.attr('data-picker')=='on')o.parent().colorpicker();
                  break;
                case 'search':
                  o.on('keyup focusout focusin',function(){ $(this).prev().css('display', this.value==''?'none':'block'); });
                  break;
                case 'colorbox':
                  var os=o.parent().find('.aw-colorbox-item').on('click',function(){var a=$(this);os.removeClass('select');a.addClass('select');a.parent().find('input').val(a.attr('data-value'));});
                  break;
                case 'time':
                  if(o.attr('data-picker')=='on')o.timepicker({showInputs:false,disableMousewheel:true,showMeridian:false,defaultTime:false,showSeconds:true})
                        .on('focusout',function(e){//hide timepick on lost focus
                            var g=$(e.relatedTarget);
                            var n=g.prop('nodeName');
                            if(n=='INPUT' || n=='SELECT' || n=='TEXTAREA')$(this).timepicker('hideWidget');
                        });

                  break;
                case 'daterange':
                  if(o.attr('data-picker')=='on')o.daterangepicker({autoUpdateInput:false,locale:{format:'DD/MM/YYYY'}},
                            function(start, end, label) {
                                setTimeout(function(){ o.val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY')); },0);
                            });
                  break;
            };
            
            //carrega as funções externas de cada campo
            let f=o.attr('data-plugin-js')+'AWLoad';
            if(typeof window[f] === 'function'){
                  window[f]({form:oForm,field:o});
            };
            
            if(o.prop('nodeName')=='TEXTAREA' &&  o.attr('auto-height')){//somente para campos textarea
                var maxHeight=fnNumber(o.attr('auto-height'));
                var minHeight=o.outerHeight();//inicial height
                if(!minHeight)minHeight=75;
                o.css({resize:'none'});
                var _fInputAH=function(a) {
                    o.css({overflow:'hidden'});
                    a.css({height:0});
                    var s=a.get(0).scrollHeight;
                    if(s<minHeight)s=minHeight;
                    var mh=(maxHeight<=minHeight?$(window).height()-140:maxHeight);
                    if(s>mh){s=mh;o.css({overflow:'auto'});};
                    a.css({height:s});
                };
                o.on('input',function(){_fInputAH($(this));});
                if(o.val()!='')_fInputAH(o);
            }
            
        });
    }
}


/*  Aplica em campos de formulário o padrão para cada tipo de tabela.
    Valores para tableName: contacts, addresses, phones, etc (nomes das tabelas padrões)
*/
function awFncTableType(tableName,obj){
    var oFields=obj.find(':input');
    switch(tableName) {
    case 'contacts':
        oFields.filter('[data-name=tipo]').on('change.stop',function(e){
            if(this.value=='f'){
                oFields.filter('[name$="--j"]').closest('.form-group').hide();
                oFields.filter('[name$="--f"]').closest('.form-group').show();
            }else if(this.value=='j'){
                oFields.filter('[name$="--f"]').closest('.form-group').hide();
                oFields.filter('[name$="--j"]').closest('.form-group').show();
            }
            if(e.namespace=='stop'){
                e.stopPropagation();
            }else{
                if(this.value!='')oFields.not(this).filter(':visible:eq(0)').focus();
            }
        }).trigger('change.stop');
        break;
    
    
    case 'posts':
        oFields.filter('[data-name=title]').on('change.stop',function(e){
            if(e.namespace=='stop')e.stopPropagation();
            var o=oFields.filter('[data-name=name]');
            if($.trim(o.val())=='')o.val(convertToSlug(this.value));
        });
        break;
    }
}


/*  Aplica nas funções de bloco dinâmico (blocos que pode serem criados/duplicados dinamicamente).
    Obs: procura automaticamente por nomes de campos com {N} para substituir pelo respectivo índice do bloco. Altera os atributo: name, id, value, div label.
    @param cb - executado sempre ao adicionar ou remover um novo
*/
function awFncBlocDinamic(oBlock,opts,cb){
    var opt = $.extend(true,{
        mode:'block',       //indica o modo visual de controle do bloco. Valores: block, inline
        add:true,           //true (default) ativa, false desativa.
        remove:true,        //exclusão do bloco. Obs se ==false então desativa (default true). Valores para array:
                            //(string) ajax - rota para exclusão (opcional)
                            //(boolean) confirm - confirmação de exclusão (opcional)
        remove_last:false,  //se true somente a última linha poderá ser removida
        block_title:null,   //titulo do bloco (opcional),
    },opts);
    
    //conta os blocos
    var fCountBlk=function(){return oBlock.find('>.form-block-group').length + oDivBase.find('>.form-block-group').length;};
    
    //adiciona um novo bloco
    var fAddBlock=function(){
        iNBlock++;
        var blk=oDefaultBlock.clone().appendTo(oDivBase).attr('data-id','new').attr('data-i',iNBlock);
        if(opt.mode=='block')blk.addClass('form-block-group-sep');
        blk.find('.j-numeral').html(iNBlock);
        
        //altera o nome dos campos e divs
        var oInputs = blk.find(':input').each(function(){
            var o=$(this);
            if(o.attr('name'))o.attr('name',o.attr('name').replace('{N}','{'+iNBlock+'}'));
            if(o.attr('id'))o.attr('id',o.attr('id').replace('{N}','{'+iNBlock+'}'));
            if($.trim(o.val())!='')o.val(o.val().replace('{N}','{'+iNBlock+'}'));
        });
        if(opt.block_title)oInputs.attr('data-block-title',opt.block_title);
        awFncFields(oInputs);
        blk.find('label').each(function(){
            var o=$(this);
            if(o.html()!='')o.html(o.html().replace('{N}','{'+iNBlock+'}'));
        });
        awFormFocus(blk);
        
        //botão remover
        blk.find('.j-remove').on('click',fRemoveBlock);
        
        //atualiza o total de blocks
        oBlock.find('[data-type="autofield_count"]').val(iNBlock);
        
        if(cb)cb.call();
    };
    
    
    //remove um block
    var fRemoveBlock=function(){
        if(opt.remove===false)return;
        var t=true;
        if(opt.remove.confirm){
            t=confirm('Deseja excluir?');
        }
        if(t){
            var th=$(this);
            var _fRem=function(id_removed){
                var oTrGr = th.closest('.form-block-group').fadeOut('fast',function(){ $(this).remove(); if(cb)cb.call(); });
                //temporáriamente desativado//iNBlock--;
                
                //atualiza o total de blocks
                oBlock.find('[data-type="autofield_count"]').val(iNBlock);
                //atualiza com os ids dos blocks removidos
                var o=oBlock.find('[data-type="autofield_remove_ids"]');
                o.val(trimx(o.val() +','+ (id_removed?id_removed:oTrGr.attr('data-i')), ','));
                
                
            }
            if(opt.remove.ajax){
                var thisBlock=th.closest('.form-block-group');
                var data=$.extend(true,{_token:admin_vars.token,id:thisBlock.attr('data-id')},awSerializeToJson(thisBlock.find(':input')));
                //console.log(data);return;
                
                awAjax({
                        url: opt.remove.ajax,
                        type:'POST',
                        data:data,
                        dataType:'json',
                        success: function(r){
                            if(r.success){
                                _fRem(data.id);
                            }else{
                                alert(r.msg);
                                //awModal({title:'Erro ao excluir',html:r.msg,msg_type:'danger','btSave':false});
                            }
                        },
                        error:function (xhr, ajaxOptions, thrownError){
                            //awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger','btSave':false});
                            alert('Erro interno de servidor');
                        }
                });
            }else{
                _fRem();
            }
        }
    };
    
    var oDefaultBlock=oBlock.find('.form-block-group-def:eq(0)').removeClass('form-block-group-def hidden').detach();//remove apenas do dom, mas fica armazendo para ser usado depois
    if(opt.remove===false)oDefaultBlock.find('.j-remove').remove();
    var oDivBase=$('<div class="form-block-dinamic"></div>').appendTo(oBlock);
    var iNBlock=fCountBlk();
    oBlock.find('[data-type="autofield_count"]').val(iNBlock);
    
    if(opt.add){
        $('<button type="button" class="btn btn-link"><span class="fa fa-plus-circle"></span> Adicionar</button>')
            .insertAfter(oBlock)
            .on('click',fAddBlock);
    };
    
    var tmp;
    //*** eventos nos blocos já carregados ***
    var oBlockLoad=oBlock.find('>.form-block-group');
    //remove
    tmp=oBlockLoad.find('.j-remove');if(opt.remove!==false){tmp.on('click',fRemoveBlock);}else{tmp.remove();};
    
    //adiciona apenas o cabeçalho dos campos
    if(opt.mode=='inline'){
        oBlockLoad.eq(0).clone().attr('class','form-block-head clearfix').prependTo(oBlock).find('.control-div,button,.j-numeral').remove();
    }
    
    
    //*** atualiza os campos com os valores atuais ***
    
}


/*  Converte o submit form para ajax - params:
        form_sel = string seletor jquery form OR object jquery form
        opts = json options. Se undefined, captura do atributo 'data-opt', caso undefined, captura de o dado 'data-opt'.
    Return $(this);
*/
function awFormAjax(form_sel,optsx){
    var frmObjs = $(
        typeof(form_sel)=='string' ? 
            $(form_sel) 
        : 
            (form_sel ? form_sel : 'form[form-auto-init=on]')    //obs: o atributo form-auto-init=on indica que irá ser processado automaticamente ao carregar a tela
        );
    if(frmObjs.length==0)return frmObjs;
    
return frmObjs.each(function(){
    /**
     * Comandos adicionais
     *      classe .last-focus  no objeto que ao perder o focus, retornará ao focus do primeiro objeto do form
     */
    var oForm=$(this).attr('form-auto-init','ok');
    dashboard_vars.forms.push(oForm);
    
    var opts=optsx;
    if(!opts){
        opts = oForm.attr('data-opt');
        if(opts)opts=$.parseJSON(opts);
        if(!opts)opts = oForm.data('data-opt');
    };
    
    var opt = $.extend(true,{
        submit:'[type=submit]', //seletor button submit (precisa estar dentro do form)
        msg:'.alert-form',//seletor msg  (precisa estar dentro do form)
        btAutoUpload:null,//(string) seletor ou (object) objeto do  botão upload. Deve ser definido apenas para botões formulários de um único botão de upload.
        submitAutoUpload:true,//(boolean) se true irá submeter o form log após o change do botão de upload (cso definido opt.btAutoUpload), 
        fileszone:false,  //true|array - se array, deve conter os parâmetros como os da função awUploadZone() para criação dinâmica do campo. Caso não informado, irá clonar os parâmetros necessários do botão de upload (parâmetro btAutoUpload). Obs: o parâmetro 'name' não é considerado neste caso. Ex: ['accept'=>'*']
        urlSuccess:'',    //se definido, redireciona após salvar os dados
        focus:false,      //se true, seta o focus automaticamente no campo ao carregar o form
        uploadProgress:true,   //utilizado para quando este form for de upload de arquivos - ativa o recurso awUploadProgress()
        dataFields:null,       /*(json) campos adicionais a serem mesclados com os inputs já existentes dentro do form no momento do submit. Sintaxe: {field:value,...}
                                    Valores padrões considerando um post para a url da rota FilesController->Post()
                                        data-opt - (json) sintaxe: {field:value,...}. Campos já programados: folder, private, area_name, area_id, thumbnails (boolean)
                                */
        fields_log:true,  //se false, desativa os campos _original_data e _modified_data para gravação automática dos dados do log. Default true.
        clearPageShow:false, //se true irá limpar o form sempre que o formulário for carregado na página (ex: back button browser)
        saveKey:false,      //se true, irá permitir que o form seja salvo com ctrl+s
        //Obs: Todos os eventos abaixo, também disparam o comando trigger('event'), //para isto deve estar defindo o parâmetro 'btAutoUpload'
        //São aceitos eventos no formato string (precisa ter o prefixo '@'). Para ser usados com a função callfnc()
        //  onBefore:function(opt){},    //antes de iniciar cada upload
        //                                      Recebe o parâmetro json:  files[...] (+ file__id)
        //  onSuccess:function(opt){},   //depois que terminar cada upload sem erro
        //                                      Recebe o parâmetro json de FilesController@post (+ file__id)
        //  onError:function(){opt},     //depois que terminar cada upload com erro
        //                                      Recebe o parâmetro json: msg, content, success  (+ file__id)
        //  onProgress:function(opt){}   //progresso de do upload (somente se existir upload) - dispara também o evento onProgress. 
        //                                      Recebe o parâmetro json: bytes_loaded,bytes_total,perc,files_count,files_current,files_ok,files_error (+ file__id)
        //  onStart:function(){opt}      //antes de inciar todo o upload (válido para uplaod múltiplo)
        //                                      Recebe o parâmetro json: todo o opt.files do campo input
        //  onComplete:function(opt){}   //depois de completar todo o upload (válido para uplaod múltiplo)
        //                                      Recebe o parâmetro json: files_count,files_ok,files_error, status (R - all success, E (um ou mais) - error)
        //  onUploadProgress:null,         //callback sempre que inicializado o awUploadProgress(). Recebe como 1º parâmetro o objeto json da função awUploadProgress().
    },opts);
    
    //console.log(opt)
    //console.log(callfnc(opt.onProgress))
    var oBtSubmit = oForm.find(opt.submit);
    if(oBtSubmit.length==0)oBtSubmit=null;
    if(oBtSubmit && !oBtSubmit.attr('data-loading-text'))oBtSubmit.attr('data-loading-text',"<i class='fa fa-circle-o-notch fa-spin'></i> "+ (oBtSubmit.text()!=''?'Processando':'') );
    if(oBtSubmit)oBtSubmit.on('reset',function(e){
                    e.stopPropagation();
                    setTimeout(function(){ oBtSubmit.button('reset'); },500);
                });
    
    if(opt.saveKey)oForm.attr('data-save-key','on');
    
    //*** formulário de upload ***
    var isFormUpload = oForm.attr('enctype')=='multipart/form-data';
    var oBtAutoUpload,isMultipleUpload=false; 
    if(isFormUpload && opt.btAutoUpload){
        //botão que dispara automaticamente o submit do form ao selecionar o arquivo
        oBtAutoUpload=typeof(opt.btAutoUpload)=='string' ? oForm.find(opt.btAutoUpload) : opt.btAutoUpload;
        if(oBtAutoUpload.length==0 && opt.fileszone){//não existe o botão de upload, mas existe apenas o filezone
            //neste caso cria um botão temporário dinamicamente
            oBtAutoUpload=$('<input type="file" style="display:none;" name="'+opt.fileszone.name+'"'+ (opt.fileszone.multiple===true?' multiple="multiple"':'') + (opt.fileszone.accept?' accept="'+opt.fileszone.accept+'"':'') +' >').prependTo(oForm);
        };
        
        if(oBtAutoUpload.length==0 && !opt.fileszone){
            //lógica: como foi informado um botão de upload que não existe e não tem parâmetro filezone, então usa o setTimeout para esperar o botão de upload ser criado dinamicamente pela função awUploadZone() abaixo
            setTimeout(function(){ oBtAutoUpload = oForm.find(opt.btAutoUpload); },0);
        };
        
        setTimeout(function(){//aqui é esperado com o timeout para o caso do botão ser criadom com o timeout acima
                oBtAutoUpload.on({
                    'loading':function(e,isLoading){//seta o loading do botão //se isLoading==false, então reseta
                        e.stopPropagation();
                        var o=$(this).parent();
                        if(oBtAutoUpload.attr('data-type')=='zone'){
                            oBtAutoUpload.trigger('set',{//obs: disparando o trigger diretamente no botão, também irá disparar no container ascendente
                                status: (isLoading===false?'R':'L')
                            });
                            if(isLoading===false)oForm[0].reset();//reseta o form para resetar este campo de upload (pois é ele que dispara o submit)

                        }else{//button    
                            if(isLoading===false){
                                o.removeClass('disabled');
                                var ic;
                                if(o.data('def')){
                                    o.find('span').html(o.data('def').text);
                                    ic=o.data('def').icon;
                                    o.find('i').attr('class','fa fa-check');
                                    o.removeData('def');
                                }
                                oForm[0].reset();//reseta o form para resetar este campo de upload (pois é ele que dispara o submit)

                                //effect check to def icon
                                setTimeout(function(){
                                    o.find('i').fadeOut('slow',function(){
                                        $(this).fadeIn('fast');
                                        if(ic)$(this).attr('class',ic);
                                    });
                                },500);
                            }else{
                                o.addClass('disabled');
                                o.data('def',{text:o.find('span').html(),icon:o.find('i').attr('class')});
                                o.find('span').html('Enviando');
                                o.find('i').attr('class','fa fa-circle-o-notch fa-spin');
                            }
                        }
                    },
                    'change':function(){
                        if(opt.submitAutoUpload && this.value!=''){
                            oForm.trigger('submit');
                        }
                    }
                });
        },0);
        //se true, indica que é upload múltiplo
        if(oBtAutoUpload.prop('multiple'))isMultipleUpload=true;
    };
    
    
    
    //*** zona de upload (adiciona o campo dinamicamente) *** 
    if(opt.fileszone){
        if(opt.fileszone===true)opt.fileszone={};
        if(!opt.fileszone.name)opt.fileszone.name='filezone_'+Math.random().toString(36).substr(2, 9);//gera um nove temporário
        if(oBtAutoUpload){//clona os parâmetros
            if(!opt.fileszone.multiple)opt.fileszone.multiple=oBtAutoUpload.prop('multiple');
            if(!opt.fileszone.accept)opt.fileszone.accept=oBtAutoUpload.attr('accept');
        };
        
        var oUplZnContainer=(opt.fileszone.maximize?$(opt.fileszone.maximize===true?document.body:opt.fileszone.maximize):oForm).on({
            'dragenter':function(ev){
                var e = ev.originalEvent;
                e.dataTransfer.dropEffect = 'copy';
                var file = e.dataTransfer.items[0];
                var type = file.type.slice(0, file.type.indexOf('/'));
                if(type!='text')oUploadZone.show();
            },
            'drop':function(e){
                if(oBtAutoUpload){
                    setTimeout(function(){
                        if(e.target.files && e.target.files.length>0){
                            oUploadZone.hide();
                            if(e.target!==oBtAutoUpload[0]){//verifica se não é o mesmo botão, foi neste caso não precisa enviar novamente
                                oBtAutoUpload[0].files = e.target.files;//atribui os arquivos selecionados somente
                                oForm.trigger('submit');
                            }
                        }
                    },1);
                }
            },
        });
        oUplZnContainer.addClass('relative').append('<div ui-uploadzone="on"></div>');
        var oUploadZone=awUploadZone(oUplZnContainer,opt.fileszone).addClass('connect_form')
            .on({
                'dragleave':function(){oUploadZone.hide();},
            });
    };
   
   
    
    var oMsg = oForm.find(opt.msg);
    if(oMsg.length==0)oMsg=$('<div><div>');//cria um objeto vazio
    oMsg.on('msg',function(e,msg){//json msg >= (json|string)msg, content(string), success(boolean) | string json=>'' (limpa os dados)
        e.stopPropagation();
        oMsg.removeClass('alert-success alert-danger').addClass('alert-'+(msg.success?'success':'danger'));
        
        var oLinkMore=oMsg.find('.alert-link-content').hide();
        if(msg==''){
            oMsg.hide().find('.alert-msg,.alert-content').html('');
        }else{
            var oFirstField;
            if(typeof(msg.msg)=='object'){//sintaxe: {fieldname:[msg1,..],...}
                var label;//,m=[];
                for(var field in msg.msg){
                    //console.log(field,msg.msg);return;
                    //oGroup=oForm.find('[name='+field+']').closest('.form-group').addClass('has-error');
                    let tmp=oForm.find('[name|="'+field+'"]');
                    if(tmp.length==0)tmp=oForm.find('[name|="'+field+'[]"]:visible:eq(0)');
                    label = tmp.closest('.form-group').find('label:eq(0)').text();//select |= para considerar sempre o início do campo igual (ex: por causa do sufixo '--f|j' dos contatos)
                    //m.push(label+': '+msg.msg[field].join(', '));
                    //m.push(msg.msg[field].join(', '));
                    tmp.trigger('msg',
                        String(msg.msg[field])
                            .replace(new RegExp("{\{"+field+"\}\}",'gi'),label)
                            .replace(new RegExp("{\{"+ (field).replace(/\_/,' ') +"\}\}",'gi'),label) //ajuste para o caractere '_' que vem com espaço no lugar
                    );
                };
                //oMsg.hide();
                oMsg.hide().fadeIn('fast').find('.alert-msg').html('Campos inválidos. Verifique e tente novamente.');
                
            }else{
                //console.log('***',msg)
                oMsg.hide().fadeIn('fast').find('.alert-msg').html(msg.msg);
                if(msg.content)oLinkMore.show();
                oMsg.find('.alert-content').hide().html($.trim(msg.content));
            };
            //scroll e focus no primeiro campo
            var o=oForm.find('.has-error:visible :input:visible:not([tabindex=-1],[disabled],[readonly]):eq(0)');
            awFieldFocus(o);//focus no campo
        }
    });
    
    var oInputs=oForm.find(':input');
    
    //aplica as funções de cada tipo de campo
    awFncFields(oInputs);
    
    //aplica as funções de blocos tipos de tabelas
    oForm.find('[form-table-type]').each(function(){
        awFncTableType($(this).attr('form-table-type'),$(this));
    });
    
    //aplica as funções de bloco dinâmicos
    var _fBlkDinamicLast=function(blk){blk.find('.form-block-group').removeClass('form-block-last').last().addClass('form-block-last');};
    oForm.find('[form-block-dinamic]').each(function(){
        var a=$(this);
        var dOpt=$.parseJSON(a.attr('form-block-dinamic'));
        if(dOpt.block_title)a.find(':input').attr('data-block-title',dOpt.block_title);
        if(dOpt.remove_last)_fBlkDinamicLast(a);
        awFncBlocDinamic(a, dOpt ,function(){
                oInputs=oForm.find(':input')//atualiza a lista de campos inputs
                if(dOpt.remove_last)_fBlkDinamicLast(a);
            });
    });
    
    //cria um json de dados para envio do log
    //return string serialized
     var _fCreateDataLog=function(is_original_data){//(boolean)is_original_data
        var r={},v1,v2,n,a,label;
        oInputs.each(function(){
            var o=$(this);
            var a=o.attr('name');
            if($.inArray(a,['_modified_data','_original_data','_token','_method'])!==-1)return;//campos ignorados
            if(a && !r[a.replace('[]','')]){
                label=o.attr('data-label');
                var t=o.attr('type');if(!t)t='';t=t.toLowerCase();
                v1=o.val();v2=v1;
                if(o.prop('nodeName')=='SELECT'){
                    v2=o.find(':selected').text();
                }else if(t=='radio' || t=='checkbox'){//obs: neste caso não será repetido no loop campos com o mesmo nome
                    o=oInputs.filter(function(){ return $(this).attr('name')==a; });
                    label=o.attr('data-label');
                    o=o.filter(':checked');
                    //usa o map() para o caso de ter + de 1 valor
                    v2=o.map(function(){ return $.trim($(this).parent().text().replace(/\|/g,'')); }).get().join('|');
                    v1=o.map(function(){ return $(this).val(); }).get().join('|');
                }else if(t=='password'){
                    v1=v2=(v1=='' || is_original_data===true?'':'******');
                };
                if(o.attr('data-block-title'))label=o.attr('data-block-title') +' '+ o.closest('.form-block-group').attr('data-i') +' > '+label;
                r[a.replace('[]','')] = {value:v1??'',text:$.trim(v2),disabled:o.prop('disabled')?'_true_':'_false_',label:label,type:t};
            };
        });
        return $.param(r);
    };
    //cria o campo de dados originais e modificados
    if(opt.fields_log)$('<input type="hidden" name="_original_data"><input type="hidden" name="_modified_data">').val(_fCreateDataLog()).prependTo(oForm);
    
    //atualiza o campo de variáveis de dados originais do form com os dados atualmente modificados
    oForm
    .on('save',function(e){
        e.preventDefault();
        oBtSubmit.click();
    })
    .on('focusout',function(e){
        var o=$(e.target);
        if(o.hasClass('last-focus'))awFormFocus(oForm);
    })
    .on('submit.aw',function(e){
        e.preventDefault();
        
        //atualiza o campo de dados modificados
        oForm.find('[name=_modified_data]').val(_fCreateDataLog());
        
        //executa o ajax
        var _fAjaxExec=function(_fopt){
            if(!_fopt)_fopt={};
            
            
            var param={
                dataType:'json',
                type:(String(oForm.attr('method')).toUpperCase()=='POST' || isFormUpload?'POST':'GET'),
                url:oForm.attr('action'),
                data:formdata,
                processData: false,
                cache: false,
                contentType: false,
                success: function(r) {//return success,msg,content,data(ok)
                    //console.log(r,'*',opt);
                    dashboardSet({is_saving:false});
                    if(files[post_i])r.file__id=files[post_i].file__id;
                    if(r.success){
                        if(r.msg)oMsg.trigger('msg',{msg:r.msg,success:true});
                        //falta programar aqui o retorno pra o caso r.alert==true - que força a exibiçã da mensagem - caso FilesService@storeFile
                        if(opt.urlSuccess!='')window.location=opt.urlSuccess.replace(':id',r.data && r.data.id?r.data.id:r.id);//redireciona para a rota após o cadastro
                        r._form=oForm;
                        if(opt.onSuccess)callfnc(opt.onSuccess,r);
                        oForm.trigger('onSuccess',r);
                        if(_fopt.onComplete)_fopt.onComplete.call(null,'R');
                        
                        //atualiza o campo de dados originais com os dados modificados
                        if(opt.fields_log)oForm.find('[name=_original_data]').val(_fCreateDataLog(true));
                        
                    }else{
                        oMsg.trigger('msg',{msg:(r.msg ? r.msg : 'Erro interno de servidor'),content:(r.success==false?r.content:JSON.stringify(r)),success:false});
                        r._form=oForm;
                        if(opt.onError)callfnc(opt.onError,r);
                        oForm.trigger('onError',r);
                        if(_fopt.onComplete)_fopt.onComplete.call(null,'E');
                    };
                },
                error: function (r, status, error) {
                    dashboardSet({is_saving:false});
                    var r2={msg:'Erro interno de servidor',content:r.responseText,success:false,file__id:(files[post_i]?files[post_i].file__id:null)};
                    oMsg.trigger('msg',r2);
                    r2._form=oForm;
                    if(opt.onError)callfnc(opt.onError,r2);
                    oForm.trigger('onError',r2);
                    if(_fopt.onComplete)_fopt.onComplete.call(null,'E');
                }
            };


            if(isFormUpload && files[post_i]){
                param.xhr=function(){
                    var myXhr = $.ajaxSettings.xhr();
                    if (myXhr.upload) { // Avalia se tem suporte a propriedade upload
                        myXhr.upload.addEventListener('progress', function (e){
                            //faz alguma coisa durante o progresso do upload
                            //console.log('enviando arquivo')
                            if(e.lengthComputable){
                                var v={
                                    bytes_loaded:e.loaded,
                                    bytes_total:e.total,
                                    files_count:files_count,
                                    files_current:post_i,
                                    files_ok:post_f,
                                    files_error:post_e,
                                    file__id:files[post_i].file__id,//current file id
                                    perc:Math.round(e.loaded/e.total * 10000) / 100,
                                };
                                if(opt.onProgress)callfnc(opt.onProgress,v);
                                oForm.trigger('onProgress',v);
                                //console.log("progress",v);
                            }
                        }, false);
                    }
                    return myXhr;
                }
                //console.log(param)
            };
            
            //atualiza o parâmetro
            if(_fopt.param)param = _fopt.param.call(null,param);
            
            //verificações antes de processar o ajax
            var v=files[post_i],is_ret=true;
            if(opt.onBefore)if(callfnc(opt.onBefore,{files:v,oForm:oForm})===false)is_ret=false;
            if(oForm.triggerHandler('onBefore',{files:v,oForm:oForm})===false)is_ret=false;
            if(!is_ret){
                if(oBtSubmit)oBtSubmit.trigger('reset');
                if(oBtAutoUpload)oBtAutoUpload.trigger('loading',false);
                if(oBtAutoUpload && opt.uploadProgress){
                    awUploadProgress({remove_files:v.file__id});
                    dashboardSet({is_uploading:false});
                    awUploadProgress({close:true});
                }
                return false;
            };
            //console.log(post_i,'***');return;
            
            
            dashboardSet({is_saving:true});
            
            //processa o ajax
            awAjax(param);
        };
        
        
        var files=(oBtAutoUpload ? oBtAutoUpload[0].files : [{
            name:'',size:0,type:''  //retorna com os valores vazios pois não foi informado o botão de upload
        }]);
        var files_count = (files ? files.length : 1);
        var post_i=0,post_f=0,post_e=0;
        
        
        //adicionar um ID na var files
        var var_files_clone=[];
        for(var ix in files){
            if(files[ix].name && files[ix].size){
                files[ix].file__id = Math.random().toString(36).substr(2, 9);
                var_files_clone.push($.extend(true, {}, files[ix]));
            }
        };
        
        var formdata = new FormData(this);
        if(opt.dataFields){//campos adicionais para mesclagem de dados
            var tmp,xx;
            for(var ix in opt.dataFields){
                tmp=opt.dataFields[ix];
                if(ix.substring(ix.length-2,ix.length)=='[]' && $.isArray(tmp)){
                    //lógica: adiciona em formdata para a sintaxe: ex {'ids[]'=[1,2,...]}
                    for(xx in tmp){
                        formdata.append(ix,tmp[xx]);
                    };
                    continue;
                }else if(typeof(tmp)=='object'){
                    tmp=JSON.stringify(tmp);
                };
                formdata.append(ix,tmp);
            }
        };
        
        if(isFormUpload)dashboardSet({is_uploading:true});
        if(opt.onStart)if(callfnc(opt.onStart,{files:var_files_clone})===false)return false;
        if(oForm.triggerHandler('onStart',{files:var_files_clone})===false)return false;
        
        if(oBtSubmit)oBtSubmit.button('loading');
        if(oBtAutoUpload)oBtAutoUpload.trigger('loading');
        
        oInputs.trigger('msg',false);
        oMsg.trigger('msg','');
        
        var _fThLoop=function(){
            var j={
                onComplete:function(status){//finalizou o ajax //status=R,E
                    //segue para o próximo loop
                    post_i++;
                    if(status=='R'){post_f++;}else{post_e++;}
                    if(files && typeof(files[post_i-1])=='object' && post_i<files_count){
                        _fThLoop();
                    };
                    
                    if(post_i==files_count || files_count==0){
                        var v={
                            files_count:files_count,
                            files_ok:post_f,
                            files_error:post_e,
                            status:(files_count==post_f?'R':'E')
                        };
                        dashboardSet({is_uploading:false});
                        
                        setTimeout(function(){//gera um pequeno delay executar o callback onComplete
                            if(opt.onComplete)callfnc(opt.onComplete,v);
                            oForm.trigger('onComplete',v);

                            if(oBtSubmit)oBtSubmit.trigger('reset');
                            if(oBtAutoUpload)oBtAutoUpload.trigger('loading',false);
                        },1);
                    }else if(files_count==0){
                        if(oBtSubmit)oBtSubmit.trigger('reset');
                        if(oBtAutoUpload)oBtAutoUpload.trigger('loading',false);
                    }
                }
            };
            
            if(isMultipleUpload){//é upload múltiplo
                j.param=function(param){
                    //modifica o parâmetro param.data
                    var name=oBtAutoUpload.attr('name');
                    param.data.delete(name);
                    param.data.append(name, files[post_i]);
                    return param;
                };
                _fAjaxExec(j);
                
            }else{//apenas 1 arquivo
                _fAjaxExec(j);
            }
        };
        _fThLoop();//start
        
    });
    
    if(opt.focus)awFormFocus(oForm);
    
    
    //form upload - ativa o recurso de awUploadProgress()
    if(oBtAutoUpload && opt.uploadProgress){
        oForm.on({
            'onStart':function(e,opt2){
                var arr=[];
                for(var i in opt2.files){
                    arr.push({id:opt2.files[i].file__id,title:opt2.files[i].name});
                };
                var o=awUploadProgress({add_files:arr});
                callfnc(opt.onUploadProgress,o);
            },
            'onProgress':function(e,opt2){
                awUploadProgress({set_files:{id:opt2.file__id,status:'L',perc:opt2.perc}});
            },
            'onSuccess onError':function(e,opt2){
                awUploadProgress({set_files:{id:opt2.file__id,status: (e.type=='onSuccess'?'R':'E'),msg:opt2.msg,data:opt2.content}});
            },
            'onComplete':function(){
                
            }
        });
    };
    
    if(opt.clearPageShow){//limpa o form sempre que o formulário for carregado na página (ex: back button browser)
        window.addEventListener("pageshow",function(){oForm[0].reset();});
    }
});
};



/** Janela de Upload Modal
 * Parâmetros json opt:
 */
var awObjUploadModal=null;
function awUploadModal(opts){
    var opt = $.extend(true,{
        accept:'image/*',
        route:'',
        multiple:false,
        id:null,                     //id do modal
        class:null,                  //class modal
        title:'Selecione o arquivo',
        descr:null,
        bt_label:'auto',             //label do botão
        bt_icon:'fa-upload',         //ícone do botão
        bt_class:'btn-info',
        hideInfoUpl:false,
        form:{},                     //os mesmos parâmetros de awFormAjax() (ex: onComplete:function(){},...)
    },opts);
    
    if(awObjUploadModal){
        awObjUploadModal.modal('show');
    }else{
        opt.form.fields_log=false;//desativa o log
        var frmDataOpt = JSON.stringify(opt.form);
        awObjUploadModal=awModal({
            form:'method="POST" action="'+ opt.route +'" accept-charset="UTF-8" enctype="multipart/form-data" data-opt=\''+frmDataOpt+'\'',
            id:opt.id,
            hideBg:false,
            esc:false,
            title:opt.title,
            html:function(oHtml){
                var r=(opt.descr?'<p>'+opt.descr+'</p>':'')+
                    (opt.hideInfoUpl?'':'<p>Limite de tamanho do arquivo: '+ admin_vars.max_size +'.</p>')+
                        
                        '<input type="hidden" name="action" value="upload">'+
                        '<div class="btn '+opt.bt_class+' btn-upload">'+
                            (opt.bt_icon ? '<i class="fa '+ opt.bt_icon +'" style="margin-right:5px;"></i> ':'')+
                            '<span>'+ (opt.bt_label=='auto'? (opt.multiple?'Escolher arquivos':'Escolher arquivo') :opt.bt_label) +'</span>'+
                            '<input type="file" class="form-control" name="file" accept="'+ opt.accept +'"'+ (opt.multiple?' multiple="multiple"':'') +'></div>'+
                        '</div>';
                        
                        
                        //estrutura html parcial das mensagens do form (o mesma view.templates.components.alert-structure.blade)
                        //'<br><br><div class="alert alert-danger alert-form hiddenx"><p class="alert-msg"></p></div>';
                    ;
                oHtml.html(r);
            },
            form_opt:$.extend(true,opt.form,{
                    btAutoUpload:'[name=file]',
                    onComplete:function(opt2){
                                    if(opt2.status=='R'){
                                        setTimeout(function(){ awObjUploadModal.modal('hide'); },1000); 
                                    };//else{//error
                                 }
                    }),
        }).on('hidden.bs.modal',function(){
            awObjUploadModal=null;//para gerar uma nova janela
        });
    }
};


/**
 * Janela de progresso de upload. 
 * Parâmetros json opt para disparar ações deste objeto.
 * Return object;
 * Obs: este objeto é único (não será gerado em duplicidade)
 * Obs2: Para cada item li:
 *      data    - data-error    ($(...).data('data-error')  //{title,data}
 *      attr    - data-status (load, wait, success, error), data-title
*/
var awObjUploadProgress=null;
function awUploadProgress(opts){
    var opt = $.extend(true,{
        add_files:null,     //adiciona itens -(array de objects json com upload iniciado. Valores json: {id,title, progress, status (A,L,E,R)}
        remove_files:null,  //remove items - string|array de ids
        set_files:null,     //altera o status dos items - json sintaxe: {id, status, msg, perc} - valores (todos opcionais):
                            //      status - L (load), A (wait), R (success), E (error)
                            //      perc   - (int 0-100) percentual de carregamento (somente para status=L)
                            //      msg    - mensagem (somente para status=E)
                            //      data    - conteúdo completo retornado (para erro)
        collapse:null,      //collapse ou não o box - boolean|auto
        close:null,         //fecha o box - boolean
        //show,             //não precisa ser setado, basta chamar awUploadProgress() sem informar parâmetros
    },opts);
    
    var obj;
    if(!awObjUploadProgress){
        obj = {
            base:$('<div class="box box-solid ui-upload-progress-box" style="z-index:'+dashboard_vars.modal_zindex +';">'+
                    '<div class="box-header bg-navy with-border">'+
                        '<h3 class="box-title"><span class="box-header-x1">Enviando arquivos</span> <span style="margin-left:5px;font-size:0.7em;" class="box-header-count"></span></h3>'+
                        '<div class="box-tools pull-right">'+
                            '<i class="btn-box-tool no-events box-header-status">'+
                                '<span class="hiddenx ok fa fa-check text-green"></span>'+
                                '<span class="hiddenx error fa fa-warning text-red"></span>'+
                                '<span class="wait fa fa-circle-o-notch"></span>'+
                                '<span class="hiddenx load"><span class="fa fa-circle-o-notch fa-spin"></span></span>'+
                            '</i>'+
                            '<button type="button" class="btn btn-box-tool fa fa-minus" data-action="collapse"></button>'+
                            '<button type="button" class="btn btn-box-tool fa fa-close" data-action="close"></button>'+
                        '</div>'+
                    '</div>'+
                    '<div class="box-body no-padding scrollmin">'+
                        '<ul class="nav nav-stacked"></ul>'+
                    '</div>'+
                '</div>').appendTo(document.body)
                .on('click',function(e){
                    var ac=$(e.target).attr('data-action');
                    if(ac=='collapse'){
                        awUploadProgress({collapse:'auto'});
                        
                    }else if(ac=='close'){
                        awUploadProgress({close:true});
                    }
                })
        };
        obj.baseIn = obj.base.find('.box-body ul:eq(0)');
        obj.headerX1 = obj.base.find('.box-header-x1');
        obj.headerCount = obj.base.find('.box-header-count');
        obj.headerStatus = obj.base.find('.box-header-status > span');
        awObjUploadProgress = obj;
    }else{
        obj = awObjUploadProgress;
        obj.base.show().css('z-index',dashboard_vars.modal_zindex);
    };
    if($(document.body).hasClass('modal-filemanager')){obj.base.addClass('pos-left')}else{obj.base.removeClass('pos-left')};
    
    var o,f;
    
    var _fCount=function(){
        var os=obj.baseIn.find('>li');
        var total = os.length;
        var n_ok = os.filter('[data-status=ok]').length;
        var n_load = os.filter('[data-status=load]').length;
        var n_error = os.filter('[data-status=error]').length;
        obj.headerCount.html( (n_ok+n_load) +'/'+ total );
        obj.headerX1.html(n_error>0 || n_ok==0? 'Erro ao enviar' : 'Enviados com sucesso');
        
        var ic='load';
        if(total==n_ok){
            ic='ok';
        }else if(total==(n_ok + n_error) && n_error>0 ){
            ic='error';
        }else if(total!=n_load && n_load==0){
            ic='wait';
        };
        //console.log(total,n_ok,n_error,n_load)
        obj.headerStatus.hide().filter('.'+ic).show();
    }
    
    if(opt.add_files){
        for(var i in opt.add_files){
            f=opt.add_files[i];
            o=$('<li data-id="'+f.id+'" title="Aguardando" data-status="wait">'+
                '<a class="clearfix">'+
                    '<span class="pull-left">'+
                        '<span class="ui-upload-title margin-r-10">'+f.title+'</span>'+
                        '<span class="ui-upload-title-msg"></span>'+
                    '</span>'+
                    '<span class="pull-right">'+
                        '<span class="hiddenx ui-upload-icon ok"><span class="fa fa-check text-green"></span></span>'+
                        '<span class="hiddenx ui-upload-icon error"><span class="fa fa-warning text-red"></span></span>'+
                        '<span class="ui-upload-icon wait"><span class="fa fa-circle-o-notch text-muted"></span></span>'+
                        '<span class="hiddenx ui-upload-icon load"><span class="fa fa-circle-o-notch fa-spin"></span></span>'+
                    '</span>'+
                '</a>'+
            '</li>').appendTo(obj.baseIn)
            .on('dblclick',function(e){
                var a=$(this);
                if(a.attr('data-status')=='error'){
                    var d=a.data('data-error');
                    awModal({title:d.title,html:d.data,msg_type:'danger'});
                };
            });
        };
        //scroll end box
        obj.baseIn.parent().animate({scrollTop:obj.baseIn.height()});
    }
    
    if(opt.remove_files){
        if(!$.isArray(opt.remove_files))opt.remove_files=[opt.remove_files];
        for(let i in opt.remove_files){
            f=opt.remove_files[i];
            obj.baseIn.find('>li[data-id='+f+']').fadeOut('fast',function(){ $(this).remove(); });
        }
    }
    
    if(opt.set_files){
        f=opt.set_files;
        let a_ic= {L:'load',A:'wait',R:'ok',E:'error'};
        let a_ms= {L:'Enviando arquivo',A:'Aguardando',R:'Enviado com sucesso',E:'Erro ao enviar'};
        let ic  = f.status ? a_ic[f.status.toUpperCase()]: null;
        let msg = f.status ? a_ms[f.status.toUpperCase()]: null;
        let msg_html='';
        if(msg){
            if(ic=='error' && f.msg){
                if(typeof(f.msg)=='object'){
                   let r=[];
                   for(let z in f.msg){
                       r.push(z +': '+ f.msg[z]);
                   }
                   f.msg=r.join(',');
                };
                msg_html='<span class="ui-upload-title-xl">'+msg+'</span> <span class="ui-upload-title-xv">'+f.msg+'</span>';
                msg+=' - '+f.msg;
            }else if(ic=='load' && f.perc){
                //msg+=' '+(Math.round(f.perc*1000)/10)+'%';
                msg_html='<span class="ui-upload-title-xl">'+msg+'</span> <span class="ui-upload-title-xv">'+f.perc+'%</span>';
                msg+=' '+f.perc+'%';
            }
        };
        o=obj.baseIn.find('>li[data-id='+f.id+']');
        if(o.length>0){
            if(ic){
                o.attr('data-status',ic);
                o.find('.ui-upload-icon').hide().filter('.'+ic).show();
            };
            o.attr('title', msg + (ic=='error'?' (+ duplo-click)':'')).attr('data-title',msg).data('data-error',{title:f.msg,data:f.data});
            let tmp = o.find('.ui-upload-title-msg').html(msg_html);
            if(ic=='error'){tmp.addClass('text-red');}else{tmp.removeClass('text-red');}
        };
    }
    
    if(opt.collapse!=null){
        let a=obj.base.find('[data-action=collapse]');
        let b=obj.baseIn.parent();
        if(opt.collapse===true || (opt.collapse=='auto' && a.hasClass('fa-minus'))){
            a.removeClass('fa-minus').addClass('fa-plus');
            b.slideUp();
        }else{
            a.removeClass('fa-plus').addClass('fa-minus');
            b.slideDown();
        }
    }
    
    if(opt.close!=null){
        if(dashboardGet('is_uploading')){
            alert('Upload em andamento.');
        }else{
            if(opt.close){obj.base.hide();}else{obj.base.show();}
        };
    };
    
    _fCount();
    
    return obj;
}


/*Cria um campo de zona de upload (drag and drop). 
Return object; 
*/
function awUploadZone(oContainer,optsx){
    return (oContainer ? oContainer.find('[ui-uploadzone=on]') : $('[ui-uploadzone=on]')).each(function(){
        var base=$(this).attr('ui-uploadzone','ok');
        if(!base.hasClass('uploadzone')){//prossegue somente se não adicionadou as funções
            var opts=optsx;
            if(!opts){
                var opts = base.attr('data-opt');
                if(opts)opts=$.parseJSON(opts);
                if(!opts)opts = base.data('data-opt');
            };
            var opt = $.extend(true,{
                title:'Clique ou arraste o arquivo aqui',
                name:'file',
                id:null,
                multiple:false,
                accept:'image/*',
                height:null,
                maximize:false, //(boolean|string) se true - adiciona a classe 'maximize' automaticamente para ocupar todo o espaço da tela, se string seletor do container que ficar a zona de upload
                class:'', //classes já programadas: connect_form - conecta ao formulário dentro de um metabox
                //obs: o parâmetro maximize e a classe .connect_form já estão integradas com a função awFormAjax(). Veja nesta função os detalhes de como integrar.
            },opts);
            
            base.removeAttr('data-opt');
            base.addClass('uploadzone transition1 '+(opt.maximize===true?'maximize ':'') + opt.class);
            if(opt.id)base.attr('id',opt.id);
            if(opt.height)base.css('height',opt.height);
            
            
            var r='<input type="file" data-type="zone" name="'+opt.name+'" accept="'+opt.accept+'"'+ (opt.multiple?' multiple="multiple"':'') +'>'+
                '<div class="uploadzone-wrap transition1">'+
                    '<div class="uploadzone-in">'+
                        '<div class="uploadzone-icon fa fa-upload"></div>'+
                        '<div class="uploadzone-title">'+ opt.title +'</div>'+
                        '<div class="uploadzone-msg help-block"></div>'+
                    '</div>'+
                '</div>';
                
            base.html(r).on({
                'dragenter dragleave drop':function(e){
                    var o=base.find('.uploadzone-icon');
                    //efeito ao mover
                    if(e.type=='dragenter'){o.addClass('effect-vert-move');}else{o.removeClass('effect-vert-move');}
                    base.attr('data-ev',e.type);
                },
                //'drag dragend dragstart dragleave dragover dragenter drop':function(e){console.log(e.type)},
                'change':function(){
                    var n=base.find('input')[0].files.length;
                    if(n>0)base.find('.uploadzone-title').html(n+' arquivo'+(n>1?'s':'')+' selecionado'+(n>1?'s':''));
                },
                'set':function(e,opt2){//json opt2: status (''|L,R)
                    e.stopPropagation();
                    var _f1=function(c,t){
                        base.find('.uploadzone-icon').hide().fadeIn().removeClass('fa-upload fa-circle-o-notch fa-spin fa-check').addClass(c);
                        base.find('.uploadzone-title').hide().fadeIn().html(t);
                    };
                    var oInput=base.find('input');
                    
                    oInput.hide();
                    var c='fa-upload';
                    var t=opt.title;
                    if(opt2.status=='L'){
                        c='fa-circle-o-notch fa-spin';
                        t='Aguarde';
                    }else if(opt2.status=='R'){
                        c='fa-check';
                        t='Concluído';
                        setTimeout(function(){
                            _f1('fa-upload',opt.title);
                        },1500);
                        base.attr('data-ev','');
                        oInput.show();
                    };
                    _f1(c,t);
                }
            });
        }
    });
};

/** Campo de upload completo
 * Associado a view uploadbox
 * Ex; Pode ser chamado diretamente no dom após o respectivo html, ex: <script>awUploadFieldBox('myid')</script>
 * Parâmetros json opt:
 */
function awUploadFieldBox(block_id){
    var oGroup=$('#form-group-'+block_id);
    if(oGroup.length==0)return;
    var oInputUrl = oGroup.find('[data-name=url]:eq(0)');
    var oInput = oGroup.find('[data-name=name]:eq(0)');
    
    var options=$.parseJSON(oGroup.attr('data-opt'));
    options.upload = $.extend(true,{
        accept:'*',
        private:false,
        filename_show:'',
    },options.upload);
    
    var oBtUpl=oGroup.find('#'+block_id+'-btupl');
    if(options.filemanager){//gerenciador de arquivos
        oBtUpl.on('click',function(){
            if(options.filemanager===true)options.filemanager={};
            options.filemanager.multiple=false;
            var onCall=null;
            if(options.filemanager.onSelectFile){
                onCall=options.filemanager.onSelectFile;
            };
            options.filemanager.onSelectFile=function(f_opt){//reescreve o padrão onSelectFile
                var n;
                if(options.upload_view){
                    for(var i in f_opt.files){//irá retornar apenas 1 registro
                        n=f_opt.files[i];
                        oInputUrl.val(n.file_url).attr('data-is_image',n.is_image?'s':'n');
                        oInput.val(n.id);
                        var qs='';//?cache='+n.updated_at.replace(/\-/g,'').replace(/\:/g,'').replace(/\s/g,'');
                        fSetView(n.file_thumbnail_all.medium[0]+qs,n.file_thumbnail_all.full[0]+qs);
                    };
                    if(onCall)callfnc(onCall,f_opt);
                };
            };
            awFilemanager(options.filemanager);
        });
    }else{//modal
        oBtUpl.on('click',function(){
            awUploadModal({
                accept:options.upload.accept,
                route:options.route,
                form:{
                    //msg:'',//para não criar o campo mensagem
                    dataFields:{
                        'data-opt':options.upload,
                    },
                    onBefore:function(opt){
                        //console.log('ex x1 - file',opt)
                        if(options.upload_form)callfnc(options.upload_form.onBefore,opt);
                    },
                    onSuccess:function(opt){
                        //console.log('**** file',opt)
                        if(options.upload_view){
                            oInputUrl.val(opt.file_url).attr('data-is_image',opt.is_image?'s':'n');
                            oInput.val(opt.file_url);
                            if(options.upload_db){
                                oInput.val(opt.id);
                                fSetView(opt.url_view+'?cache='+opt.cache);
                            }else{
                                oInput.val(opt.data_serialize);
                                fSetView(opt.file_url+'?cache='+opt.file_lastmodified);
                            }
                        };
                        if(options.upload_form)callfnc(options.upload_form.onSuccess,opt);
                    },
                    onError:function(opt){
                        //console.log('upload err',opt)
                        alert('Erro ao enviar arquivo');
                        if(options.upload_form)callfnc(options.upload_form.onError,opt);
                    },
                }
            });
        });
    };
    
    var oView = oGroup.find('.uploadbox-view:eq(0)')
            .on('click dblclick','[data-action]',function(e){
                e.preventDefault();
                var o=$(e.target);if(o.prop('nodeName')!='A')o=o.closest('a');
                var cmd=o.attr('data-action');
                if(cmd=='remove' && e.type=='click'){
                    var rem=options.upload_view.remove,t=false;
                    if((rem=='e2' || rem=='r2') && confirm('Deseja remover?')==false)return false;
                    if(rem=='e' || rem=='e2' || !options.route){//limpa
                        oInputUrl.val('');
                        oInput.val('');
                        fSetView('');
                    }else if(rem=='r' || rem=='r2'){//remove
                        //console.log(rem,options);
                        awAjax({
                            url: options.route,
                            data: options.upload_db ? {action:'remove',file_id:oInput.val()} : {action:'remove',file:oInputUrl.val(),private:options.upload.private,folder:options.upload.folder,account_off:options.upload.account_off},
                            processData: true,
                            success: function(r){
                                if(r.success){
                                    oInputUrl.val('');
                                    oInput.val('');
                                    fSetView('');
                                }else{
                                    alert(r.msg);
                                }
                            },
                            error:function (xhr, ajaxOptions, thrownError){
                                console.log('uploadbox del err',xhr.responseText)
                                alert('Erro ao excluir');
                            }
                        });
                    };
                }else if(cmd=='open' && (e.type=='dblclick' || e.ctrlKey)){
                    window.open(oView.find('.lnk-img-view').attr('href'));
                }
            });
    var fSetView=function(url,url_full){
        if(options.upload_view){
            var r;
            if(url==''){
                r='<span class="ui-img-box lnk-img-view no" style="width:'+ options.upload_view.width +'px;height:'+ options.upload_view.height +'px;line-height:'+ options.upload_view.height +'px;">Sem arquivo</span>'+
                  (options.filename_show?'<div>'+options.filename_show+'</div>':'');
            }else{
                var is_image = oInputUrl.attr('data-is_image')=='s';
                var filename = url.substring(url.lastIndexOf('/')+1).split('?')[0];
                r='<a href="'+(url_full??url)+'" class="ui-img-box lnk-img-view ui-img-contain" data-action="open" title="Ctrl + Clique ou Duplo Clique para ampliar" style="width:'+ options.upload_view.width +'px;height:'+ options.upload_view.height +'px;">'+
                    (is_image ? '<img src="'+url+'">' : '<span class="lnk-into-fnx1"><span class="fa fa-file"></span> '+filename+'</span>')+
                   '</a>'+
                   (options.filename_show?'<div>'+options.filename_show+'</div>':'')+
                   (options.upload_view.remove?'<a href="#" class="lnk-img-remove" data-action="remove"><span class="fa fa-remove"></span> Remover</a>':'');
            };
            oView.html(r);
        }
    };
    
    //ao carregar
    fSetView(oInputUrl.val());
};


//Comandos automáticos para barra de filtros na lista de dados
function awFilterBar(form_sel,opts){
    var opt = $.extend(true,{
        autoSubmit:false, //se true, fará com que a cada alteração de campo dispare um submit
    },opts);
    
    var oForm=$(typeof(form_sel)=='string' ? $(form_sel) : form_sel);
    oForm.on('submit',function(e){
        var fchk=[];
        var fields=oForm.find(':input').not('[name=_token]').each(function(){
                if($(this).attr('type')=='checkbox' && $(this).prop('checked')==false)fchk.push($(this).attr('name')+'=');//seta vazio
            });
            fields=fields.serialize().replace(/\%2C/g,',');
            if(fchk)fields+= (fields?'&':'') + fchk.join('&');
        //console.log(fields);return
        var url = addQS(admin_vars.url_current+'?'+admin_vars.querystring,fields+'&page=','string');//obs: sempre irá setar page='' para iniciar da primeira página
        window.location=url;
    });
    
    if(opt.autoSubmit){
        oForm.find(':input').on('change',function(){
            oForm.trigger('submit');
        });
    }
}


//campo de mascara somente alphanumericos
jQuery.fn.maskAlphaNumeric=function(){
    $(this).keypress(function(e) {
        var regex = new RegExp("^[0-9a-zA-Z_\.\-]+$");
        var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
        if (regex.test(str)) {
            return true;
        };
        e.preventDefault();
        return false;
    });
};



//***** correções de plugins ****
setTimeout(function(){
    //1) fix allowClear $.fn.select2 (apresentava erro ao remover quando não existia o atributo placeholder)
    if($.fn.select2)$.fn.select2.defaults.set('placeholder','');
},0);