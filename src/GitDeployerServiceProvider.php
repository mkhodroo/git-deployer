<?php

namespace Behin\GitDeployer;

use Behin\GitDeployer\Console\Commands\DeployerDiagnoseCommand;
use Behin\GitDeployer\Console\Commands\DeployerHistoryCommand;
use Behin\GitDeployer\Console\Commands\DeployerInitCommand;
use Behin\GitDeployer\Console\Commands\DeployerListCommand;
use Behin\GitDeployer\Console\Commands\DeployerRollbackCommand;
use Behin\GitDeployer\Console\Commands\DeployerStatusCommand;
use Behin\GitDeployer\Console\Commands\DeployerUpdateCommand;
use Illuminate\Support\ServiceProvider;

class GitDeployerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/git-deployer.php', 'git-deployer');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'git-deployer');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/git-deployer.php' => config_path('git-deployer.php'),
            ], 'git-deployer-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/git-deployer'),
            ], 'git-deployer-views');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'git-deployer-migrations');

            $this->commands([
                DeployerListCommand::class,
                DeployerDiagnoseCommand::class,
                DeployerInitCommand::class,
                DeployerStatusCommand::class,
                DeployerHistoryCommand::class,
                DeployerUpdateCommand::class,
                DeployerRollbackCommand::class,
            ]);
        }
    }
}
