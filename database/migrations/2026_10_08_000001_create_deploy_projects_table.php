<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deploy_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('repo_url');
            $table->string('branch', 100)->default('main');
            $table->text('deploy_path');
            $table->string('current_commit', 64)->nullable();
            $table->string('auth_type', 20)->default('none'); // none|token|ssh
            $table->string('username')->nullable();
            $table->text('token')->nullable(); // با cast رمزنگاری می‌شود
            $table->text('ssh_key_path')->nullable();
            $table->json('post_deploy')->nullable();
            $table->string('webhook_secret', 255)->nullable();
            $table->boolean('auto_deploy')->default(false);
            $table->string('last_status', 32)->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deploy_projects');
    }
};
