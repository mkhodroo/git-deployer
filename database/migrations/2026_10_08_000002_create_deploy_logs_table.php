<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deploy_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deploy_project_id')->constrained('deploy_projects')->cascadeOnDelete();
            $table->string('event', 32)->default('deploy'); // init|deploy|rollback|webhook
            $table->string('from_commit', 64)->nullable();
            $table->string('to_commit', 64)->nullable();
            $table->string('status', 32)->default('success'); // success|failed
            $table->text('message')->nullable();
            $table->longText('output')->nullable();
            $table->string('actor')->nullable(); // کاربر/فرستنده وب‌هوک
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deploy_logs');
    }
};
