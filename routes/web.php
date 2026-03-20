<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AvatarController;

Route::get('/', function () {
    return view('welcome');
});




// 1️⃣ ダッシュボードルート（ログイン後のホーム）
Route::middleware([ 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard'); // resources/views/dashboard.blade.php 必須
    })->name('dashboard');

    // 2️⃣ プロフィール関連
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 3️⃣ ユーザ管理画面
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::middleware(['auth'])->get('/home', function () {
        $news = \App\Models\News::where('is_published', true)
            ->latest()
            ->take(5)
            ->get();

        return view('home', compact('news'));
    })->name('home');

    Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::resource('news', \App\Http\Controllers\Admin\NewsController::class);

    Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/users/status',
            [\App\Http\Controllers\Admin\UserStatusController::class, 'index']
        )->name('users.status.index');

        Route::get('/users/{user}/status/edit',
            [\App\Http\Controllers\Admin\UserStatusController::class, 'edit']
        )->name('users.status.edit');

        Route::put('/users/{user}/status',
            [\App\Http\Controllers\Admin\UserStatusController::class, 'update']
        )->name('users.status.update');

        // ★ これを追加
        Route::post('/users/{user}/exp',
            [\App\Http\Controllers\Admin\UserStatusController::class, 'addExp']
        )->name('users.exp.add');
    });



    // 学びクエスト
    Route::get('/quest', [QuestController::class, 'index'])->name('quest.index');

    // ゲーム
    Route::get('/game', [GameController::class, 'index'])->name('game.index');

    // 作業依頼
    Route::get('/task', [TaskController::class, 'index'])->name('task.index');

    // 設定
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');


   
 
});
   // routes/web.php
    Route::get('/avatar', [AvatarController::class,'show'])->name('avatar.show');
    Route::get('/avatar/edit', [AvatarController::class,'edit'])->name('avatar.edit');
    Route::post('/avatar/update', [AvatarController::class,'update'])->name('avatar.update');


});



// 4️⃣ 認証ルート
require __DIR__.'/auth.php';
