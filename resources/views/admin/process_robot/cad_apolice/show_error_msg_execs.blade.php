@php
/*****
Arquivo utilizado para exibir do padrão de erro que vem do robô local Autoit 'error_msg'.
Até o momento é utilizado apenas para os erros retornados ao localizar proposta (quid0...6)
Ex de sintaxe:
    Para status_code=quid01 a quid06
    error_msg = [
        quiver_id => boolean,
        produto => boolean,
        seguradora => boolean,
        status => boolean,
        vigencia => boolean,
        
        //campos exibidos somente nas situações em que é necessário
        produto_val => string - valor da coluna do produto no quiver,
        apo_num => boolean
        apo_num_val => string - valor da coluna do nº da apólice no quiver
        quiver_id_val => string - valor da coluna do Documento do Quiver
    ]
    //Cada valor boleano acima representa que o respectivo campo foi compatível para localizar o documento.
    //Ex: se produto=false, e o restante =true, então quer dizer que o documento só não foi localizado porque a coluna do produto é incompatível

Variáveis:
    $status_code
    $error_msg

*****/


if(substr($status_code,0,4)=='quid'){//erro ao localizar documento
    $r='Parâmetros de busca: &nbsp; ';
    //dump($error_msg);
    foreach(['seguradora'=>'Seguradora','produto'=>'Produto','vigencia'=>'Vigência','status'=>'Status','apo_num'=>'Nº Apólice','quiver_id'=>'Documento Quiver'] as $f=> $label){
        if(!isset($error_msg[$f]))continue;
        $t=$error_msg[$f];
        if($t=='False')$t=false;
        //dump([$f,$t]);
        
        //if($f=='quiver_id' && !in_array(Auth::user()->user_level,['dev','superadmin']))continue;//obs: até o momento, não precisa exibir o campo quiver_id para o administrador (motivo: ele não está aconstumado a visualizar esta informação, e só irá confundí-lo)
        if($t==false){
            if($f=='produto' && isset($error_msg['produto_val']))$label.=' ('. $error_msg['produto_val'] .')';
            if($f=='apo_num' && isset($error_msg['apo_num_val']))$label.=' ('. $error_msg['apo_num_val'] .')';
            if($f=='quiver_id' && isset($error_msg['quiver_id_val']))$label.=' ('. $error_msg['quiver_id_val'] .')';
        }
        $r.= '<span class="x'. ($t?'text-green':'text-red') .' margin-r-10"><span class="fa fa-'. ($t?'check':'close') .' margin-r-5"></span> '. $label .'</span>';
    }
    echo $r;
    //dump('***',$status_code,$error_msg);

}


@endphp