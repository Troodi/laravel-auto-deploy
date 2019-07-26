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
	  $config_array = config('logging');
      $default = config('logging')['default'];
      $channel_array = $config_array['channels'];
      $custom_channel_array = [
          'deploy' => [
              'driver' => 'daily',
              'path' => storage_path('logs/deploy.log'),
              'level' => 'debug',
              'days' => 14,
          ],
      ];

      $final_channel_array = array_merge($channel_array, $custom_channel_array);
      $final_config_array = array_merge(["default" => $default], ["channels" => $final_channel_array]);
      config()->set('logging', $final_config_array);
      Log::channel('deploy')->info('Deploying started');
      $this->line('Deploying started');
      if($this->option('maintenance')){
        Artisan::call('down');
      }
      Log::channel('deploy')->info('Get latest commit');
      $this->line('Get latest commit');
      $process = new Process('cd '.base_path().' && git reset --hard && git pull');
      $process->setTimeout(3600);
      try {
          $process->mustRun();
          Log::channel('deploy')->info($process->getOutput());
          $this->line($process->getOutput());
          if(Str::contains($process->getOutput(), ['Already up-to-date', 'Already up to-date', 'Already up to date', 'Already up-to date'])){
              Log::channel('deploy')->info('You have the latest version');
              $this->line('You have the latest version');
              Log::channel('deploy')->info('--------------------------------------------------------------------');
              return;
          }
      } catch (\Exception $exception) {
          Log::channel('deploy')->error('An error occurred while executing the command "git".');
          $this->line('An error occurred while executing the command "git".');
          Log::channel('deploy')->error($exception->getMessage());
          $this->line($exception->getMessage());
          if($this->option('maintenance')){
            Artisan::call('up');
          }
          Log::channel('deploy')->info('--------------------------------------------------------------------');
          return;
      }
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
      $process = new Process('cd '.base_path().' && '.$php_bin.' '.base_path().'/vendor/troodi/laravel-auto-deploy/src/composer.phar update');
      $process->setEnv([
          'COMPOSER_HOME' => base_path().'/vendor/troodi/laravel-auto-deploy/src/composer.phar',
          'COMPOSER_CACHE_DIR' => base_path().'/.composer/cache'
      ]);
      $process->setTimeout(3600);
      try {
          $process->mustRun();
          Log::channel('deploy')->info($process->getOutput());
          $this->line($process->getOutput());
      } catch (\Exception $exception) {
          Log::channel('deploy')->error('An error occurred while executing the command "composer".');
          $this->line('An error occurred while executing the command "composer".');
          Log::channel('deploy')->error($exception->getMessage());
          $this->line($exception->getMessage());
          if($this->option('maintenance')){
            Artisan::call('up');
          }
          Log::channel('deploy')->info('--------------------------------------------------------------------');
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
