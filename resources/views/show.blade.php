@extends('git-deployer::layout')
@section('title', '- '.$project->name)
@section('content')
<div class="card">
<h2>{{ $project->name }} <span class="badge">{{ $project->branch }}</span></h2>
<p>ریپو: <code>{{ $project->repo_url }}</code></p>
<p>مسیر: <code>{{ $project->deploy_path }}</code></p>
<p>کامیت فعلی: <code>{{ $project->current_commit ? substr($project->current_commit, 0, 8) : '-' }}</code></p>
@if(isset($status['error']))
<p>خطا: {{ $status['error'] }}</p>
@elseif(! ($status['installed'] ?? false))
<p>هنوز به هاست متصل نشده.</p>
@else
<p>
@if($status['up_to_date']) ✅ به‌روز است
@else ⏳ {{ $status['behind'] ?? '?' }} کامیت عقب‌تر از ریموت @endif
@if(! empty($status['dirty'])) | ⚠️ تغییرات محلی دارد @endif
</p>
<p>لوکال: <code>{{ $status['local_commit'] ? substr($status['local_commit'], 0, 8) : '-' }}</code>
ریموت: <code>{{ $status['remote_commit'] ? substr($status['remote_commit'], 0, 8) : '-' }}</code></p>
@endif
<form method="POST" action="{{ route('git-deployer.deploy', $project) }}" style="display:inline">
@csrf
<button class="btn btn-g" type="submit">⬇ دریافت آخرین تغییرات (update)</button>
</form>
<a class="btn btn-p" href="{{ route('git-deployer.edit', $project) }}">ویرایش</a>
</div>

<div class="card">
<h3>بازگشت به کامیت خاص (آپگرید / دان‌گرید)</h3>
<form method="POST" action="{{ route('git-deployer.rollback', $project) }}">
@csrf
<label>هش کامیت یا تگ</label>
<input name="commit" dir="ltr" placeholder="abc1234" required>
<br><br>
<button class="btn btn-o" type="submit" onclick="return confirm('مطمئنی؟')">بازگشت به این نسخه</button>
</form>
</div>

<div class="card">
<h3>تاریخچه ریموت ({{ $project->branch }})</h3>
<table>
<tr><th></th><th>پیام</th><th>نویسنده</th><th>تاریخ</th><th></th></tr>
@forelse($history as $h)
<tr @if($h['current']) style="background:#f0fdf4" @endif>
<td><code>{{ $h['short'] }}</code> @if($h['current'])<span class="badge">فعلی</span>@endif</td>
<td>{{ $h['subject'] }}</td>
<td>{{ $h['author'] }}</td>
<td dir="ltr">{{ $h['date'] }}</td>
<td>
<form method="POST" action="{{ route('git-deployer.rollback', $project) }}">
@csrf
<input type="hidden" name="commit" value="{{ $h['hash'] }}">
<button class="btn btn-o" type="submit" onclick="return confirm('بازگشت به {{ $h['short'] }}؟')">بازگشت</button>
</form>
</td>
</tr>
@empty
<tr><td colspan="5">تاریخچه‌ای نیست (شاید init نشده).</td></tr>
@endforelse
</table>
</div>

<div class="card">
<h3>وب‌هوک دپلوی خودکار</h3>
<p>آدرس: <code>{{ route('git-deployer.webhook', $project) }}?token=RAZ</code></p>
<p>در گیت‌هاب: Settings → Webhooks → Add webhook → آدرس بالا + Content type روی application/json. اگر راز (secret) برای پروژه گذاشتی، همان را در گیت‌هاب هم بگذار تا امضا بررسی شود.</p>
</div>

<div class="card">
<h3>آخرین لاگ‌ها</h3>
<table>
<tr><th>رویداد</th><th>از</th><th>به</th><th>وضعیت</th><th>زمان</th></tr>
@forelse($logs as $l)
<tr>
<td>{{ $l->event }}</td>
<td><code>{{ $l->from_commit ? substr($l->from_commit, 0, 8) : '-' }}</code></td>
<td><code>{{ $l->to_commit ? substr($l->to_commit, 0, 8) : '-' }}</code></td>
<td>{{ $l->status }}</td>
<td>{{ $l->created_at }}</td>
</tr>
@empty
<tr><td colspan="5">لاگی نیست.</td></tr>
@endforelse
</table>
</div>
@endsection
