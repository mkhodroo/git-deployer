<?php

namespace Behin\GitDeployer\Console\Commands;

use Behin\GitDeployer\Models\DeployProject;
use Behin\GitDeployer\Services\GitDeployer;
use Illuminate\Console\Command;

class DeployerRollbackCommand extends Command
{
    protected $signature = 'deployer:rollback {project : نام پروژه} {commit : هش کامیت یا تگ مقصد} {--no-post : اجرای نکردن دستورات پس از دپلوی}';
    protected $description = 'بازگشت (آپگرید/دان‌گرید) به کامیت مشخص';

    public function handle(GitDeployer $deployer): int
    {
        $project = DeployProject::where('name', $this->argument('project'))->first();
        if (! $project) {
            $this->error('پروژه پیدا نشد.');

            return self::FAILURE;
        }

        if (! $this->confirm("بازگشت پروژه «{$project->name}» به «{$this->argument('commit')}»؟", true)) {
            return self::SUCCESS;
        }

        try {
            $result = $deployer->rollback($project, $this->argument('commit'), 'artisan', ! $this->option('no-post'));
            $this->info('بازگشت موفق به '.substr($result['to'], 0, 8));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
