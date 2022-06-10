<?php

namespace App\Http\Controllers\SuperAdmin\Process;

use Illuminate\Http\Request;

/**
 * Classe responsável pelo processo de cadastro de apólices no Quiver
 */
class ProcessCadApoliceController extends \App\Http\Controllers\Process\ProcessCadApoliceController {
   public function index(Request $request){
       return $this->get_list($request);
   }
   
}
