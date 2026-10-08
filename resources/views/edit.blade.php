@extends('git-deployer::layout')
@section('title', '- ویرایش '.$project->name)
@section('content')
<div class="card">
<h2>ویرایش {{ $project->name }}</h2>
<form method="POST" action="{{ route('git-deployer.update', $project) }}">
@csrf
@method('PUT')
<label>آدرس ریپازیتوری</label>
<input name="repo_url" dir="ltr" value="{{ old('repo_url', $project->repo_url) }}" required>
<label>شاخه</label>
<input name="branch" dir="ltr" value="{{ old('branch', $project->branch) }}">
<label>مسیر دایرکتوری روی هاست</label>
<input name="deploy_path" dir="ltr" value="{{ old('deploy_path', $project->deploy_path) }}" required>
<label>نوع احراز هویت</label>
<select name="auth_type">
@foreach(['none' => 'بدون احراز هویت', 'token' => 'توکن', 'ssh' => 'کلید SSH'] as $k => $v)
<option value="{{ $k }}" @selected(old('auth_type', $project->auth_type) === $k)>{{ $v }}</option>
@endforeach
</select>
<label>نام کاربری</label>
<input name="username" dir="ltr" value="{{ old('username', $project->username) }}">
<label>توکن جدید (خالی = حفظ قبلی)</label>
<input name="token" dir="ltr" value="">
<label>مسیر کلید SSH</label>
<input name="ssh_key_path" dir="ltr" value="{{ old('ssh_key_path', $project->ssh_key_path) }}">
<label>دستورات پس از دپلوی (هر خط یک دستور)</label>
<textarea name="post_deploy" rows="3" dir="ltr">{{ old('post_deploy', is_array($project->post_deploy) ? implode("\n", $project->post_deploy) : '') }}</textarea>
<label>راز وب‌هوک</label>
<input name="webhook_secret" dir="ltr" value="{{ old('webhook_secret', $project->webhook_secret) }}">
<label><input type="checkbox" name="auto_deploy" value="1" @checked(old('auto_deploy', $project->auto_deploy)) style="width:auto"> دپلوی خودکار با وب‌هوک</label>
<br><br>
<button class="btn btn-g" type="submit">ذخیره</button>
</form>
<br>
<form method="POST" action="{{ route('git-deployer.destroy', $project) }}" onsubmit="return confirm('حذف پروژه؟ (فایل‌های هاست دست نمی‌خورد)') ">
@csrf
@method('DELETE')
<button class="btn btn-r" type="submit">حذف پروژه</button>
</form>
</div>
@endsection
