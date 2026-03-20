<!DOCTYPE html>
<html>
<head>
    <title>新規登録</title>
</head>
<body>
<h2>新規登録</h2>

@if ($errors->any())
    <div>
        @foreach ($errors->all() as $error)
            <p style="color:red;">{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('register') }}">
    @csrf
    <div>
        <label>ニックネーム</label>
        <input type="text" name="nickname" value="" autocomplete="off">
    </div>
    <div>
        <label>パスワード</label>
        <input type="password" name="password" value="" autocomplete="new-password">
    </div>
    <button type="submit">登録</button>
</form>
<a href="{{ route('login') }}">ログインはこちら</a>
</body>
</html>