<?php

namespace Troodi\LaravelAutoDeploy;

use Illuminate\Console\Command;
use File;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use Artisan;
use Illuminate\Support\Str;
use Log;

class LaravelAutoDeployCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy {--maintenance}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulling project using git';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      Log::channel('deploy')->info('Deploying started');
      $this->line('Deploying started');
      if($this->option('maintenance')){
        Artisan::call('down');
      }
      Log::channel('deploy')->info('Get latest commit');
      $this->line('Get latest commit');
      // $process = new Process('cd '.base_path().' && git pull');
      // $process->setTimeout(3600);
      // try {
      //     $process->mustRun();
      //     Log::channel('deploy')->info($process->getOutput());
      //     echo $process->getOutput();
      //     if(Str::contains($process->getOutput(), 'Already up to date.')){
      //         Log::channel('deploy')->info('You have the latest version');
      //         $this->line('You have the latest version');
      //         return;
      //     }
      // } catch (\Exception $exception) {
      //     Log::channel('deploy')->error('An error occurred while executing the command "git".');
      //     $this->line('An error occurred while executing the command "git".');
      //     Log::channel('deploy')->error($exception->getMessage());
      //     echo $exception->getMessage();
      //     if($this->option('maintenance')){
      //       Artisan::call('up');
      //     }
      //     return;
      // }
      Log::channel('deploy')->info('Update composer packages');
      $this->line('Update composer packages');
      if(!(new PhpExecutableFinder)->find()){
        $php_bin = env('PHP_BIN', '/opt/php73/bin/php');
      } else {
        $php_bin = (new PhpExecutableFinder)->find();
      }
      if(!File::isDirectory(base_path().'/.composer')){
          File::makeDirectory(base_path().'/.composer', 0755, true, true);
      }
      if(!File::isDirectory(base_path().'/.composer/cache')){
          File::makeDirectory(base_path().'/.composer/cache', 0755, true, true);
      }
      $process = new Process('cd '.base_path().' && '.$php_bin.' composer.phar update');
      $process->setEnv([
          'COMPOSER_HOME' => base_path().'/composer.phar',
          'COMPOSER_CACHE_DIR' => base_path().'/.composer/cache'
      ]);
      $process->setTimeout(3600);
      try {
          $process->mustRun();
          Log::channel('deploy')->info($process->getOutput());
          echo $process->getOutput();
          Log::channel('deploy')->info($process->getOutput());
      } catch (\Exception $exception) {
          Log::channel('deploy')->error('An error occurred while executing the command "composer".');
          $this->line('An error occurred while executing the command "composer".');
          Log::channel('deploy')->info($process->getOutput());
          Log::channel('deploy')->error($exception->getMessage());
          echo $exception->getMessage();
          if($this->option('maintenance')){
            Artisan::call('up');
          }
          return;
      }
      Log::channel('deploy')->info('Migrating');
      $this->line('Migrating');
      Artisan::call('migrate');
      Log::channel('deploy')->info('Generate storage link');
      $this->line('Generate storage link');
      Artisan::call('storage:link');
      Log::channel('deploy')->info('Clearing cache');
      $this->line('Clearing cache');
      Artisan::call('view:clear');
      Artisan::call('config:clear');
      Artisan::call('route:clear');
      Log::channel('deploy')->info('Restarting queue');
      $this->line('Restarting queue');
      Artisan::call('queue:restart');
      if($this->option('maintenance')){
        Artisan::call('up');
      }
      $this->line('Pulling completed successfully');
      Log::channel('deploy')->info('Pulling completed successfully');
      Log::channel('deploy')->info('--------------------------------------------------------------------');
    }
}