<?php

use App\Http\Controllers\AntispamController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DnsController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/stats', StatsController::class)->name('stats');
    Route::get('/queue', [QueueController::class, 'index'])->name('queue.index');
    Route::post('/queue/cancel', [QueueController::class, 'cancel'])->name('queue.cancel');
    Route::post('/queue/bulk-cancel', [QueueController::class, 'bulkCancel'])->name('queue.bulkCancel');
    Route::post('/queue/flush', [QueueController::class, 'flush'])->name('queue.flush');
    Route::post('/queue/clear', [QueueController::class, 'clear'])->name('queue.clear');
    Route::get('/lists', [ListController::class, 'index'])->name('lists.index');
    Route::post('/lists/{table}', [ListController::class, 'store'])->name('lists.store');
    Route::post('/lists/{table}/import', [ListController::class, 'import'])->name('lists.import');
    Route::get('/lists/{table}/export', [ListController::class, 'export'])->name('lists.export');
    Route::patch('/lists/{table}/{id}/toggle', [ListController::class, 'toggle'])->name('lists.toggle');
    Route::delete('/lists/{table}/{id}', [ListController::class, 'destroy'])->name('lists.destroy');
    Route::get('/dns', [DnsController::class, 'index'])->name('dns.index');
    Route::post('/dns', [DnsController::class, 'check'])->name('dns.check');
    Route::get('/antispam', AntispamController::class)->name('antispam');
    Route::get('/logs', LogsController::class)->name('logs');
    Route::get('/system', SystemController::class)->name('system');
    Route::get('/audit', AuditController::class)->name('audit');
});
