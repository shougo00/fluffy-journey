<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>FamLevel</title>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* ----------------------
   アバター全体
---------------------- */
.navbar-avatar-box {
    position: relative;
    width: 40px;
    height: 50px;
    cursor: pointer;
}

/* パーツ共通 */
.navbar-avatar-layer {
    position: absolute;
    object-fit: contain;
}

/* PC用パーツ位置 */
.navbar-avatar-layer.hair { top:0; left:0; width:100%; height:20px; z-index:6; }
.navbar-avatar-layer.face { top:10px; left:10px; width:20px; height:20px; z-index:5; }
.navbar-avatar-layer.top { top:25px; left:0; width:40px; height:15px; z-index:4; }
.navbar-avatar-layer.bottom { top:35px; left:0; width:40px; height:10px; z-index:3; }
.navbar-avatar-layer.shoes { top:45px; left:5px; width:30px; height:5px; z-index:2; }
.navbar-avatar-layer.accessory { top:0; left:5px; width:30px; height:10px; z-index:7; }

/* PC ロゴとアバター間の間隔 */
.navbar-brand { display:flex; align-items:center; gap:0.3rem; }

/* ----------------------
   スマホ対応
---------------------- */
@media (max-width: 576px) {
    /* アバター縮小＆中央寄せ */
    .navbar-avatar-box {
        width: 35px;
        height: 45px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .navbar-avatar-layer {
        width: 80%;
        height: auto;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
    }

    /* パーツ縦位置調整 */
    .navbar-avatar-layer.hair { top:0%; }
    .navbar-avatar-layer.face { top:20%; }
    .navbar-avatar-layer.top { top:50%; }
    .navbar-avatar-layer.bottom { top:70%; }
    .navbar-avatar-layer.shoes { top:85%; }
    .navbar-avatar-layer.accessory { top:5%; }

    /* アバター + FAMLEVEL 中央揃え */
    .navbar-mobile-center {
        display:flex;
        align-items:center;
        justify-content:center;
        gap:0.3rem;
        flex-shrink:0;
        line-height:1;
    }

    .navbar-mobile-center span {
        white-space: nowrap;
    }

    /* レベル・ポイント表示を小さく */
    .nav-item.d-flex .badge { font-size:0.7rem; }
    .nav-item.d-flex .progress { width:80px; height:8px; }

    /* ナビメニュー文字を小さく */
    .navbar-nav .nav-link { font-size:0.9rem; }
}
</style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
<div class="container d-flex justify-content-between align-items-center">

    {{-- PC用 --}}
    <div class="d-none d-lg-flex align-items-center">
        @auth
            @php $avatar = Auth::user()->avatar; @endphp
            @if($avatar)
                <a href="{{ route('avatar.show') }}" class="me-2">
                    <div class="navbar-avatar-box">
                        @foreach(['bottom','shoes','top','face','hair','accessory'] as $part)
                            @if($avatar->$part)
                                <img src="{{ asset('avatars/'.$part.'/'.$avatar->$part->image_path) }}"
                                     class="navbar-avatar-layer {{ $part }}">
                            @endif
                        @endforeach
                    </div>
                </a>
            @else
                <a href="{{ route('avatar.show') }}" class="me-2">
                    <img src="{{ asset('avatars/default.png') }}" alt="デフォルトアバター"
                         style="width:40px;height:50px;object-fit:contain;">
                </a>
            @endif
        @endauth
        <i class="bi bi-journal-bookmark-fill text-primary fs-3 me-2"></i>
        <span class="fw-bold text-dark">FamLevel</span>
    </div>

    {{-- スマホ用 --}}
    <div class="d-flex d-lg-none w-100 justify-content-between align-items-center">
        <!-- 左アイコン -->
        <i class="bi bi-journal-bookmark-fill text-primary fs-3"></i>

        <!-- 中央: アバター + FAMLEVEL -->
        <div class="navbar-mobile-center">
            @auth
                @if($avatar)
                    <a href="{{ route('avatar.show') }}">
                        <div class="navbar-avatar-box">
                            @foreach(['bottom','shoes','top','face','hair','accessory'] as $part)
                                @if($avatar->$part)
                                    <img src="{{ asset('avatars/'.$part.'/'.$avatar->$part->image_path) }}"
                                         class="navbar-avatar-layer {{ $part }}">
                                @endif
                            @endforeach
                        </div>
                    </a>
                @else
                    <a href="{{ route('avatar.show') }}">
                        <img src="{{ asset('avatars/default.png') }}" alt="デフォルトアバター"
                             style="width:35px;height:45px;object-fit:contain;">
                    </a>
                @endif
            @endauth
            <span class="fw-bold text-dark ms-1">FAMLEVEL</span>
        </div>

        <!-- 右ハンバーガー -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>

    {{-- メニュー --}}
    <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('home') }}">ホーム</a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
            @auth
                @php
                    $level = Auth::user()->level ?? 1;
                    $exp = Auth::user()->exp ?? 30;
                    $nextExp = Auth::user()->next_exp ?? 100;
                    $percent = min(100, intval($exp / $nextExp * 100));
                    $point = Auth::user()->point ?? 250;
                @endphp

                <li class="nav-item d-flex align-items-center me-3 flex-wrap">
                    <span class="badge bg-primary me-2">Lv {{ $level }}</span>
                    <div class="progress me-2 flex-grow-1" style="height:10px; min-width:50px;">
                        <div class="progress-bar bg-success" style="width: {{ $percent }}%"></div>
                    </div>
                    <span class="badge bg-warning text-dark">{{ $point }} pt</span>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown">{{ Auth::user()->name }}</a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">プロフィール</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item">ログアウト</button>
                            </form>
                        </li>
                    </ul>
                </li>
            @else
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('login') }}">ログイン</a>
                </li>
            @endauth
        </ul>
    </div>

</div>
</nav>

{{-- ページヘッダー --}}
@isset($header)
<header class="bg-white shadow py-3 mb-4">
    <div class="container">
        <h1 class="h4 m-0 text-dark">{{ $header }}</h1>
    </div>
</header>
@endisset

<main class="container mb-5">
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
