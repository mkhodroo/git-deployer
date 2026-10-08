<?php

namespace Behin\GitDeployer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeployLog extends Model
{
    protected $table = 'deploy_logs';

    protected $fillable = [
        'deploy_project_id', 'event', 'from_commit', 'to_commit',
        'status', 'message', 'output', 'actor',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(DeployProject::class, 'deploy_project_id');
    }
}
