@section('toolbar-header')
@can('admin')
<a href="#" class="btn btn-primary" id="bt-add-process">Adicionar Apólices para Verificação</a>
@endcan
@endsection

@include('admin.process_robot.seguradora_data._template-list')

<script>
    var bt=$('#bt-add-process').on('click',function(){
        awModal({
            title:'Adicionando apólices para verificação nos sites das seguradoras',
            html:function(oHtml){
                oHtml.html(
                    '<p>IDs do processo Cadastro de Apólices</p>'+
                    '<div class="form-group" id="form-group-account_id">'+
                        '<div class="control-div"><textarea class="form-control" name="ids" placeholder="Separar ids por virgula" rows="5"></textarea><span class="help-block"></span></div>'+
                    '</div>'+
                    '<div class="form-group" id="form-group-account_id">'+
                        '<div class="control-div" data-type="checkbox"><label class="nostrong"><input type="checkbox" name="overwrite" value="s"><span class="checkmark "></span> Verificar novamente caso já processados</label><span class="help-block"></span></div>'+
                    '</div>'+
                    ''
                );
                setTimeout(function(){ oHtml.find('textarea').focus(); },500);
            },
            btClose:false,
            btSave:'Salvar',
            form:'method="POST" action="{{route($prefix.'.app.post',['process_seguradora_data','add_process_check'])}}" accept-charset="UTF-8" ',
            form_opt:{
                dataFields:{process_prod:'apolice_check'},
                onSuccess:function(r){
                    if(r.success){
                        window.location.reload();
                    }
                },
                fields_log:false
            }
        });
        return false;
    });
</script>
