<?php

namespace Behin\GitDeployer\Console\Commands;

use Behin\GitDeployer\Models\DeployProject;
use Behin\GitDeployer\Services\GitDeployer;
use Illuminate\Console\Command;

class DeployerUpdateCommand extends Command
{
    protected $signature = 'deployer:update {project : نام پروژه} {--ref= : کامیت/تگ/شاخه خاص (خالی = آخرین کامیت شاخه)} {--no-post : اجرای نکردن دستورات پس از دپلوی}';
    protected $description = 'گرفتن آخرین تغییرات از گیت و اعمال روی دایرکتوری';

    public function handle(GitDeployer $deployer): int
    {
        $project = DeployProject::where('name', $this->argument('project'))->first();
        if (! $project) {
            $this->error('پروژه پیدا نشد.');

            return self::FAILURE;
        }

        try {
            $result = $deployer->update($project, $this->option('ref') ?: null, 'artisan', ! $this->option('no-post'));
            $this->info('از '.($result['from'] ? substr($result['from'], 0, 8) : '-').' به '.substr($result['to'], 0, 8));
            if ($this->getOutput()->isVerbose()) {
                $this->line($result['output']);
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
