@php
/*
Parâmetros esperados:
    $prefix
    $user
    form_id     - id do formulário
    
Obs: 
    No arquivo blade, é necessário:
        existir um link com a classe j-permiss-login, ex: '<a href="#" class="j-permiss-login">Editar</a>'
    E incluir no final do arquivo no respectivo arquivo blade este temaplte, ex: echo view('_inc.users.login_permiss_edit');
    
*/
@endphp


<script>
(function(){
    var form=$('#{{$form_id}}');
    var valInit = {!! json_encode($user ? ($user->getMetadata("login_permiss")??[]) : []) !!}; 
    var oInput = $('<input type="hidden" name="login_permiss" id="login_permiss">').appendTo('form').val(JSON.stringify(valInit));
    var oModal = null;
    var oIp, oDtActive, oActives;
    
    var f_createModal=function(){
        oModal=awModal({
            title:'Permissões de Acesso',
            form:'method="POST" action="{{route($prefix.".app.get",["aa","bb"])}}"',
            html:function(obj){
                var r=`
                <div class="form-block-wrap clearfix form-horizontal form-login-permiss">
                    <div class="form-group">
                        <label class="control-label col-sm-3">Restrição por IP</label>
                        <div class="control-div col-sm-9">
                          <input type="text" data-type="ip" class="form-control" name="ip" maxlength="500" autocomplete="no" data-label="IP" placeholder="Digite os ips separados por virgula">
                        </div>
                    </div>

                    <div class="form-group">
                          <label class="nostrong" style="margin-left:25px;"><input type="checkbox" name="dt_active" class="j-dt_active" value="s"><span class="checkmark"></span> Restrição por dia e horário</label>
                    </div>

                    <div class="form-group">`;
                        var semanas={1:'Dom',2:'Seg',3:'Ter',4:'Quar',5:'Qui',6:'Sex',7:'Sáb'};
                        r+='<table class="table no-border j-table-date">'+
                            '<tr><th class="col-sm-2 text-right">Semana</th><th class="col-sm-3">Permitir</th><th class="col-sm-7">Horário <small class="nostrong text-muted" style="margin-left:5px;">deixe vazio para bloquear</small></th></tr>';
                        for(var s in semanas){
                            r+= '<tr>'+
                                    '<td class="col-sm-2 text-right"><div class="padd-form-top">'+ semanas[s] +'</div></td>'+
                                    '<td class="col-sm-3"><label class="nostrong"><select name="s'+s+'_active" data-s="'+s+'" class="form-control j-active"><option value="0">Não</option><option value="1">Dia inteiro</option><option value="2">Parte do dia</option></select></td>'+
                                    '<td class="col-sm-7"><input type="text" class="form-control j-time" name="s'+s+'_time" maxlength="25" autocomplete="no" placeholder="hh:mm-hh:mm, hh:mm-hh:mm"></td>'+
                                '</tr>';
                        }
                        r+='</table>';
                r+=`</div>

                </div>
                `;
                obj.html(r);
            },
            //btSave:'Salvar',
            width:700
        })
            .on('shown.bs.modal',function(){ awFormFocus(oModal.find('form')); })
            .on('hide.bs.modal',function(){ updateJson(); });

        oModal
            .on('change ev-init','.j-active',function(e){
                var o=$(this);
                var t = o.val()=='2';//2 = parte do dia
                var a=o.closest('tr').find('.j-time').prop('disabled',!t);
                if(t && e.type!='ev-init')a.focus();
            })
            .on('click ev-init','.j-dt_active',function(e){
                var o=$(this).closest('form').find('.j-table-date');
                if(!$(this).prop('checked')){
                    o.addClass('table-disabled');
                }else{
                    o.removeClass('table-disabled');
                }
            });

        //console.log('valInit',valInit)
        //captura os campos
        oIp = oModal.find('[name="ip"]');
        oDtActive = oModal.find('.j-dt_active');
        oActives = oModal.find('.j-active');

        //seta os valores iniciais
        if(awCount(valInit)>0){
            oIp.val($.trim(valInit.ip));
            oDtActive.prop('checked',valInit.dt_active);
            oActives.each(function(){
                var r = valInit.dt_table[ $(this).attr('data-s') ];
                $(this).val(r.day);
                $(this).closest('tr').find('.j-time').val(r.time);
            });
        };

        //dispara os eventos ao inicializar
        oDtActive.trigger('ev-init');
        oActives.trigger('ev-init');
    };
    
    
    //função que gera o json atualizado dos campos
    var updateJson=function(){
        var tb={};
        oActives.each(function(){
            var a=$(this);
            tb[a.attr('data-s')] = {
                day: a.val(),
                time: a.closest('tr').find('.j-time').val()
            };
        });
        var j={
            ip: oIp.val(),
            dt_active: oDtActive.prop('checked'),
            dt_table:tb
        };
        //console.log(j);
        valInit=j;
        oInput.val(JSON.stringify(j));
    };
    
    form.off('click.permiss-login').on('click.permiss-login','.j-permiss-login',function(e){
        e.preventDefault();
        f_createModal();
        oModal.modal('show');
    });
}());
</script>
<style>
.form-login-permiss .table-disabled{opacity:0.5;pointer-events:none;user-select:none;}
.form-login-permiss .form-control[disabled]{color:#999;}
</style>
