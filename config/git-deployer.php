<?php

return [

    // پیشوند مسیرهای پنل وب
    'route_prefix' => 'git-deployer',

    // میدل‌ورهای پنل (وب‌هوک از این مستثنی است و میدل‌ور جدا دارد)
    'middleware' => ['web', 'auth'],

    // میدل‌ور وب‌هوک (معمولاً فقط api یا خالی؛ امضای گیت‌هاب/توکن بررسی می‌شود)
    'webhook_middleware' => ['api'],

    // مسیر باینری گیت
    'git_binary' => env('GIT_DEPLOYER_BINARY', 'git'),

    // تایم‌اوت اجرای دستورات گیت (ثانیه)
    'timeout' => env('GIT_DEPLOYER_TIMEOUT', 300),

    // شاخه پیش‌فرض وقتی پروژه شاخه ندارد
    'default_branch' => env('GIT_DEPLOYER_BRANCH', 'main'),

    // تعداد کامیت نمایشی در پنل و دستور history
    'history_limit' => 20,

    // راز سراسری وب‌هوک (اگر پروژه راز اختصاصی نداشته باشد از این استفاده می‌شود)
    // با ?token=xxx یا هدر X-Webhook-Secret ارسال شود
    'webhook_secret' => env('GIT_DEPLOYER_WEBHOOK_SECRET', null),

    // بررسی امضای گیت‌هاب (X-Hub-Signature-256) با همین راز
    'verify_github_signature' => true,

    // دستورات پس از دپلوی موفق (پیش‌فرض سراسری؛ هر پروژه می‌تواند بازنویسی کند)
    // مثال: ['php artisan migrate --force', 'php artisan config:clear']
    'post_deploy' => [],

    // اجازه اجرای دستورات پس از دپلوی از پنل/کامند (اگر false فقط fetch/checkout انجام می‌شود)
    'allow_post_deploy' => true,

    // افزودن خودکار safe.directory برای مسیرهای دپلوی (رفع خطای dubious ownership)
    'auto_safe_directory' => true,

    // مسیرهایی که اجازه دپلوی در آن‌ها نیست (امنیت)
    'forbidden_paths' => ['/', '/etc', '/root', 'C:\\', 'C:\\Windows'],
];
