<?php
namespace App\Http\Controllers\Site;

use Illuminate\Http\Request;

/**
 * Classe de exemplo de Posts 
 * Acesso por rota não autenticada
 */

!!!!! em desenvolvimento !!!!
class PostController extends \App\Http\Controllers\PostController{
    
    //seta que este controller tem acesso público
    protected $public = true;
    
}
