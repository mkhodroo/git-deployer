<?php

namespace Behin\GitDeployer\Http\Controllers;

use Behin\GitDeployer\Models\DeployProject;
use Behin\GitDeployer\Services\GitDeployer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DeployProjectController extends Controller
{
    public function index()
    {
        $projects = DeployProject::orderBy('name')->get();

        return view('git-deployer::index', compact('projects'));
    }

    public function create()
    {
        return view('git-deployer::create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if (($data['auth_type'] ?? 'none') === 'token' && empty($data['token'])) {
            return back()->withErrors(['token' => 'توکن الزامی است.'])->withInput();
        }
        $project = DeployProject::create($data);

        return redirect()->route('git-deployer.show', $project)->with('success', 'پروژه ساخته شد. حالا «اتصال اولیه» را بزنید.');
    }

    public function show(DeployProject $project, GitDeployer $deployer)
    {
        try {
            $status = $deployer->status($project);
        } catch (\Throwable $e) {
            $status = ['installed' => false, 'error' => $e->getMessage()];
        }

        try {
            $history = $status['installed'] ?? false
                ? $deployer->history($project, (int) config('git-deployer.history_limit', 20))
                : [];
        } catch (\Throwable) {
            $history = [];
        }

        $logs = $project->logs()->limit(20)->get();

        return view('git-deployer::show', compact('project', 'status', 'history', 'logs'));
    }

    public function edit(DeployProject $project)
    {
        return view('git-deployer::edit', compact('project'));
    }

    public function update(Request $request, DeployProject $project)
    {
        $data = $this->validated($request, true);
        // اگر توکن جدید وارد نشد، قبلی حفظ شود
        if (($data['auth_type'] ?? 'none') === 'token' && empty($data['token'])) {
            unset($data['token']);
        }
        $project->update($data);

        return redirect()->route('git-deployer.show', $project)->with('success', 'پروژه به‌روزرسانی شد.');
    }

    public function destroy(DeployProject $project)
    {
        $project->delete();

        return redirect()->route('git-deployer.index')->with('success', 'پروژه حذف شد (فایل‌های روی هاست دست نخورد).');
    }

    public function deploy(DeployProject $project, GitDeployer $deployer)
    {
        $ref = request('ref') ?: null;

        try {
            $result = $deployer->update($project, $ref, $this->actor(), true);
            $msg = 'دپلوی موفق به '.substr($result['to'], 0, 8);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('git-deployer.show', $project)->with('success', $msg);
    }

    public function rollback(DeployProject $project, GitDeployer $deployer, Request $request)
    {
        $request->validate(['commit' => 'required|string|max:100']);

        try {
            $result = $deployer->rollback($project, $request->input('commit'), $this->actor(), true);
            $msg = 'بازگشت موفق به '.substr($result['to'], 0, 8);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('git-deployer.show', $project)->with('success', $msg);
    }

    protected function validated(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'name' => ($isUpdate ? 'sometimes' : 'required').'|string|max:100|unique:deploy_projects,name'.($isUpdate ? ','.request()->route('project')->id : ''),
            'repo_url' => 'required|string|max:2000',
            'branch' => 'nullable|string|max:100',
            'deploy_path' => 'required|string|max:2000',
            'auth_type' => 'nullable|in:none,token,ssh',
            'username' => 'nullable|string|max:255',
            'token' => 'nullable|string|max:2000',
            'ssh_key_path' => 'nullable|string|max:2000',
            'post_deploy' => 'nullable|string|max:5000',
            'webhook_secret' => 'nullable|string|max:255',
            'auto_deploy' => 'nullable|boolean',
        ];

        $data = $request->validate($rules, [], [
            'name' => 'نام', 'repo_url' => 'آدرس ریپازیتوری', 'branch' => 'شاخه',
            'deploy_path' => 'مسیر دپلوی', 'token' => 'توکن',
        ]);

        $data['branch'] = $data['branch'] ?: config('git-deployer.default_branch', 'main');
        $data['auth_type'] = $data['auth_type'] ?: 'none';
        $data['auto_deploy'] = $request->boolean('auto_deploy');

        // دستورات پس از دپلوی: هر خط یک دستور
        if (isset($data['post_deploy']) && is_string($data['post_deploy'])) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $data['post_deploy']))));
            $data['post_deploy'] = $lines ?: null;
        }

        return $data;
    }

    protected function actor(): ?string
    {
        try {
            return 'web:'.(auth()->user()?->getAuthIdentifier() ?? auth()->id() ?? 'guest');
        } catch (\Throwable) {
            return 'web';
        }
    }
}
