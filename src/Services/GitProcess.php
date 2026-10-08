<?php

namespace Behin\GitDeployer\Services;

use RuntimeException;

class GitProcess
{
    /**
     * اجرای یک دستور گیت و برگرداندن خروجی.
     *
     * @param  array  $args  آرگومان‌ها بدون باینری گیت (مثال: ['fetch', '--all'])
     * @return array{exit_code:int, output:string, error:string, command:string}
     */
    public function run(array $args, ?string $cwd = null, array $env = [], int $timeout = 300): array
    {
        $binary = config('git-deployer.git_binary', 'git');
        $command = $this->buildCommand($binary, $args);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $processEnv = array_merge($this->baseEnv(), $env);

        $process = proc_open($command, $descriptors, $pipes, $cwd ?: null, $processEnv);

        if (! is_resource($process)) {
            throw new RuntimeException("اجرای دستور گیت ممکن نشد: {$command}");
        }

        fclose($pipes[0]);

        foreach ([$pipes[1], $pipes[2]] as $pipe) {
            stream_set_blocking($pipe, false);
        }

        $out = '';
        $err = '';
        $start = time();

        do {
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;
            $ready = stream_select($read, $write, $except, 1);

            if ($ready === false) {
                break;
            }

            foreach ($read as $stream) {
                $chunk = stream_get_contents($stream);
                if ($stream === $pipes[1]) {
                    $out .= $chunk;
                } else {
                    $err .= $chunk;
                }
            }

            $status = proc_get_status($process);
            if (! $status['running']) {
                break;
            }

            if ((time() - $start) > $timeout) {
                proc_terminate($process, 9);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);

                throw new RuntimeException("تایم‌اوت اجرای دستور گیت (بیش از {$timeout} ثانیه): {$command}");
            }
        } while (true);

        // باقی‌مانده بافرها
        $out .= stream_get_contents($pipes[1]);
        $err .= stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exit_code' => $exitCode,
            'output' => trim($out),
            'error' => trim($err),
            'command' => $command,
        ];
    }

    /**
     * اجرا با پرتاب استثنا در صورت خطا.
     */
    public function runOrFail(array $args, ?string $cwd = null, array $env = [], ?int $timeout = null): string
    {
        $timeout ??= (int) config('git-deployer.timeout', 300);
        $result = $this->run($args, $cwd, $env, $timeout);

        if ($result['exit_code'] !== 0) {
            $message = $result['error'] !== '' ? $result['error'] : $result['output'];
            throw new RuntimeException("خطای گیت [{$result['command']}]: {$message}");
        }

        return $result['output'];
    }

    protected function buildCommand(string $binary, array $args): string
    {
        $parts = [$this->escape($binary)];
        foreach ($args as $arg) {
            $parts[] = $this->escape((string) $arg);
        }

        return implode(' ', $parts);
    }

    protected function escape(string $value): string
    {
        // escapeshellarg در ویندوز کوتیشن تکی می‌گذارد که با git سازگار است
        if (DIRECTORY_SEPARATOR === '\\') {
            return '"'.str_replace(['"', '%', '!'], ['""', '%%', '^!'], $value).'"';
        }

        return escapeshellarg($value);
    }

    protected function baseEnv(): array
    {
        $env = [];
        foreach (['PATH', 'PATHEXT', 'SystemRoot', 'HOME', 'USERPROFILE', 'LANG', 'LC_ALL'] as $key) {
            $value = getenv($key);
            if ($value !== false) {
                $env[$key] = $value;
            }
        }
        $env['GIT_TERMINAL_PROMPT'] = '0';

        return $env;
    }
}
