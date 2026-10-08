<?php

namespace Behin\GitDeployer\Console\Commands;

use Behin\GitDeployer\Models\DeployProject;
use Behin\GitDeployer\Services\GitDeployer;
use Illuminate\Console\Command;

class DeployerInitCommand extends Command
{
    protected $signature = 'deployer:init
        {project? : نام پروژه (خالی = ساخت پروژه جدید)}
        {--repo= : آدرس ریپازیتوری گیت}
        {--branch=main : شاخه}
        {--path= : مسیر دایرکتوری روی هاست}
        {--auth=none : نوع احراز هویت: none|token|ssh}';
    protected $description = 'اتصال اولیه ریپازیتوری گیت به دایرکتوری هاست';

    public function handle(GitDeployer $deployer): int
    {
        $name = $this->argument('project') ?: $this->ask('نام پروژه');
        $project = DeployProject::where('name', $name)->first();

        if (! $project) {
            $repo = $this->option('repo') ?: $this->ask('آدرس ریپازیتوری (repo_url)');
            $branch = $this->option('branch') ?: $this->ask('شاخه', config('git-deployer.default_branch', 'main'));
            $path = $this->option('path') ?: $this->ask('مسیر دایرکتوری روی هاست (deploy_path)');
            $auth = $this->option('auth') ?: $this->choice('نوع احراز هویت', ['none', 'token', 'ssh'], 0);

            $project = new DeployProject([
                'name' => $name, 'repo_url' => $repo, 'branch' => $branch,
                'deploy_path' => $path, 'auth_type' => $auth,
            ]);

            if ($auth === 'token') {
                $project->username = $this->ask('نام کاربری (اختیاری)', 'oauth2');
                $project->token = $this->secret('توکن دسترسی');
            } elseif ($auth === 'ssh') {
                $project->ssh_key_path = $this->ask('مسیر کلید SSH خصوصی');
            }

            $project->save();
            $this->info("پروژه «{$name}» ساخته شد.");
        }

        $this->info('در حال اتصال اولیه...');

        try {
            $result = $deployer->init($project, $this->actor());
            $this->info('اتصال موفق. کامیت: '.substr($result['commit'], 0, 8));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function actor(): ?string
    {
        try {
            return 'artisan:'.(function_exists('get_current_user') ? get_current_user() : 'cli');
        } catch (\Throwable) {
            return 'artisan';
        }
    }
}
