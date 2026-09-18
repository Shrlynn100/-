<?php

use App\Http\Controllers\AuctionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GuildMemberController;
use App\Http\Controllers\WoeController;
use Illuminate\Support\Facades\Route;

// Main auction view
Route::get('/', [AuctionController::class, 'index'])->name('auction.index');

// Guild members view (80 slots)
Route::get('/members', [GuildMemberController::class, 'index'])->name('members.index');

// WoE (War of Emperium) team composition
Route::get('/woe', [WoeController::class, 'index'])->name('woe.index');

// Public API endpoints
Route::get('/api/category/{slug}', [AuctionController::class, 'getCategoryData'])->name('api.category.data');
Route::get('/api/category/{slug}/export', [AuctionController::class, 'exportSummary'])->name('api.category.export');
Route::get('/api/members/export', [GuildMemberController::class, 'export'])->name('api.members.export');
Route::get('/api/admin/status', [AuthController::class, 'status'])->name('admin.status');

// Admin authentication
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

// Admin actions (Protected by session in controller)
Route::post('/api/items/sync-date', [AuctionController::class, 'syncDate'])->name('api.items.sync-date');
Route::post('/api/items/{id}', [AuctionController::class, 'updateItem'])->name('api.item.update');
Route::post('/api/category/{slug}/reset', [AuctionController::class, 'resetCategory'])->name('api.category.reset');
Route::post('/api/members/reset', [GuildMemberController::class, 'reset'])->name('api.members.reset');
Route::post('/api/members/{id}', [GuildMemberController::class, 'update'])->name('api.members.update');
Route::post('/admin/change-password', [AuthController::class, 'changePassword'])->name('admin.change-password');

// WoE Admin actions
Route::post('/api/woe/{id}', [WoeController::class, 'updateSlot'])->name('api.woe.update');
Route::post('/api/woe/map/{mapSlug}/reset', [WoeController::class, 'resetMap'])->name('api.woe.reset');

// Outcome setting action
Route::post('/api/settings/outcome', [AuctionController::class, 'saveOutcome'])->name('api.settings.outcome');

// Full auction overview data endpoint
Route::get('/api/auction/overview', [AuctionController::class, 'getOverviewData'])->name('api.auction.overview');

