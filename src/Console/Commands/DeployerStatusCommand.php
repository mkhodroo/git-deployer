<?php

namespace Behin\GitDeployer\Console\Commands;

use Behin\GitDeployer\Models\DeployProject;
use Illuminate\Console\Command;

class DeployerStatusCommand extends Command
{
    protected $signature = 'deployer:status {project : نام پروژه}';
    protected $description = 'نمایش وضعیت دپلوی یک پروژه (عقب‌ماندگی از ریموت)';

    public function handle(): int
    {
        $project = DeployProject::where('name', $this->argument('project'))->first();
        if (! $project) {
            $this->error('پروژه پیدا نشد.');

            return self::FAILURE;
        }

        $status = app(\Behin\GitDeployer\Services\GitDeployer::class)->status($project);

        $this->table(['کلید', 'مقدار'], collect($status)->map(fn ($v, $k) => [
            $k, is_bool($v) ? ($v ? 'بله' : 'خیر') : ($v ?? '-'),
        ])->values()->all());

        return self::SUCCESS;
    }
}
