@php
if(!isset($taxRel))return;

$term_id = isset($term_id) && !is_array($term_id) ? [$term_id] : false;
$tax_filter = $tax_filter??[];
$r_taxs=[];
if($taxRel){
    
    
    //dd($taxRel);
    foreach($taxRel as $tx){
        if($tx instanceof \App\Models\Tax){
            $taxcl = $tx;
        }else{
            $taxcl = $tx->tax;
        }
        if(!isset($taxcl->term_id))continue;
        if($tax_filter && !in_array($tx->id,$tax_filter))continue;
        
        if($term_id && !in_array($taxcl->term_id,$term_id))continue;//filtra a lista pelo respectivo termo
        if(!isset($r_taxs[$taxcl->term_id]))$r_taxs[$taxcl->term_id]='';
        $r_taxs[$taxcl->term_id].=view('templates.components.tag_item',['opt'=>
            array_merge([
                'title'=>$taxcl->tax_title,
                'color'=>$taxcl->tax_options['color'],
                'icon'=>$taxcl->tax_options['icon'],
                'term_id'=>$taxcl->term_id,
                'tax_id'=>$tx->tax_id,
            ],$opt??[])
        ]);
    }
    
    if($term_id){
        foreach($term_id as $term_id_x){
            echo '<div class="row-taxonomy-items '. ($class??'') .'" data-term_id="'.$term_id_x.'" id="'. ($id??'') .'" '. ($attr??'') .'>'. ($r_taxs[$term_id_x]??'') .'</div>';
        }
    }
    
    

    /*
    foreach($taxRel as $tx){
        if($tx instanceof \App\Models\Tax){
            $taxcl = $tx;
        }else{
            $taxcl = $tx->tax;
        }
        if(!isset($taxcl->term_id))continue;
        if($tax_filter && !in_array($tx->id,$tax_filter))continue;
        
        if($term_id && !in_array($taxcl->term_id,$term_id))continue;//filtra a lista pelo respectivo termo
        if(!isset($r_taxs[$taxcl->term_id]))$r_taxs[$taxcl->term_id]='';
        $r_taxs[$taxcl->term_id].=view('templates.components.tag_item',['opt'=>
            array_merge([
                'title'=>$taxcl->tax_title,
                'color'=>$taxcl->tax_options['color'],
                'icon'=>$taxcl->tax_options['icon'],
                'term_id'=>$taxcl->term_id,
                'tax_id'=>$tx->tax_id,
            ],$opt??[])
        ]);
    }
    
    if($term_id){
        foreach($term_id as $term_id_x){
            echo '<div class="row-taxonomy-items '. ($class??'') .'" data-term_id="'.$term_id_x.'" id="'. ($id??'') .'" '. ($attr??'') .'>'. ($r_taxs[$term_id_x]??'') .'</div>';
        }
    }
    */
}
@endphp