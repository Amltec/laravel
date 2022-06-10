@extends('templates.admin.index')

@php
    $menu_side_ex=Request::input('menu_side');       //valores, list, menu, text
    
    $files = \App\Services\FilesService::getList();
    $list = view('templates.ui.auto_list',[
                'data'=>$files['files'],
                'columns'=>[
                    'id'=>'ID',
                    'file_title'=>['Título','value'=>function($val,$reg){
                        return '<strong>'. $reg->file_title .'</strong><br><small>'. $reg->created_at .'</small>';
                    }],
                ],
                'options'=>['footer'=>false],
            ]);
    
     $tree=view('templates.components.menuv',[
                'pos_caret'=>'right',
                //'select'=>'b7',
                'sub'=>[
                        'h1'=>array('title'=>'Cabeçalho 1','header'=>true),
                        'a'=>'Opção A',
                        'b'=>'Opção B',
                        'sub1'=>array('title'=>'Sub Menus 1x','pos_caret'=>true,//,'collapse'=>false
                            'sub'=>[//,'link'=>'http://www.google.com.br'
                                'a1'=>'Opção Sub A',
                                'b2'=>'Opção Sub B',
                                'c3'=>['title'=>'Opção Sub C','sub'=>[
                                    'a6'=>'Opção Sub A6',
                                    'b7'=>'Opção Sub B7',
                                ]],
                            ]
                         ),
                        'c'=>'Opção C',
                        'd'=>'Opção D',
                    ]
            ]);
    
    $text = 'Menu lateral adicional';
    
    
    //*** exemplo de lista na lateral ***
    if($menu_side_ex=='list'){
       $html_list=$list;
       
    }elseif($menu_side_ex=='menu'){
        $html_list=$tree;
        
    }elseif($menu_side_ex=='mixed'){
        $html_list='<div>'. $tree . $list . $tree .'</div>';
        
    }else{
        $html_list=$text;
    }
    
    
@endphp



@section('title_bar')
    Barra de Título Página Padrão
@endsection


@section('title')
    Página Padrão
@endsection


@section('description')
    Descrição adicional
@endsection


@section('toolbar-header')
    Local para adicionar botões de barra de ferramentas
@endsection


@section('menu_side')
   {!! $html_list !!}
@endsection


@section('content-view')
<p class="strong">Exibindo na lateral: 
    <a href="?name=template-default-menu2&menu_side=text" class="margin-r-5">Texto</a> 
    <a href="?name=template-default-menu2&menu_side=list" class="margin-r-5">Lista</a> 
    <a href="?name=template-default-menu2&menu_side=menu" class="margin-r-5">Menu</a>
    <a href="?name=template-default-menu2&menu_side=mixed" class="margin-r-5">Diversos</a>
</p>

<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Minime id quidem, inquam, alienum, multumque ad ea, quae quaerimus, explicatio tua ista profecerit. Nec enim, omnes avaritias si aeque avaritias esse dixerimus, sequetur ut etiam aequas esse dicamus. <a href="http://loripsum.net/" target="_blank">Velut ego nunc moveor.</a> Eamne rationem igitur sequere, qua tecum ipse et cum tuis utare, profiteri et in medium proferre non audeas? Non quaero, quid dicat, sed quid convenienter possit rationi et sententiae suae dicere. Duo Reges: constructio interrete. </p>

<p>Mihi quidem Antiochum, quem audis, satis belle videris attendere. Hoc autem tempore, etsi multa in omni parte Athenarum sunt in ipsis locis indicia summorum virorum, tamen ego illa moveor exhedra. Hac videlicet ratione, quod ea, quae externa sunt, iis tuemur officiis, quae oriuntur a suo cuiusque genere virtutis. Quod dicit Epicurus etiam de voluptate, quae minime sint voluptates, eas obscurari saepe et obrui. Si enim, ut mihi quidem videtur, non explet bona naturae voluptas, iure praetermissa est; Ac tamen, ne cui loco non videatur esse responsum, pauca etiam nunc dicam ad reliquam orationem tuam. </p>

<p>Quid ergo attinet gloriose loqui, nisi constanter loquare? Quod si ita est, sequitur id ipsum, quod te velle video, omnes semper beatos esse sapientes. Fieri, inquam, Triari, nullo pacto potest, ut non dicas, quid non probes eius, a quo dissentias. Quod, inquit, quamquam voluptatibus quibusdam est saepe iucundius, tamen expetitur propter voluptatem. Sunt enim quasi prima elementa naturae, quibus ubertas orationis adhiberi vix potest, nec equidem eam cogito consectari. Atque ut reliqui fures earum rerum, quas ceperunt, signa commutant, sic illi, ut sententiis nostris pro suis uterentur, nomina tamquam rerum notas mutaverunt. Si enim ita est, vide ne facinus facias, cum mori suadeas. Ita fit cum gravior, tum etiam splendidior oratio. Neque enim disputari sine reprehensione nec cum iracundia aut pertinacia recte disputari potest. Expectoque quid ad id, quod quaerebam, respondeas. Epicurei num desistunt de isdem, de quibus et ab Epicuro scriptum est et ab antiquis, ad arbitrium suum scribere? Hoc ne statuam quidem dicturam pater aiebat, si loqui posset. Age nunc isti doceant, vel tu potius quis enim ista melius? </p>

<p>Alterum significari idem, ut si diceretur, officia media omnia aut pleraque servantem vivere. In omni enim arte vel studio vel quavis scientia vel in ipsa virtute optimum quidque rarissimum est. Aliter enim explicari, quod quaeritur, non potest. Cum ageremus, inquit, vitae beatum et eundem supremum diem, scribebamus haec. Tum Lucius: Mihi vero ista valde probata sunt, quod item fratri puto. Nam prius a se poterit quisque discedere quam appetitum earum rerum, quae sibi conducant, amittere. Res enim se praeclare habebat, et quidem in utraque parte. Sin eam, quam Hieronymus, ne fecisset idem, ut voluptatem illam Aristippi in prima commendatione poneret. Ergo illi intellegunt quid Epicurus dicat, ego non intellego? Levatio igitur vitiorum magna fit in iis, qui habent ad virtutem progressionis aliquantum. Ipse enim Metrodorus, paene alter Epicurus, beatum esse describit his fere verbis: cum corpus bene constitutum sit et sit exploratum ita futurum. Et quidem, inquit, vehementer errat; <a href="http://loripsum.net/" target="_blank">Huius ego nunc auctoritatem sequens idem faciam.</a> Nam quibus rebus efficiuntur voluptates, eae non sunt in potestate sapientis. </p>

@endsection



