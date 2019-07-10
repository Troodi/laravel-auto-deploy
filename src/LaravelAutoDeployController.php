<?php

namespace Troodi\LaravelAutoDeploy;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class LaravelAutoDeployController extends Controller
{
    public function index(Request $request){
      app('log')->stack([
          'channels' => [
            'deploy' => [
                'driver' => 'daily',
                'path' => storage_path('logs/deploy.log'),
                'level' => 'debug',
                'days' => 14,
            ],
          ]
      ]);

      Artisan::call('deploy');
    }
}
