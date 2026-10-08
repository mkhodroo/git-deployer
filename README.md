# Git Deployer — behin/git-deployer

پکیج لاراول برای اتصال یک **ریپازیتوری گیت** به یک **دایرکتوری روی هاست**؛ دقیقاً مشابه کار با گیت:
آخرین تغییرات را بگیر، روی دایرکتوری بریز، تاریخچه ببین و هر وقت خواستی به هر **کامیت** برگرد (آپگرید / دان‌گرید).

## امکانات

- تعریف چند پروژه (هر پروژه: ریپو + شاخه + مسیر هاست + احراز هویت)
- اتصال اولیه (`init`: clone به مسیر دلخواه)
- آپدیت به آخرین کامیت شاخه (`update`)
- بازگشت به هر کامیت/تگ (`rollback`)
- وضعیت (`status`: چند کامیت عقب است؟) و تاریخچه (`history`)
- دستورات پس از دپلوی (مثل `php artisan migrate --force`) — هر خط یک دستور
- لاگ کامل هر عملیات در دیتابیس (از کجا به کجا، موفق/ناموفق، خروجی)
- پنل وب فارسی (لیست، ساخت، مدیریت، دکمه دپلوی، دکمه بازگشت کنار هر کامیت)
- وب‌هوک گیت‌هاب برای دپلوی خودکار (توکن + امضای `X-Hub-Signature-256`)
- احراز هویت `token` (ریپوی خصوصی https) و `ssh` (کلید خصوصی)

## نصب

```bash
composer require behin/git-deployer
php artisan migrate
```

انتشار کانفیگ (اختیاری):

```bash
php artisan vendor:publish --tag=git-deployer-config
```

## دستورات Artisan

```bash
# اتصال اولیه ریپو به مسیر هاست (تعاملی یا با آپشن)
php artisan deployer:init mysite --repo=https://github.com/user/repo.git --branch=main --path=/home/user/public_html

# لیست پروژه‌ها
php artisan deployer:list

# وضعیت (عقب‌ماندگی از ریموت)
php artisan deployer:status mysite

# تاریخچه کامیت‌های ریموت
php artisan deployer:history mysite --limit=20

# گرفتن آخرین تغییرات و ریختن روی دایرکتوری
php artisan deployer:update mysite

# آپدیت به کامیت/تگ خاص
php artisan deployer:update mysite --ref=v1.2.0

# بازگشت (دان‌گرید/آپگرید) به کامیت مشخص
php artisan deployer:rollback mysite abc1234
```

## پنل وب

پیش‌فرض: `/git-deployer` (با میدل‌ور `web,auth` — در کانفیگ قابل تغییر).

- ساخت پروژه با فرم فارسی (ریپو، شاخه، مسیر، توکن/SSH، دستورات پس از دپلوی، راز وب‌هوک)
- صفحه هر پروژه: وضعیت به‌روزی، دکمه «دریافت آخرین تغییرات»، فرم «بازگشت به کامیت»، جدول تاریخچه با دکمه بازگشت کنار هر کامیت، آدرس وب‌هوک، لاگ‌ها

## وب‌هوک (دپلوی خودکار)

آدرس هر پروژه در صفحه‌اش نوشته شده:

```
POST /git-deployer/webhook/{project}?token=RAZ
```

- در گیت‌هاب: Settings → Webhooks → Add webhook → آدرس بالا، Content type: `application/json`
- اگر برای پروژه `webhook_secret` بگذاری، همان را به‌عنوان Secret گیت‌هاب هم بگذار تا امضا بررسی شود.
- وب‌هوک فقط شاخه هدف پروژه را دپلوی می‌کند؛ اگر `auto_deploy` خاموش باشد، `?deploy=1` لازم است.
- برای امنیت بیشتر می‌توانی به‌جای توکن در URL از هدر `X-Webhook-Secret` استفاده کنی.

## کانفیگ

| کلید | توضیح | پیش‌فرض |
| --- | --- | --- |
| `route_prefix` | پیشوند مسیرهای پنل و وب‌هوک | `git-deployer` |
| `middleware` | میدل‌ور پنل | `['web','auth']` |
| `webhook_middleware` | میدل‌ور وب‌هوک | `['api']` |
| `git_binary` | مسیر باینری گیت | `git` |
| `timeout` | تایم‌اوت دستورات (ثانیه) | `300` |
| `default_branch` | شاخه پیش‌فرض | `main` |
| `history_limit` | تعداد کامیت پنل | `20` |
| `webhook_secret` | راز سراسری وب‌هوک | `null` |
| `post_deploy` | دستورات سراسری پس از دپلوی | `[]` |
| `allow_post_deploy` | اجازه اجرای post-deploy | `true` |
| `auto_safe_directory` | افزودن خودکار safe.directory | `true` |
| `forbidden_paths` | مسیرهای ممنوعه | `/, /etc, ...` |

## نکات مهم

- پوشه مقصد برای `init` باید خالی باشد یا از قبل همان ریپو باشد؛ وگرنه برای جلوگیری از حذف فایل، خطا می‌دهد.
- `update` و `rollback` با `checkout -f` + `reset --hard` کار می‌کنند؛ یعنی تغییرات محلیِ ذخیره‌نشده روی هاست **بازنویسی** می‌شود (رفتار استاندارد دپلوی).
- توکن در لاگ‌ها با `***` ماسک می‌شود و در دیتابیس با cast رمزنگاری (`encrypted`) ذخیره می‌شود.
- روی هاست اشتراکی مطمئن شو `git` و تابع `proc_open` فعال باشند.
