<?php

namespace Behin\GitDeployer\Tests;

use Behin\GitDeployer\Services\GitProcess;
use PHPUnit\Framework\TestCase;

class GitProcessTest extends TestCase
{
    public function test_git_binary_runs(): void
    {
        if (! function_exists('config')) {
            function config($key, $default = null)
            {
                return $key === 'git-deployer.git_binary' ? 'git' : $default;
            }
        }

        $git = new GitProcess();
        $result = $git->run(['--version'], null, [], 30);

        $this->assertSame(0, $result['exit_code']);
        $this->assertStringContainsString('git version', $result['output']);
    }
}
