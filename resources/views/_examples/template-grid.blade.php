@extends('templates.admin.index-grid',[
    'dashboard'=>[
        //'navbar'=>false,
    ],
    'grid_sizes'=>[
        'right'=>100,
    ],
    //'grid_border'=>false,
])
@php
    
    $var='<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Minime id quidem, inquam, alienum, multumque ad ea, quae quaerimus, explicatio tua ista profecerit. Nec enim, omnes avaritias si aeque avaritias esse dixerimus, sequetur ut etiam aequas esse dicamus. <a href="http://loripsum.net/" target="_blank">Velut ego nunc moveor.</a> Eamne rationem igitur sequere, qua tecum ipse et cum tuis utare, profiteri et in medium proferre non audeas? Non quaero, quid dicat, sed quid convenienter possit rationi et sententiae suae dicere. Duo Reges: constructio interrete. </p>

<p>Mihi quidem Antiochum, quem audis, satis belle videris attendere. Hoc autem tempore, etsi multa in omni parte Athenarum sunt in ipsis locis indicia summorum virorum, tamen ego illa moveor exhedra. Hac videlicet ratione, quod ea, quae externa sunt, iis tuemur officiis, quae oriuntur a suo cuiusque genere virtutis. Quod dicit Epicurus etiam de voluptate, quae minime sint voluptates, eas obscurari saepe et obrui. Si enim, ut mihi quidem videtur, non explet bona naturae voluptas, iure praetermissa est; Ac tamen, ne cui loco non videatur esse responsum, pauca etiam nunc dicam ad reliquam orationem tuam. </p>

<p>Quid ergo attinet gloriose loqui, nisi constanter loquare? Quod si ita est, sequitur id ipsum, quod te velle video, omnes semper beatos esse sapientes. Fieri, inquam, Triari, nullo pacto potest, ut non dicas, quid non probes eius, a quo dissentias. Quod, inquit, quamquam voluptatibus quibusdam est saepe iucundius, tamen expetitur propter voluptatem. Sunt enim quasi prima elementa naturae, quibus ubertas orationis adhiberi vix potest, nec equidem eam cogito consectari. Atque ut reliqui fures earum rerum, quas ceperunt, signa commutant, sic illi, ut sententiis nostris pro suis uterentur, nomina tamquam rerum notas mutaverunt. Si enim ita est, vide ne facinus facias, cum mori suadeas. Ita fit cum gravior, tum etiam splendidior oratio. Neque enim disputari sine reprehensione nec cum iracundia aut pertinacia recte disputari potest. Expectoque quid ad id, quod quaerebam, respondeas. Epicurei num desistunt de isdem, de quibus et ab Epicuro scriptum est et ab antiquis, ad arbitrium suum scribere? Hoc ne statuam quidem dicturam pater aiebat, si loqui posset. Age nunc isti doceant, vel tu potius quis enim ista melius? </p>

<p>Alterum significari idem, ut si diceretur, officia media omnia aut pleraque servantem vivere. In omni enim arte vel studio vel quavis scientia vel in ipsa virtute optimum quidque rarissimum est. Aliter enim explicari, quod quaeritur, non potest. Cum ageremus, inquit, vitae beatum et eundem supremum diem, scribebamus haec. Tum Lucius: Mihi vero ista valde probata sunt, quod item fratri puto. Nam prius a se poterit quisque discedere quam appetitum earum rerum, quae sibi conducant, amittere. Res enim se praeclare habebat, et quidem in utraque parte. Sin eam, quam Hieronymus, ne fecisset idem, ut voluptatem illam Aristippi in prima commendatione poneret. Ergo illi intellegunt quid Epicurus dicat, ego non intellego? Levatio igitur vitiorum magna fit in iis, qui habent ad virtutem progressionis aliquantum. Ipse enim Metrodorus, paene alter Epicurus, beatum esse describit his fere verbis: cum corpus bene constitutum sit et sit exploratum ita futurum. Et quidem, inquit, vehementer errat; <a href="http://loripsum.net/" target="_blank">Huius ego nunc auctoritatem sequens idem faciam.</a> Nam quibus rebus efficiuntur voluptates, eae non sunt in potestate sapientis. </p>

<p>Illorum vero ista ipsa quam exilia de virtutis vi! Quam tantam volunt esse, ut beatum per se efficere possit. <a href="http://loripsum.net/" target="_blank">Certe, nisi voluptatem tanti aestimaretis.</a> <a href="http://loripsum.net/" target="_blank">Quam si explicavisset, non tam haesitaret.</a> Primum cur ista res digna odio est, nisi quod est turpis? Eam si varietatem diceres, intellegerem, ut etiam non dicente te intellego; Ego quoque, inquit, didicerim libentius si quid attuleris, quam te reprehenderim. Ab his oratores, ab his imperatores ac rerum publicarum principes extiterunt. </p>

<p><b>Sin aliud quid voles, postea.</b> Nisi enim id faceret, cur Plato Aegyptum peragravit, ut a sacerdotibus barbaris numeros et caelestia acciperet? Idem adhuc; Non dolere, inquam, istud quam vim habeat postea videro; An me, inquam, nisi te audire vellem, censes haec dicturum fuisse? Itaque eo, quale sit, breviter, ut tempus postulat, constituto accedam ad omnia tua, Torquate, nisi memoria forte defecerit. Et harum quidem rerum facilis est et expedita distinctio. Quae contraria sunt his, malane? Hoc igitur quaerentes omnes, et ii, qui quodcumque in mentem veniat aut quodcumque occurrat se sequi dicent, et vos ad naturam revertemini. Nonne odio multos dignos putamus, qui quodam motu aut statu videntur naturae legem et modum contempsisse? Cognitio autem haec est una nostri, ut vim corporis animique norimus sequamurque eam vitam, quae rebus iis ipsis perfruatur. <b>Hunc vos beatum;</b> Vitiosum est enim in dividendo partem in genere numerare. Ut alios omittam, hunc appello, quem ille unum secutus est. Vos autem cum perspicuis dubia debeatis illustrare, dubiis perspicua conamini tollere. </p>

<p>Tollenda est atque extrahenda radicitus. Si quicquam extra virtutem habeatur in bonis. Quodsi vultum tibi, si incessum fingeres, quo gravior viderere, non esses tui similis; <a href="http://loripsum.net/" target="_blank">At hoc in eo M.</a> <a href="http://loripsum.net/" target="_blank">Mihi, inquam, qui te id ipsum rogavi?</a> Perturbationes autem nulla naturae vi commoventur, omniaque ea sunt opiniones ac iudicia levitatis. </p>

<p><b>Primum quid tu dicis breve?</b> At quicum ioca seria, ut dicitur, quicum arcana, quicum occulta omnia? <i>Qui-vere falsone, quaerere mittimus-dicitur oculis se privasse;</i> Superiores tres erant, quae esse possent, quarum est una sola defensa, eaque vehementer. Quae cum dixissem, Habeo, inquit Torquatus, ad quos ista referam, et, quamquam aliquid ipse poteram, tamen invenire malo paratiores. <b>Videsne, ut haec concinant?</b> Sed quot homines, tot sententiae; <a href="http://loripsum.net/" target="_blank">Recte, inquit, intellegis.</a> Modo etiam paulum ad dexteram de via declinavi, ut ad Pericli sepulcrum accederem. </p>

<p>Partim cursu et peragratione laetantur, congregatione aliae coetum quodam modo civitatis imitantur; Huc et illuc, Torquate, vos versetis licet, nihil in hac praeclara epistula scriptum ab Epicuro congruens et conveniens decretis eius reperietis. Sin tantum modo ad indicia veteris memoriae cognoscenda, curiosorum. <i>Non laboro, inquit, de nomine.</i> Virtutis, magnitudinis animi, patientiae, fortitudinis fomentis dolor mitigari solet. Si verbum sequimur, primum longius verbum praepositum quam bonum. </p>

<p>Itaque homo in primis ingenuus et gravis, dignus illa familiaritate Scipionis et Laelii, Panaetius, cum ad Q. Habes, inquam, Cato, formam eorum, de quibus loquor, philosophorum. Hoc est non modo cor non habere, sed ne palatum quidem. Sin tantum modo ad indicia veteris memoriae cognoscenda, curiosorum. Sed haec ab Antiocho, familiari nostro, dicuntur multo melius et fortius, quam a Stasea dicebantur. <a href="http://loripsum.net/" target="_blank">Quid censes in Latino fore?</a> </p>

<p><b>Deinde dolorem quem maximum?</b> Possumusne ergo in vita summum bonum dicere, cum id ne in cena quidem posse videamur? Nam si quae sunt aliae, falsum est omnis animi voluptates esse e corporis societate. Vide, ne etiam menses! nisi forte eum dicis, qui, simul atque arripuit, interficit. Quod maxime efficit Theophrasti de beata vita liber, in quo multum admodum fortunae datur. Intrandum est igitur in rerum naturam et penitus quid ea postulet pervidendum; <b>Quid sequatur, quid repugnet, vident.</b> <i>Summum ením bonum exposuit vacuitatem doloris;</i> </p>

<p>Qua exposita scire cupio quae causa sit, cur Zeno ab hac antiqua constitutione desciverit, quidnam horum ab eo non sit probatum; Ne discipulum abducam, times. Stoici restant, ei quidem non unam aliquam aut alteram rem a nobis, sed totam ad se nostram philosophiam transtulerunt; Idemne potest esse dies saepius, qui semel fuit? Heri, inquam, ludis commissis ex urbe profectus veni ad vesperum. Cum autem assumpta ratío est, tanto in dominatu locatur, ut omnia illa prima naturae hulus tutelae subiciantur. <a href="http://loripsum.net/" target="_blank">At hoc in eo M.</a> Idem fecisset Epicurus, si sententiam hanc, quae nunc Hieronymi est, coniunxisset cum Aristippi vetere sententia. Ita multo sanguine profuso in laetitia et in victoria est mortuus. Si sapiens, ne tum quidem miser, cum ab Oroete, praetore Darei, in crucem actus est. <b>Non est igitur voluptas bonum.</b> Quoniam, inquiunt, omne peccatum inbecillitatis et inconstantiae est, haec autem vitia in omnibus stultis aeque magna sunt, necesse est paria esse peccata. </p>

<p>Etenim nec iustitia nec amicitia esse omnino poterunt, nisi ipsae per se expetuntur. Ex quo intellegitur officium medium quiddam esse, quod neque in bonis ponatur neque in contrariis. <b>Nihilo magis.</b> </p>

<p>Eorum enim est haec querela, qui sibi cari sunt seseque diligunt. Non autem hoc: igitur ne illud quidem. An vero, inquit, quisquam potest probare, quod perceptfum, quod. Sed quia studebat laudi et dignitati, multum in virtute processerat. Illorum vero ista ipsa quam exilia de virtutis vi! Quam tantam volunt esse, ut beatum per se efficere possit. Restincta enim sitis stabilitatem voluptatis habet, inquit, illa autem voluptas ipsius restinctionis in motu est. Quid tibi, Torquate, quid huic Triario litterae, quid historiae cognitioque rerum, quid poetarum evolutio, quid tanta tot versuum memoria voluptatis affert? Itaque vides, quo modo loquantur, nova verba fingunt, deserunt usitata. <a href="http://loripsum.net/" target="_blank">Cur tantas regiones barbarorum pedibus obiit, tot maria transmisit?</a> Quis istum dolorem timet? </p>
';
@endphp




@section('grid_top')
    Conteúdo da grade no topo
    
@endsection

@section('grid_left')
    Conteúdo da grade à esquerda
@endsection

@section('grid_center')
    <div class="margin">
        Conteúdo da grade à centro
        <h3>Meu Título</h3>
        {!! $var !!}
        <br>
    </div>
@endsection


@section('grid_right')
    Conteúdo da grade à direita
@endsection


@section('grid_bottom')
    Conteúdo da grade na base
    
@endsection


@push('head')
<style>
    .awgrid-content{}
    .awgrid-left{}
    .awgrid-center{}
    .awgrid-right{}
</style>
@endpush