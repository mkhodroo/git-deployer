<?php

namespace Behin\GitDeployer\Console\Commands;

use Behin\GitDeployer\Models\DeployProject;
use Illuminate\Console\Command;

class DeployerListCommand extends Command
{
    protected $signature = 'deployer:list';
    protected $description = 'نمایش لیست پروژه‌های دپلوی';

    public function handle(): int
    {
        $projects = DeployProject::orderBy('name')->get();
        if ($projects->isEmpty()) {
            $this->warn('هنوز پروژه‌ای ثبت نشده است.');

            return self::SUCCESS;
        }

        $this->table(
            ['نام', 'شاخه', 'مسیر', 'کامیت فعلی', 'آخرین وضعیت', 'آخرین دپلوی'],
            $projects->map(fn ($p) => [
                $p->name, $p->branch, $p->deploy_path,
                $p->current_commit ? substr($p->current_commit, 0, 8) : '-',
                $p->last_status ?? '-',
                $p->last_deployed_at?->format('Y-m-d H:i') ?? '-',
            ])->all()
        );

        return self::SUCCESS;
    }
}
