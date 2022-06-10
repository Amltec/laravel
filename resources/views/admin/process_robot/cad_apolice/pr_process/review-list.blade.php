@php
    $prefix = Config::adminPrefix();
@endphp


@section('title')
Revisão no Quiver do Cadastro de Apólice
@endsection


@section('toolbar-header')
    <a href="#" class="btn btn-primary" id="bt-add-process">Adicionar Apólice para Revisão</a>
@endsection


@include('admin.process_robot.cad_apolice.pr_process._template-list')

<script>
    var bt=$('#bt-add-process').on('click',function(){
        awModal({
            title:'Adicionando apólices para revisão',
            html:function(oHtml){
                oHtml.html(
                    '<p>IDs do processo Cadastro de Apólices</p>'+
                    '<div class="form-group" id="form-group-account_id">'+
                        '<div class="control-div"><textarea class="form-control" name="ids" placeholder="Separar ids por virgula" rows="5"></textarea><span class="help-block"></span></div>'+
                    '</div>'+
                    '<div class="form-group" id="form-group-account_id">'+
                        '<div class="control-div" data-type="checkbox"><label class="nostrong"><input type="checkbox" name="add_again" value="s"><span class="checkmark "></span> Adicionar novamente caso já exista</label><span class="help-block"></span></div>'+
                    '</div>'+
                    ''
                );
                setTimeout(function(){ oHtml.find('textarea').focus(); },500);
            },
            btClose:false,
            btSave:'Salvar',
            form:'method="POST" action="{{route($prefix.'.app.post',['process_cad_apolice_pr','add_process'])}}" accept-charset="UTF-8" ',
            form_opt:{
                dataFields:{process:'{{$pr_process}}'},
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