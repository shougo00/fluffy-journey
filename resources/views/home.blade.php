@extends('layouts.user')

@section('content')
<div class="container py-5">
    <div class="row g-4">

        <!-- 左：お知らせ -->
        <div class="col-lg-7">
            <h2 class="mb-4 fw-bold" style="font-size:1.8rem;">お知らせ</h2>

            <div class="border rounded p-3" style="max-height: 550px; overflow-y: auto;">
                @forelse($news as $item)
                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title fw-bold" style="font-size:1.2rem;">{{ $item->title }}</h5>
                            <p class="card-text" style="font-size:1rem;">{{ $item->body }}</p>
                            <small class="text-muted">{{ $item->created_at->format('Y/m/d') }}</small>
                        </div>
                    </div>
                @empty
                    <p class="mb-0" style="font-size:1rem;">現在お知らせはありません。</p>
                @endforelse
            </div>
        </div>

        <!-- 右：メニュー -->
        <div class="col-lg-5">
            <h2 class="mb-4 fw-bold text-center" style="font-size:1.8rem;">メニュー</h2>

            <div class="d-grid gap-4">

                <!-- 学びクエスト -->
                <a href="{{ route('quest.index') }}" class="btn btn-primary p-4 shadow d-flex flex-column align-items-start text-start">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-journal-bookmark-fill me-2" style="font-size:1.8rem;"></i>
                        <span class="fw-bold" style="font-size:1.2rem;">学びクエスト</span>
                    </div>
                    <small class="text-white-50">クエストをクリアすることでポイントと経験値を獲得できます。</small>
                </a>

                <!-- ゲーム -->
                <a href="{{ route('game.index') }}" class="btn btn-success p-4 shadow d-flex flex-column align-items-start text-start">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-controller me-2" style="font-size:1.8rem;"></i>
                        <span class="fw-bold" style="font-size:1.2rem;">ゲーム</span>
                    </div>
                    <small class="text-white-50">ポイントを使ってゲームをプレイできます。</small>
                </a>

                <!-- 作業依頼 -->
                <a href="{{ route('task.index') }}" class="btn btn-warning p-4 shadow d-flex flex-column align-items-start text-start text-dark">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-clipboard-check me-2" style="font-size:1.8rem;"></i>
                        <span class="fw-bold" style="font-size:1.2rem;">作業依頼</span>
                    </div>
                    <small class="text-dark-50">家庭内のお仕事や勉強を成功させるとポイントと経験値を獲得できます。</small>
                </a>

                <!-- 設定 -->
                <a href="{{ route('settings.index') }}" class="btn btn-secondary p-4 shadow d-flex flex-column align-items-start text-start">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-gear-fill me-2" style="font-size:1.8rem;"></i>
                        <span class="fw-bold" style="font-size:1.2rem;">設定</span>
                    </div>
                    <small class="text-white-50">各種設定を変更できます。</small>
                </a>

            </div>
        </div>

    </div>
</div>
@endsection
