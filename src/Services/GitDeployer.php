<?php

namespace Behin\GitDeployer\Services;

use Behin\GitDeployer\Models\DeployLog;
use Behin\GitDeployer\Models\DeployProject;
use RuntimeException;

class GitDeployer
{
    public function __construct(protected GitProcess $git)
    {
    }

    public function init(DeployProject $project, ?string $actor = null): array
    {
        $this->guardPath($project->deploy_path);
        $from = $project->current_commit;
        $path = $project->deploy_path;
        $log = [];

        try {
            if ($this->isGitRepo($path)) {
                $log[] = $this->git->runOrFail(['remote', 'set-url', 'origin', $this->authenticatedUrl($project)], $path);
                $log[] = $this->git->runOrFail(['fetch', '--all', '--prune'], $path, $this->gitEnv($project));
            } else {
                $this->ensureExists($path);
                if ($this->isEmptyDir($path)) {
                    $log[] = $this->git->runOrFail(
                        ['clone', '--branch', $project->branch, $this->authenticatedUrl($project), $path.'.tmp-clone'],
                        dirname($path),
                        $this->gitEnv($project)
                    );
                    $this->moveCloneIntoPlace($path);
                } else {
                    // پوشه پر است و گیت نیست (حالت رایج هاست اشتراکی مثل public_html):
                    // درجا git init می‌کنیم تا فایل‌های untracked مثل .env حفظ شوند.
                    // فایل‌هایی که در ریپو هم هستند بازنویسی می‌شوند.
                    $log[] = $this->git->runOrFail(['init', '-b', $project->branch], $path);
                    $hasOrigin = $this->git->run(['remote', 'get-url', 'origin'], $path);
                    if ($hasOrigin['exit_code'] === 0) {
                        $log[] = $this->git->runOrFail(['remote', 'set-url', 'origin', $this->authenticatedUrl($project)], $path);
                    } else {
                        $log[] = $this->git->runOrFail(['remote', 'add', 'origin', $this->authenticatedUrl($project)], $path);
                    }
                    $log[] = $this->git->runOrFail(['fetch', 'origin', '--prune', '--tags'], $path, $this->gitEnv($project));
                }
            }

            $this->markSafe($path);
            $log[] = $this->git->runOrFail(['checkout', '-f', "origin/{$project->branch}"], $path, $this->gitEnv($project));
            $log[] = $this->git->runOrFail(['reset', '--hard', "origin/{$project->branch}"], $path, $this->gitEnv($project));

            $to = $this->currentCommit($path);
            $this->afterDeploy($project, $from, $to, 'init', $actor, implode("\n", array_filter($log)));

            return ['commit' => $to, 'output' => implode("\n", array_filter($log))];
        } catch (\Throwable $e) {
            $this->writeLog($project, 'init', $from, null, 'failed', $e->getMessage(), implode("\n", $log), $actor);
            throw $e;
        }
    }


    public function update(DeployProject $project, ?string $ref = null, ?string $actor = null, bool $withPostDeploy = true): array
    {
        $this->guardPath($project->deploy_path);
        $path = $project->deploy_path;
        $from = $project->current_commit;

        if (! $this->isGitRepo($path)) {
            throw new RuntimeException("مسیر «{$path}» هنوز به گیت متصل نیست. ابتدا دکمه «اتصال اولیه (init)» را در صفحه پروژه بزنید یا دستور php artisan deployer:init {$project->name} را اجرا کنید.");
        }

        $this->markSafe($path);
        $log = [];

        try {
            $log[] = $this->git->runOrFail(['fetch', '--all', '--prune', '--tags'], $path, $this->gitEnv($project));
            $target = $ref ? $this->resolveRef($project, $ref) : "origin/{$project->branch}";
            $log[] = $this->git->runOrFail(['checkout', '-f', $target], $path, $this->gitEnv($project));
            $log[] = $this->git->runOrFail(['reset', '--hard', $target], $path, $this->gitEnv($project));
            $to = $this->currentCommit($path);

            if ($withPostDeploy) {
                $post = $this->runPostDeploy($project, $path);
                if ($post !== '') {
                    $log[] = $post;
                }
            }

            $event = $ref ? 'rollback' : 'deploy';
            $this->afterDeploy($project, $from, $to, $event, $actor, implode("\n", array_filter($log)));

            return ['from' => $from, 'to' => $to, 'output' => implode("\n", array_filter($log))];
        } catch (\Throwable $e) {
            $this->writeLog($project, $ref ? 'rollback' : 'deploy', $from, null, 'failed', $e->getMessage(), implode("\n", $log), $actor);
            throw $e;
        }
    }

    public function rollback(DeployProject $project, string $commit, ?string $actor = null, bool $withPostDeploy = true): array
    {
        return $this->update($project, $commit, $actor, $withPostDeploy);
    }


    public function status(DeployProject $project): array
    {
        $path = $project->deploy_path;
        $installed = $this->isGitRepo($path);
        $data = [
            'installed' => $installed, 'path' => $path,
            'branch' => $project->branch, 'current' => $project->current_commit,
            'local_commit' => null, 'remote_commit' => null,
            'behind' => null, 'up_to_date' => null, 'dirty' => null,
        ];
        if (! $installed) {
            return $data;
        }
        $this->git->run(['fetch', '--all', '--prune', '--tags'], $path, $this->gitEnv($project), 120);
        $data['local_commit'] = $this->try(fn () => $this->git->runOrFail(['rev-parse', 'HEAD'], $path));
        $data['remote_commit'] = $this->try(fn () => $this->git->runOrFail(['rev-parse', "origin/{$project->branch}"], $path));
        $dirty = $this->try(fn () => $this->git->runOrFail(['status', '--porcelain'], $path));
        $data['dirty'] = $dirty !== null ? trim((string) $dirty) !== '' : null;
        if ($data['local_commit'] && $data['remote_commit']) {
            $data['up_to_date'] = $data['local_commit'] === $data['remote_commit'];
            $behind = $this->try(fn () => $this->git->runOrFail(['rev-list', '--count', "HEAD..origin/{$project->branch}"], $path));
            $data['behind'] = is_numeric(trim((string) $behind)) ? (int) trim((string) $behind) : null;
        }

        return $data;
    }

    public function history(DeployProject $project, int $limit = 20): array
    {
        $path = $project->deploy_path;
        if (! $this->isGitRepo($path)) {
            throw new RuntimeException('پروژه هنوز init نشده است.');
        }
        $this->git->run(['fetch', '--all', '--prune', '--tags'], $path, $this->gitEnv($project), 120);
        $format = '%H%x1f%h%x1f%s%x1f%an%x1f%ad';
        $raw = $this->git->runOrFail(['log', "origin/{$project->branch}", '-n', (string) $limit, "--pretty=format:{$format}", '--date=iso'], $path, $this->gitEnv($project));
        $current = $project->current_commit ?: $this->try(fn () => $this->git->runOrFail(['rev-parse', 'HEAD'], $path));
        $rows = [];
        foreach (explode("\n", $raw) as $line) {
            if (trim($line) === '') {
                continue;
            }
            [$hash, $short, $subject, $author, $date] = array_pad(explode("\x1f", $line), 5, '');
            $rows[] = ['hash' => $hash, 'short' => $short, 'subject' => $subject, 'author' => $author, 'date' => $date, 'current' => $hash === $current];
        }

        return $rows;
    }

    public function isGitRepo(string $path): bool
    {
        return is_dir($path) && is_dir($path.DIRECTORY_SEPARATOR.'.git');
    }

    public function currentCommit(string $path): string
    {
        return trim($this->git->runOrFail(['rev-parse', 'HEAD'], $path));
    }

    protected function resolveRef(DeployProject $project, string $ref): string
    {
        $ref = trim($ref);
        if ($ref === '' || $ref === 'latest') {
            return "origin/{$project->branch}";
        }
        if (preg_match('/^[0-9a-f]{7,40}$/i', $ref)) {
            return $ref;
        }
        $path = $project->deploy_path;
        if ($this->isGitRepo($path)) {
            $exists = $this->git->run(['rev-parse', '--verify', '--quiet', "refs/remotes/origin/{$ref}"], $path);
            if ($exists['exit_code'] === 0) {
                return "origin/{$ref}";
            }
            $tag = $this->git->run(['rev-parse', '--verify', '--quiet', "refs/tags/{$ref}"], $path);
            if ($tag['exit_code'] === 0) {
                return "tags/{$ref}";
            }
        }

        return $ref;
    }

    protected function ensureExists(string $path): void
    {
        if (! is_dir($path)) {
            if (! mkdir($path, 0755, true) && ! is_dir($path)) {
                throw new RuntimeException("ساخت پوشه ممکن نشد: {$path}");
            }
        }
    }

    protected function isEmptyDir(string $path): bool
    {
        $files = array_diff(scandir($path) ?: [], ['.', '..']);

        return $files === [];
    }
    protected function moveCloneIntoPlace(string $path): void
    {
        $tmp = $path.'.tmp-clone';
        if (@rename($tmp, $path)) {
            return;
        }
        $this->copyDir($tmp, $path);
        $this->deleteDir($tmp);
    }

    protected function copyDir(string $src, string $dst): void
    {
        if (! is_dir($dst) && ! mkdir($dst, 0755, true) && ! is_dir($dst)) {
            throw new RuntimeException("ساخت پوشه ممکن نشد: {$dst}");
        }
        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $s = $src.DIRECTORY_SEPARATOR.$item;
            $d = $dst.DIRECTORY_SEPARATOR.$item;
            if (is_dir($s)) {
                $this->copyDir($s, $d);
            } else {
                copy($s, $d);
            }
        }
    }

    protected function deleteDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $p = $dir.DIRECTORY_SEPARATOR.$item;
            is_dir($p) ? $this->deleteDir($p) : @unlink($p);
        }
        @rmdir($dir);
    }

    protected function guardPath(string $path): void
    {
        $forbidden = config('git-deployer.forbidden_paths', []);
        $normalized = rtrim(str_replace('\\', '/', (string) $path), '/');
        foreach ($forbidden as $bad) {
            $badNorm = rtrim(str_replace('\\', '/', (string) $bad), '/');
            if ($badNorm !== '' && ($normalized === $badNorm || str_starts_with($normalized, $badNorm.'/'))) {
                throw new RuntimeException("مسیر مجاز نیست: {$path}");
            }
        }
    }

    public function authenticatedUrl(DeployProject $project): string
    {
        $url = trim($project->repo_url);
        if ($project->auth_type === 'token' && $project->token) {
            $user = $project->username ?: 'oauth2';
            if (preg_match('#^(https?://)(.*)$#i', $url, $m)) {
                return $m[1].rawurlencode($user).':'.rawurlencode($project->token).'@'.$m[2];
            }
        }

        return $url;
    }

    public function gitEnv(DeployProject $project): array
    {
        if ($project->auth_type === 'ssh' && $project->ssh_key_path) {
            return ['GIT_SSH_COMMAND' => "ssh -i {$project->ssh_key_path} -o StrictHostKeyChecking=accept-new"];
        }

        return [];
    }

    protected function markSafe(string $path): void
    {
        if (! config('git-deployer.auto_safe_directory', true)) {
            return;
        }
        try {
            $this->git->run(['config', '--global', '--add', 'safe.directory', $path], $path, [], 30);
        } catch (\Throwable) {
        }
    }
    protected function runPostDeploy(DeployProject $project, string $path): string
    {
        if (! config('git-deployer.allow_post_deploy', true)) {
            return '';
        }
        $outputs = [];
        foreach ($project->postDeployCommands() as $cmd) {
            $outputs[] = $this->runShell((string) $cmd, $path);
        }

        return implode("\n", array_filter($outputs));
    }

    protected function runShell(string $command, string $cwd): string
    {
        $timeout = (int) config('git-deployer.timeout', 300);
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($command, $descriptors, $pipes, $cwd);
        if (! is_resource($process)) {
            throw new RuntimeException("اجرای دستور ممکن نشد: {$command}");
        }
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $start = time();
        do {
            $status = proc_get_status($process);
            if (! $status['running']) {
                $exit = $status['exitcode'];
                proc_close($process);
                if ($exit !== 0) {
                    throw new RuntimeException("خطا در [{$command}]: ".trim($err ?: $out));
                }

                return "$ {$command}\n".trim($out);
            }
            if ((time() - $start) > $timeout) {
                proc_terminate($process, 9);
                proc_close($process);
                throw new RuntimeException("تایم‌اوت در دستور: {$command}");
            }
            sleep(1);
        } while (true);
    }

    protected function afterDeploy(DeployProject $project, ?string $from, string $to, string $event, ?string $actor, string $output): void
    {
        $project->forceFill(['current_commit' => $to, 'last_status' => 'success', 'last_deployed_at' => now()])->save();
        $this->writeLog($project, $event, $from, $to, 'success', null, $output, $actor);
    }

    protected function writeLog(DeployProject $project, string $event, ?string $from, ?string $to, string $status, ?string $message, ?string $output, ?string $actor): void
    {
        if ($project->token) {
            $output = str_replace($project->token, '***', (string) $output);
            $message = str_replace($project->token, '***', (string) $message);
        }
        DeployLog::create([
            'deploy_project_id' => $project->id, 'event' => $event,
            'from_commit' => $from ? substr($from, 0, 40) : null,
            'to_commit' => $to ? substr($to, 0, 40) : null,
            'status' => $status, 'message' => $message, 'output' => $output, 'actor' => $actor,
        ]);
    }

    /**
     * بررسی‌های محیطی برای عیب‌یابی (مخصوص هاست اشتراکی).
     *
     * @return array<int, array{key:string,label:string,ok:bool|null,detail:string}>
     */
    public function diagnose(DeployProject $project): array
    {
        $path = $project->deploy_path;
        $checks = [];

        $procOpen = function_exists('proc_open');
        $checks[] = [
            'key' => 'proc_open', 'label' => 'تابع proc_open',
            'ok' => $procOpen,
            'detail' => $procOpen ? 'فعال است' : 'غیرفعال است؛ از پشتیبانی هاست بخواهید فعالش کند',
        ];

        try {
            $version = $this->git->runOrFail(['--version'], null, [], 30);
            $checks[] = ['key' => 'git', 'label' => 'باینری گیت', 'ok' => true, 'detail' => $version];
        } catch (\Throwable $e) {
            $checks[] = ['key' => 'git', 'label' => 'باینری گیت', 'ok' => false, 'detail' => $e->getMessage()];

            return $checks;
        }

        $exists = is_dir($path);
        $checks[] = [
            'key' => 'path_exists', 'label' => 'وجود پوشه',
            'ok' => $exists,
            'detail' => $exists ? $path : $path.' — پوشه وجود ندارد؛ مسیر deploy_path را بررسی کنید',
        ];

        if ($exists) {
            $writable = is_writable($path);
            $checks[] = [
                'key' => 'path_writable', 'label' => 'دسترسی نوشتن',
                'ok' => $writable,
                'detail' => $writable ? 'قابل نوشتن است' : 'قابل نوشتن نیست؛ سطح دسترسی پوشه را بررسی کنید',
            ];
        }

        $isRepo = $this->isGitRepo($path);
        $checks[] = [
            'key' => 'is_repo', 'label' => 'اتصال گیت (init)',
            'ok' => $isRepo,
            'detail' => $isRepo ? 'متصل است' : 'هنوز init نشده؛ دکمه «اتصال اولیه» را بزنید',
        ];

        if ($isRepo) {
            $remote = $this->try(fn () => $this->git->runOrFail(['remote', 'get-url', 'origin'], $path));
            if (is_string($remote)) {
                $masked = preg_replace('#(https?://[^:]+:)[^@]+@#', '$1***@', $remote);
                $checks[] = ['key' => 'remote', 'label' => 'ریموت origin', 'ok' => true, 'detail' => $masked];
            }
            $branch = $this->try(fn () => $this->git->runOrFail(['rev-parse', '--abbrev-ref', 'HEAD'], $path));
            if (is_string($branch)) {
                $checks[] = ['key' => 'branch', 'label' => 'شاخه فعلی', 'ok' => true, 'detail' => $branch];
            }
        }

        return $checks;
    }

    protected function try(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return null;
        }
    }



}
