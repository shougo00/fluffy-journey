<x-app-layout>

    <div class="container py-5">

    <!-- 機能カード -->
    <div class="row g-4">

        <!-- ユーザ管理 -->
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="{{ route('users.index') }}" class="card text-center h-100 text-decoration-none text-dark shadow-sm hover-shadow">
                <div class="card-body">
                    <div class="display-4 mb-3">👤</div>
                    <h5 class="card-title">ユーザ管理</h5>
                    <p class="card-text text-muted">ユーザ一覧・追加・編集・削除</p>
                </div>
            </a>
        </div>

        <!-- プロフィール編集 -->
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="{{ route('profile.edit') }}" class="card text-center h-100 text-decoration-none text-dark shadow-sm hover-shadow">
                <div class="card-body">
                    <div class="display-4 mb-3">⚙️</div>
                    <h5 class="card-title">プロフィール編集</h5>
                    <p class="card-text text-muted">アカウント情報やパスワードを変更</p>
                </div>
            </a>
        </div>

        <!-- お知らせ管理 -->
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="{{ route('news.index') }}" class="card text-center h-100 text-decoration-none text-dark shadow-sm hover-shadow">
                <div class="card-body">
                    <div class="display-4 mb-3">📢</div>
                    <h5 class="card-title">お知らせ管理</h5>
                    <p class="card-text text-muted">お知らせの作成・編集・公開管理</p>
                </div>
            </a>
        </div>

        <!-- ステータス管理 -->
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="{{ route('admin.users.status.index') }}" class="card text-center h-100 text-decoration-none text-dark shadow-sm hover-shadow">
                <div class="card-body">
                    <div class="display-4 mb-3">🎮</div>
                    <h5 class="card-title">ステータス管理</h5>
                    <p class="card-text text-muted">レベル・経験値・ポイント管理</p>
                </div>
            </a>
        </div>

    </div>
</div>


    <!-- Bootstrap CSS/JS（必要であれば読み込み） -->
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            .hover-shadow:hover {
                box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @endpush

</x-app-layout>
