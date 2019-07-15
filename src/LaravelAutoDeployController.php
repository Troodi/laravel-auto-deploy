<?php

namespace Troodi\LaravelAutoDeploy;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Artisan;

class LaravelAutoDeployController extends Controller
{
    public function index(Request $request){
      Artisan::call('deploy');
    }
}
