<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function landing_page(){
        return view("pages.landing_page"); 
    }

    

    public function tasks(){
        return view("pages.tasks");
    }

    public function metrics(){
        return view("pages.metrics");
    }
}

