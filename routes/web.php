<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\VideoController;

Route::get('/', function () {
    return redirect('/login');
});



// 1️⃣ ダッシュボードルート（ログイン後のホーム）
Route::middleware([ 'verified'])->group(function () {
    
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

    Route::resource('news', NewsController::class)->except(['show']);

    // ホーム画面に変更
    Route::get('/home', [RecordController::class, 'index'])->name('home');
    // 立追加はそのまま
    Route::post('/records', [RecordController::class, 'store'])->name('records.store');
    Route::post('/shots/{id}', [RecordController::class, 'updateShot'])->name('shots.update');
    Route::delete('/records/{record}', [RecordController::class, 'destroy'])->name('records.destroy');
    Route::get('/dashboard', [RecordController::class, 'dashboard'])->name('dashboard');

   // routes/web.php
    Route::get('/avatar', [AvatarController::class,'show'])->name('avatar.show');
    Route::get('/avatar/edit', [AvatarController::class,'edit'])->name('avatar.edit');
    Route::post('/avatar/update', [AvatarController::class,'update'])->name('avatar.update');


    Route::get('/camera', function () {
    return view('camera');
    })->name('camera');

    Route::post('/video/upload', [VideoController::class, 'upload']);
    Route::get('/video/list', [VideoController::class, 'list']);

});



// 4️⃣ 認証ルート
require __DIR__.'/auth.php';
