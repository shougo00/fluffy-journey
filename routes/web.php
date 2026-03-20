<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
Route::get('/', function () {
    return view('welcome');
});




Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ログイン必須のページ
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return 'ログイン成功！ここにダッシュボードを作る';
    });
});

//グループ選択画面
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard'); // ダッシュボードはグループ選択ページに変更
    })->name('dashboard');

    Route::get('/group/select', function () {
        return view('group_select'); // グループ選択ページ
    })->name('group.select');
});