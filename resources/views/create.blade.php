@extends('git-deployer::layout')
@section('title', '- پروژه جدید')
@section('content')
<div class="card">
<h2>پروژه جدید</h2>
<form method="POST" action="{{ route('git-deployer.store') }}">
@csrf
<label>نام (انگلیسی، یکتا)</label>
<input name="name" value="{{ old('name') }}" required>
<label>آدرس ریپازیتوری (repo_url)</label>
<input name="repo_url" dir="ltr" value="{{ old('repo_url') }}" placeholder="https://github.com/user/repo.git" required>
<label>شاخه</label>
<input name="branch" dir="ltr" value="{{ old('branch', 'main') }}">
<label>مسیر دایرکتوری روی هاست (deploy_path)</label>
<input name="deploy_path" dir="ltr" value="{{ old('deploy_path') }}" placeholder="/home/user/public_html یا C:\wamp64\www\site" required>
<label>نوع احراز هویت</label>
<select name="auth_type">
<option value="none">بدون احراز هویت (ریپوی عمومی)</option>
<option value="token">توکن (ریپوی خصوصی https)</option>
<option value="ssh">کلید SSH</option>
</select>
<label>نام کاربری (برای token، اختیاری)</label>
<input name="username" dir="ltr" value="{{ old('username') }}">
<label>توکن (برای ریپوی خصوصی)</label>
<input name="token" dir="ltr" value="{{ old('token') }}">
<label>مسیر کلید SSH خصوصی (برای ssh)</label>
<input name="ssh_key_path" dir="ltr" value="{{ old('ssh_key_path') }}">
<label>دستورات پس از دپلوی (هر خط یک دستور، اجرا در پوشه پروژه)</label>
<textarea name="post_deploy" rows="3" dir="ltr">{{ old('post_deploy') }}</textarea>
<label>راز وب‌هوک (اختیاری)</label>
<input name="webhook_secret" dir="ltr" value="{{ old('webhook_secret') }}">
<label><input type="checkbox" name="auto_deploy" value="1" style="width:auto"> دپلوی خودکار با وب‌هوک</label>
<br><br>
<button class="btn btn-g" type="submit">ذخیره</button>
</form>
</div>
@endsection
