<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Form;
use App\Utilities\HtmlUtility;

//somente abaixo da versão laravel 7
use Illuminate\Support\Facades\Blade;


/**
 * Classe de carregamento de componentes VIEW customizados.
 * Utiliza o Html&Form do Laravel
 * Ex de chamada de componente em arquivos blade: Form::loadScript('component-name');
 */
class ComponentServiceProvider extends ServiceProvider {

    public static $load_scripts = ['header'=>[],'footer'=>[]];
    
    /**
     * Sintaxe: name = [ js=>, css=>, dep=> ]
     *      js, css - string|array files. Ex url: 'http://...' ou '/...'
     *      dep     - string|array nome da pendência, ex 'forms'
     *      ver     - string version
     */
    public static $names_scripts=[
        //boxlist
        'boxlist' =>[
            'js'=>'/js/src/boxlist.js',
        ],
        
        //formulários
        'forms' => [
            'js'=>'/js/src/forms.js',
            'css'=>'/css/src/forms.css',
            'ver'=>'1.593'
        ],
        
        //listas
        'lists' =>[
            'js'=>'/js/src/lists.js',
            'css'=>'/css/src/lists.css',
            'dep'=>'inputs',
            'ver'=>'1.592'
        ],
        
        //taxonomias
        'taxonomies' => [
            'js'=>'/js/src/taxonomies.js',
            'css'=>'/css/src/taxonomies.css',
        ],
        
        //<!-- Select2 -->
        'select2' => [
            'js'=>'/AdminLTE-2.4.5/bower_components/select2/dist/js/select2.full.min.js',
            'css'=>'/AdminLTE-2.4.5/bower_components/select2/dist/css/select2.min.css',
        ],
        
        //<!-- date picker -->
        'datepicker' =>[
            'css'=>'/AdminLTE-2.4.5/bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css',
            'js'=>'/AdminLTE-2.4.5/bower_components/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js'
        ],
        
        //<!-- daterange picker // bootstrap datepicker -->
        'daterangepicker' =>[
            'js'=>['/AdminLTE-2.4.5/bower_components/moment/min/moment.min.js','/AdminLTE-2.4.5/bower_components/bootstrap-daterangepicker/daterangepicker.js'],
            'css'=>'/AdminLTE-2.4.5/bower_components/bootstrap-daterangepicker/daterangepicker.css',
        ],
        
        /*//substiuído por checkbox/radio direto em html + class .checkmark
        //<!-- iCheck for checkboxes and radio inputs -->
        'check'=>
            '<link rel="stylesheet" href="{{route}}/AdminLTE-2.4.5/plugins/iCheck/all.css">'.
            '<script src="{{route}}/AdminLTE-2.4.5/plugins/iCheck/icheck.min.js"></script>',
        */
        
        //<!-- Bootstrap Color Picker -->
        'colorpicker'=>[
            'js'=>'/AdminLTE-2.4.5/bower_components/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js',
            'css'=>'/AdminLTE-2.4.5/bower_components/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css',
            'dep'=>''
        ],
        
        //<!-- Bootstrap time Picker -->
        'timepicker'=>[
            'js'=>'/AdminLTE-2.4.5/plugins/timepicker/bootstrap-timepicker.min.js',
            'css'=>'/AdminLTE-2.4.5/plugins/timepicker/bootstrap-timepicker.min.css',
            'dep'=>''
        ],
        
        //<!-- SlimScroll -->
        'slimscroll'=>[
            'js'=>'/AdminLTE-2.4.5/bower_components/jquery-slimscroll/jquery.slimscroll.min.js',
        ],
        
        //<!-- FastClick -->
        'fastclick'=>[
            'js'=>'/AdminLTE-2.4.5/bower_components/fastclick/lib/fastclick.js',
        ],
        
        /*//<!-- bootstrap wysihtml5 - text editor -->
        'editor'=>
            '<link rel="stylesheet" href="{{route}}/AdminLTE-2.4.5/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">'.
            '<script src="{{route}}/AdminLTE-2.4.5/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>',
        */
        //<!-- CK Editor -->
        'ckeditor'=>[
            'js'=>['/AdminLTE-2.4.5/bower_components/ckeditor/ckeditor.js','/js/editors/ckeditor.js'],
        ],
        
        //<!-- Jodit Editor -->
        'jodit'=>[
            'js'=>['/plugins/jodit/jodit-3.6.18/build/jodit.min.js','/js/editors/jodit.js'],
            'css'=>'/plugins/jodit/jodit-3.6.18/build/jodit.min.css',
        ],
        
        //<!-- Editor de código -->
        'codemirror'=>[
            'js'=>[
                '/plugins/codemirror/5.61.1/codemirror.min.js',
                '/plugins/codemirror/5.61.1/addon/selection-pointer.min.js',
                '/plugins/codemirror/5.61.1/addon/active-line.min.js',
                /*'/plugins/codemirror/5.61.1/mode/javascript.min.js',
                '/plugins/codemirror/5.61.1/mode/css.min.js',
                '/plugins/codemirror/5.61.1/mode/vbscript.min.js',
                '/plugins/codemirror/5.61.1/mode/xml.min.js',
                '/plugins/codemirror/5.61.1/mode/htmlmixed.min.js',*/
                '/js/editors/editorcode.js'
            ],
            'css'=>[
                '/css/editors/editorcode.css',
                '/plugins/codemirror/5.61.1/codemirror.min.css'
            ],
        ],
        
        //<!-- DataTables -->
        'datatable'=>[
            'js'=>['/AdminLTE-2.4.5/bower_components/datatables.net/js/jquery.dataTables.min.js','/AdminLTE-2.4.5/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js'],
            'css'=>'/AdminLTE-2.4.5/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css',
        ],
        
        //<!-- InputMask -->
        'inputmask'=>[
            //AdminLTE-2.4.5/plugins/input-mask/jquery.inputmask.js
            //AdminLTE-2.4.5/plugins/input-mask/jquery.inputmask.date.extensions.js
            //AdminLTE-2.4.5/plugins/input-mask/jquery.inputmask.extensions.js
            'js'=>'/plugins/input-mask/jquery.inputmask.bundle.min.js',
        ],
        
        //Slick 1.9 - http://kenwheeler.github.io/slick/
        'slick'=>[
            'js'=>'/plugins/slick/slick.min.js',
            'css'=>'/plugins/slick/slick.css',
        ],
        
        //Sticky 1.0.4 - http://stickyjs.com/
        'sticky'=>[
            'js'=>'/plugins/sticky-1.0.4/jquery.sticky.min.js',
        ],
        
        //desabilitado - analisando melor se realmente precisa
        //DoubleScroll 0.5 - https://github.com/avianey/jqDoubleScroll
        'doublescroll'=>[
            'js'=>'/plugins/double-scroll/jquery.doubleScroll.js',
        ],
        
        //gallery images - https://photoswipe.com/
        'photoswipe'=>[
            'js'=>[
                '/plugins/photoswipe/photoswipe.min.js',
                '/plugins/photoswipe/photoswipe-ui-default.min.js',
                //funções adicionais do sistema
                '/js/src/gallery.js?ver=1.1'
            ],
            'css'=>[
                '/plugins/photoswipe/photoswipe.css',
                '/plugins/photoswipe/default-skin/default-skin.css',
                //funções adicionais do sistema
                '/css/src/gallery.css?ver=1.0',
            ],
        ],
        
        //mentions
        'mentionjs'=>[
            'js'=>['/plugins/caret/jquery.caret.min.js','/js/src/mentionjs.js'],
            'dep'=>'boxlist'
        ],
        
        //https://github.com/lukasoppermann/html5sortable
        'html5sortable'=>[
            'js'=>'/plugins/html5sortable/html5sortable.min.js'
        ]
    ];
    
    
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public static $fnc_names = [];
    public static $all_files=[];
    public function boot() {
        $scripts_loaded = explode(',',\Request::header('x-scripts-loaded'));//array de nomes de scripts já carregados
        //dd($scripts_loaded);
        
        
       /**
         * Carrega na view os arquivos base para funcionamento do componente CSS,JS
         * Param string name - deve conter o identificador do camponente
         * Param string files - caminho do componente css/js (opcional, e neste caso deverá ser um nome que já conste na lista de componentes). Ex: Form::loadScript('custom_name','<link rel="stylesheet" href="file.css">');
         *       array files - sintaxe: [js=>..., css=>...]     //ex de valor url: Ex url: 'http://...' ou '/...'
         * Return void
         */
        Form::macro('loadScript', function($name,$files='',$isFooter=false){
            if(\Request::ajax())$isFooter=true;//se a origem da requisição for ajax, então footer=true para escrever o caminho do arquivo a ser carregado no final da página
            $a = $isFooter ? 'footer' : 'header';
            $s = ComponentServiceProvider::$load_scripts;
            $n = ComponentServiceProvider::$names_scripts;
            if (!isset($s[$a][$name]) && !empty($name)) {
                if(isset($n[$name])){$files = $n[$name];}
                
                //carrega os itens dependêntes
                $dep=$files['dep']??null;
                if($dep){
                    if(!is_array($dep))$dep=[$dep];
                    foreach($dep as $d){
                        Form::loadScript($d);
                        //$r=$n[$d];
                        //dd($r);
                    }
                    //dd($files,$d);
                }
                //dump($name);
                
                $s='';
                if($files['js']??null){
                    $n=HtmlUtility::importJSCSS('js',$files['js'],$name,($files['ver']??'')) ;
                    ComponentServiceProvider::$all_files[]=$n;
                    $s.=$n;
                }
                if($files['css']??null){
                    $n=HtmlUtility::importJSCSS('css',$files['css'],$name,($files['ver']??''));
                    ComponentServiceProvider::$all_files[]=$n;
                    $s.=$n;
                }
                ComponentServiceProvider::$load_scripts[$a][$name] = $s;
            }
        });
        
        
        /**
         * Executa a função JS ao carregar a página. As funções registradas são executadas pelo comando JS awFncJsInit();
         * Param array|string name - nome da função.
         * Return void
         * Ex: Form:execFnc('myFunctionName');
         */
        Form::macro('execFnc', function($name=''){
            if(!empty($name)){
                $a=ComponentServiceProvider::$fnc_names;
                if(is_array($name)){
                    foreach($name as $f){
                        if(!in_array($n,$a))$a[]=$n;
                    }
                }else{
                    if(!in_array($name,$a))$a[]=$name;
                }
                ComponentServiceProvider::$fnc_names=$a;
            }
        });
        
        
        /*
         * Escreve na view os scripts carregados pela macro LoadScript
         */
        Form::macro('writeScripts', function($isFooter=false) use($scripts_loaded){
            $f='';
            $s_loaded=[];
            foreach(ComponentServiceProvider::$load_scripts[$isFooter?'footer':'header'] as $name=>$files){
                if(in_array($name,$scripts_loaded))continue;//já carregado, portanto não precisa carregar o script novamente
                if(!empty($files)){
                    $f.= '<!-- '.$name.' -->' .PHP_EOL. $files .PHP_EOL;
                    $s_loaded[]=$name;
                }
            }
            $f=str_replace('{{route}}',url('/'),$f);
            
            //escreve os nomes das funções estáticas js a serem executadas no final da página
            //escreve os nomes das funções estáticas js a serem executadas no final da página
            if($isFooter && !empty(ComponentServiceProvider::$fnc_names))
                    $f.='<script>awFncJsInit("'.join(',',ComponentServiceProvider::$fnc_names).'"'.  ($s_loaded?',"'.join(',',$s_loaded).'"':'')  .');</script>';
            return $f;
        });
        
        
        //somente abaixo da versão laravel 7
        Blade::directive('pushonce', function ($expression) {
            $var = '$__env->{"__pushonce_" . md5(__FILE__ . ":" . __LINE__)}';
            return "<?php if(!isset({$var})): {$var} = true; \$__env->startPush({$expression}); ?>";
        });

        Blade::directive('endpushonce', function ($expression) {
            return '<?php $__env->stopPush(); endif; ?>';
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        //
    }
    
    

}
