<?php

namespace Behin\GitDeployer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeployProject extends Model
{
    protected $table = 'deploy_projects';

    protected $fillable = [
        'name', 'repo_url', 'branch', 'deploy_path', 'current_commit',
        'auth_type', 'username', 'token', 'ssh_key_path',
        'post_deploy', 'webhook_secret', 'auto_deploy', 'last_status', 'last_deployed_at',
    ];

    protected $casts = [
        'post_deploy' => 'array',
        'auto_deploy' => 'boolean',
        'last_deployed_at' => 'datetime',
        'token' => 'encrypted',
    ];

    protected $hidden = ['token', 'webhook_secret'];

    public function logs(): HasMany
    {
        return $this->hasMany(DeployLog::class, 'deploy_project_id')->latest();
    }

    public function postDeployCommands(): array
    {
        if (is_array($this->post_deploy) && $this->post_deploy !== []) {
            return array_values(array_filter($this->post_deploy));
        }

        return config('git-deployer.post_deploy', []);
    }

    public function webhookSecret(): ?string
    {
        return $this->webhook_secret ?: config('git-deployer.webhook_secret');
    }
}
