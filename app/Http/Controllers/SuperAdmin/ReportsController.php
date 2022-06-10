<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;

class ReportsController extends SuperAdminBaseController{
    
    
    public function get_processTotals(){
        return view('reports.process_totals');
    }
}