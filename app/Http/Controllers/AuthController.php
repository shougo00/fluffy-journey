<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // 新規登録画面
    public function showRegister()
    {
        return view('auth.register');
    }



    // 新規登録処理
    public function register(Request $request)
    {
        $request->validate([
            'nickname' => 'required|unique:users,nickname',
            'password' => 'required|min:6',
        ]);

        User::create([
            'nickname' => $request->nickname,
            'password' => Hash::make($request->password), // ←ハッシュ化
            'group_code' => 0,  // とりあえず空
            'is_host' => 0,     // とりあえず空
        ]);

        return redirect()->route('login')->with('success', '登録完了しました');
    }

    // ログイン画面
    public function showLogin()
    {
        return view('auth.login');
    }

        public function login(Request $request)
    {
        // バリデーション
        $request->validate([
            'nickname' => 'required',
            'password' => 'required',
        ]);

        // ここに書く → nickname と password でログインを試みる
        if (Auth::attempt([
            'nickname' => $request->nickname,
            'password' => $request->password
        ])) {
            // ログイン成功
            $request->session()->regenerate();

            // グループ選択ページにリダイレクト
            return redirect()->route('group.select');  
        }

        // ログイン失敗
        return back()->withErrors([
            'nickname' => 'ニックネームまたはパスワードが間違っています',
        ]);
    }

    // ログアウト
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
