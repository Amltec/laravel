@php
/*
    Menu lateral com a lista das taxs
    Variáveis esperadas:
        postFolder
        thisClass
        termsList
        title_folder
        taxs_select
*/
use App\Services\TaxsService;




echo '<div class="postview-menuleft col-xs-12 col-sm-2">';

$i=0;
foreach($termsList as $term){
    $taxs = TaxsService::getTaxListTree(
        $term->id,
        [],
        function($reg) use($prefix,$postFolder,$thisClass){ 
            if($postFolder){
                return route($prefix .'.app.gets',[$thisClass->post_type,'view-list',$postFolder->folder_name.'/?tx_id='.$reg->id]); 
            }else{
                return route($prefix .'.app.gets',[$thisClass->post_type,'view-list','?tx_id='.$reg->id]); 
            }
        } 
    );
    
    echo '<h4>'. ($i==0 ? $title_folder : $term->term_title) .'</h4>';
    echo view('templates.components.tree',[
        'sub'=>$taxs,
        'class_menu'=>'tree-condensed',
        'icon_def'=>'fa-angle-right',
        'sub_icon_def'=>'fa-caret-right',
        'show_caret'=>false,
        'select'=>$taxs_select,
        'link_force'=>false,
    ]);
    
    $i++;
}
    
echo '</div>';


@endphp