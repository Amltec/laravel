@php

$taxsList = $thisClass->taxsList('edit',$post);
if(!$taxsList)return;
foreach($taxsList as $term_id => $tax_opt){
    if(!$tax_opt['taxs_sel'])continue;

    //echo '<div id="term_'.$term_id.'_wrap" class="term_'.$term_id.'_wrap term_wrap" data-term_id="'.$term_id.'" data-term_title="'.$tax_opt['term']->term_title.'">';
        if($is_title??false)echo '<strong class="row-taxonomy-title">'.$tax_opt['term']->term_title.'</strong>';
        echo view('templates.ui.tag_item_list',[
                'taxRel'=>$tax_opt['taxs'],
                'term_id'=>$term_id,
                'tax_filter'=>$tax_opt['taxs_sel'],
            ]);
    //echo '</div>';
}

@endphp