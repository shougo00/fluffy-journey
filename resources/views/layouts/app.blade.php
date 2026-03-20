<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>FamLevel</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Optional: Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container">
    <!-- 左：ロゴ -->
    <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
      <i class="bi bi-journal-bookmark-fill text-primary fs-3 me-2"></i>
      <span class="fw-bold text-dark">FamLevel管理</span>
    </a>

    <!-- モバイルハンバーガー -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
        aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <!-- 中央リンク + 右ユーザー -->
    <div class="collapse navbar-collapse" id="navbarContent">
      <!-- 中央リンク -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link active" href="{{ route('dashboard') }}">ホーム</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('users.index') }}">ユーザ一覧</a>
        </li>
      </ul>

      <!-- 右：ユーザー名ドロップダウン -->
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        @auth
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
               data-bs-toggle="dropdown" aria-expanded="false">
              {{ Auth::user()->name }}
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="{{ route('profile.edit') }}">プロフィール</a></li>
              <li>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button class="dropdown-item" type="submit">ログアウト</button>
                </form>
              </li>
            </ul>
          </li>
        @else
          <li class="nav-item">
            <a class="nav-link" href="{{ route('login') }}">ログイン</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="{{ route('register') }}">新規登録</a>
          </li>
        @endauth
      </ul>
    </div>
  </div>
</nav>

<!-- ページヘッダー -->
@isset($header)
  <header class="bg-white shadow py-3 mb-4">
      <div class="container">
          <h1 class="h4 m-0 text-dark">{{ $header }}</h1>
      </div>
  </header>
@endisset

<!-- ページコンテンツ -->
<main class="container mb-5">
  {{ $slot }}
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
