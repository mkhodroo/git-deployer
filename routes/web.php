<?php

use Behin\GitDeployer\Http\Controllers\DeployProjectController;
use Behin\GitDeployer\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('git-deployer.route_prefix', 'git-deployer'),
    'middleware' => config('git-deployer.middleware', ['web', 'auth']),
    'as' => 'git-deployer.',
], function () {
    Route::get('/', [DeployProjectController::class, 'index'])->name('index');
    Route::get('/create', [DeployProjectController::class, 'create'])->name('create');
    Route::post('/', [DeployProjectController::class, 'store'])->name('store');
    Route::get('/{project}', [DeployProjectController::class, 'show'])->name('show');
    Route::get('/{project}/edit', [DeployProjectController::class, 'edit'])->name('edit');
    Route::put('/{project}', [DeployProjectController::class, 'update'])->name('update');
    Route::delete('/{project}', [DeployProjectController::class, 'destroy'])->name('destroy');
    Route::post('/{project}/deploy', [DeployProjectController::class, 'deploy'])->name('deploy');
    Route::post('/{project}/rollback', [DeployProjectController::class, 'rollback'])->name('rollback');
});

Route::group([
    'prefix' => config('git-deployer.route_prefix', 'git-deployer'),
    'middleware' => config('git-deployer.webhook_middleware', ['api']),
    'as' => 'git-deployer.',
], function () {
    Route::post('/webhook/{project}', [WebhookController::class, 'handle'])->name('webhook');
});
