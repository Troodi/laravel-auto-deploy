<?
Route::post('/deploy', [
   'uses' => 'Troodi\LaravelAutoDeploy\LaravelAutoDeployController@index',
   'nocsrf' => true,
]);
