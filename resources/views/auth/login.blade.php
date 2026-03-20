<!DOCTYPE html>
<html>
<head>
    <title>ログイン</title>
</head>
<body>
<h2>ログイン</h2>

@if ($errors->any())
    <div>
        @foreach ($errors->all() as $error)
            <p style="color:red;">{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf
    <div>
        <label>ニックネーム</label>
        <input type="text" name="nickname" value="{{ old('nickname') }}" autocomplete="username">
    </div>
    <div>
        <label>パスワード</label>
        <input type="password" name="password" autocomplete="current-password">
    </div>
    
    <button type="submit">ログイン</button>
</form>
<a href="{{ route('register') }}">新規登録はこちら</a>
</body>
</html>