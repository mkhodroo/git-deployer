<?php

namespace Behin\GitDeployer\Http\Controllers;

use Behin\GitDeployer\Models\DeployProject;
use Behin\GitDeployer\Services\GitDeployer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function handle(Request $request, DeployProject $project, GitDeployer $deployer)
    {
        if (! $this->authorized($request, $project)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // فقط push روی شاخه هدف (اگر اطلاعات گیت‌هاب موجود باشد)
        $ref = $request->input('ref', '');
        if ($ref !== '' && $ref !== 'refs/heads/'.$project->branch) {
            return response()->json(['message' => 'Ignored (branch mismatch)', 'ref' => $ref]);
        }

        $autoDeploy = $project->auto_deploy;
        $force = $request->boolean('deploy', false) || $request->query('deploy') === '1';
        if (! $autoDeploy && ! $force) {
            return response()->json(['message' => 'auto_deploy is off; add ?deploy=1 to force']);
        }

        $actor = 'webhook:'.($request->input('pusher.name') ?: $request->ip());

        try {
            $result = $deployer->update($project, null, $actor, true);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'deployed',
            'from' => $result['from'],
            'to' => $result['to'],
        ]);
    }

    protected function authorized(Request $request, DeployProject $project): bool
    {
        $secret = $project->webhookSecret();

        // ۱) توکن در query یا هدر
        $token = $request->query('token') ?? $request->header('X-Webhook-Secret');
        if ($secret && $token && hash_equals((string) $secret, (string) $token)) {
            return true;
        }

        // ۲) امضای گیت‌هاب
        $signature = $request->header('X-Hub-Signature-256', '');
        if ($secret && $signature && config('git-deployer.verify_github_signature', true)) {
            $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        // ۳) اگر هیچ رازی تنظیم نشده و درخواست از پنل با deploy=1 نیست، قبول نکن
        return false;
    }
}
