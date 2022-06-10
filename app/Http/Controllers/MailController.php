<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Controller de gerenciamento de e-mails em geral
 */
class MailController extends Controller{
    
    /**
     * Permite visualizar um template de e-mail 
     */
    public function get_viewFile(Request $request){
        //em desenvolvimento...
        //exemplo de classe de teste de e-mail
        return new \App\Mail\TestMail();
    }
}
