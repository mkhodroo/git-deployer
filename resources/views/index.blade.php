@extends('git-deployer::layout')
@section('title', '- پروژه‌ها')
@section('content')
<div class="card">
<h2>پروژه‌های دپلوی</h2>
<table>
<tr><th>نام</th><th>شاخه</th><th>مسیر</th><th>کامیت فعلی</th><th>وضعیت</th><th>عملیات</th></tr>
@forelse($projects as $p)
<tr>
<td><a href="{{ route('git-deployer.show', $p) }}">{{ $p->name }}</a></td>
<td>{{ $p->branch }}</td>
<td><code>{{ $p->deploy_path }}</code></td>
<td><code>{{ $p->current_commit ? substr($p->current_commit, 0, 8) : '-' }}</code></td>
<td>{{ $p->last_status ?? '-' }}</td>
<td><a class="btn btn-p" href="{{ route('git-deployer.show', $p) }}">مدیریت</a></td>
</tr>
@empty
<tr><td colspan="6">پروژه‌ای ثبت نشده است.</td></tr>
@endforelse
</table>
</div>
@endsection
