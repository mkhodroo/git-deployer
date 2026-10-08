<?php

namespace Behin\GitDeployer\Console\Commands;

use Behin\GitDeployer\Models\DeployProject;
use Behin\GitDeployer\Services\GitDeployer;
use Illuminate\Console\Command;

class DeployerDiagnoseCommand extends Command
{
    protected $signature = 'deployer:diagnose {project : نام پروژه}';
    protected $description = 'عیب‌یابی محیط دپلوی (وضعیت git، دسترسی پوشه، اتصال init)';

    public function handle(GitDeployer $deployer): int
    {
        $project = DeployProject::where('name', $this->argument('project'))->first();
        if (! $project) {
            $this->error('پروژه پیدا نشد.');

            return self::FAILURE;
        }

        $rows = [];
        foreach ($deployer->diagnose($project) as $c) {
            $rows[] = [
                $c['label'],
                $c['ok'] === true ? 'OK' : ($c['ok'] === false ? 'FAIL' : '-'),
                mb_substr((string) $c['detail'], 0, 120),
            ];
        }
        $this->table(['مورد', 'وضعیت', 'جزئیات'], $rows);

        return self::SUCCESS;
    }
}
