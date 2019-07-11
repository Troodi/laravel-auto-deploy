<?php

namespace Troodi\LaravelAutoDeploy;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class LaravelAutoDeployController extends Controller
{
    public function index(Request $request){
      Artisan::call('deploy');
    }
}
