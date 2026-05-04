<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録</title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 col-lg-5">

            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h4 class="text-center mb-4">新規ユーザー登録</h4>

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <!-- 名前 -->
                        <div class="mb-3">
                            <label class="form-label">ニックネーム</label>
                            <input type="text"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- メール -->
                        <div class="mb-3">
                            <label class="form-label">メールアドレス ※学校のメール
                            </label>
                            <input type="email"
                                   name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- パスワード -->
                        <div class="mb-3">
                            <label class="form-label">パスワード</label>
                            <input type="password"
                                   name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 確認 -->
                        <div class="mb-3">
                            <label class="form-label">パスワード（確認）</label>
                            <input type="password"
                                   name="password_confirmation"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="mb-3">
                        <label class="form-label">試合区分</label>

                        <div>
                            <label class="me-3">
                                <input type="radio" name="gender" value="male"
                                    {{ old('gender') == 'male' ? 'checked' : '' }} required>
                                男子の部
                            </label>

                            <label>
                                <input type="radio" name="gender" value="female"
                                    {{ old('gender') == 'female' ? 'checked' : '' }}>
                                女子の部
                            </label>
                        </div>

                        @error('gender')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-primary">
                                登録する
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <a href="{{ route('login') }}" class="text-decoration-none">
                                すでにアカウントをお持ちですか？
                            </a>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
