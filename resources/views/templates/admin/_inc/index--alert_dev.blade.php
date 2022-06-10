@php
$alert_dev='';
if(in_array(env('APP_ENV'),['local','homologation']))$alert_dev.='Versão em '. (env('APP_ENV')=='local'?'Desenvolvimento':'Homologação');
if(env('APP_SETUP')===true)$alert_dev.='<br>Atenção: Configuração de SETUP habilitado';
if(env('APP_MAINTENANCE')=='on')$alert_dev.='<br>Modo de Manutenção Ativado';
if($alert_dev)$alert_dev = '<div style="background:'. (env('APP_ENV')=='local'?'red':'orange')  .';position:fixed;left:300px;width:calc(100% - 420px);right:46px;pointer-events:none;z-index:9999;text-align:center;padding:2px;font-size:9px;color:#fff;text-transform:uppercase;font-weight:bold;">'. $alert_dev .'</div>';
echo $alert_dev;
@endphp
