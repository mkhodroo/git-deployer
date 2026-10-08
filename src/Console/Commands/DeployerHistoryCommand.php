<?php

namespace Behin\GitDeployer\Console\Commands;

use Behin\GitDeployer\Models\DeployProject;
use Behin\GitDeployer\Services\GitDeployer;
use Illuminate\Console\Command;

class DeployerHistoryCommand extends Command
{
    protected $signature = 'deployer:history {project : نام پروژه} {--limit=20 : تعداد کامیت}';
    protected $description = 'نمایش تاریخچه کامیت‌های ریموت برای انتخاب نسخه';

    public function handle(GitDeployer $deployer): int
    {
        $project = DeployProject::where('name', $this->argument('project'))->first();
        if (! $project) {
            $this->error('پروژه پیدا نشد.');

            return self::FAILURE;
        }

        try {
            $rows = $deployer->history($project, (int) $this->option('limit'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['هش کوتاه', 'پیام', 'نویسنده', 'تاریخ', 'فعلی؟'],
            array_map(fn ($r) => [$r['short'], mb_substr($r['subject'], 0, 60), $r['author'], $r['date'], $r['current'] ? '*' : ''], $rows)
        );

        return self::SUCCESS;
    }
}
