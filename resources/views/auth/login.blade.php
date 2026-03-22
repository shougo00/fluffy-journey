<!DOCTYPE html>
<html lang="ja">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ログイン</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card shadow-sm w-100" style="max-width: 420px;">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">ログイン</h3>

            <!-- セッションステータス -->
            @if(session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- メールアドレス -->
                <div class="mb-3">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- パスワード -->
                <div class="mb-3">
                    <label for="password" class="form-label">パスワード</label>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- ログイン状態を保持 -->
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember_me" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember_me">
                        ログイン状態を保持する
                    </label>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <!-- パスワードを忘れた場合 -->
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="link-primary small">パスワードを忘れた場合</a>
                    @endif

                    <!-- ログインボタン -->
                    <button type="submit" class="btn btn-primary">ログイン</button>
                </div>

                <hr>

                <!-- 新規登録リンク -->
                <div class="text-center mt-3">
                    <span class="small">アカウントをお持ちでないですか？</span>
                    <a href="{{ route('register') }}" class="btn btn-outline-primary btn-sm ms-2">
                    新規登録
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

