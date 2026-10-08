<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>دپلوی گیت @yield('title')</title>
<style>
body{font-family:Tahoma,sans-serif;background:#f4f6fb;margin:0;color:#222}
.container{max-width:1000px;margin:24px auto;padding:0 16px}
.card{background:#fff;border-radius:10px;padding:20px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.btn{display:inline-block;padding:8px 16px;border-radius:6px;border:0;cursor:pointer;text-decoration:none;font-size:14px}
.btn-p{background:#2563eb;color:#fff}.btn-g{background:#16a34a;color:#fff}.btn-o{background:#ea580c;color:#fff}.btn-r{background:#dc2626;color:#fff}.btn-gr{background:#6b7280;color:#fff}
table{width:100%;border-collapse:collapse;font-size:13px}
th,td{border:1px solid #e5e7eb;padding:8px;text-align:right}
th{background:#f8fafc}
code{direction:ltr;display:inline-block;background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:12px}
.alert{padding:10px;border-radius:6px;margin-bottom:12px}
.alert-s{background:#dcfce7}.alert-e{background:#fee2e2}
input,select,textarea{width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:14px}
label{font-size:13px;font-weight:bold;display:block;margin:12px 0 4px}
.nav{display:flex;gap:12px;margin-bottom:16px}
pre{direction:ltr;text-align:left;background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;overflow:auto;font-size:12px;max-height:300px}
.badge{background:#eef2ff;color:#3730a3;padding:2px 8px;border-radius:20px;font-size:12px}
</style>
</head>
<body>
<div class="container">
<div class="nav">
<a class="btn btn-gr" href="{{ route('git-deployer.index') }}">همه پروژه‌ها</a>
<a class="btn btn-p" href="{{ route('git-deployer.create') }}">+ پروژه جدید</a>
</div>
@if(session('success'))<div class="alert alert-s">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-e">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-e"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
@yield('content')
</div>
</body>
</html>
