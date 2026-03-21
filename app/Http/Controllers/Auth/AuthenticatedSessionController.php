<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;


class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    
   public function create(Request $request): View
{
    // 古いセッション破棄（スマホで閉じても安全）
    $request->session()->invalidate();      // 必要なら flush() に置き換え
    $request->session()->regenerateToken(); // 新しいCSRFトークンを発行

    return view('auth.login');
}
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

      
        // // 管理者の場合
        // if ($user->is_admin) {
        //     return redirect()->route('dashboard');
        // }

        // 一般ユーザーの場合
        return redirect()->route('home'); // 好きな画面に変更OK
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login'); // ← ここが重要
    }



}
