@extends('layouts.user')

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1">設定</h4>
            <div class="text-muted small">
                {{ $group ? $group->name : 'グループ未参加' }}
            </div>
        </div>
        <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">プロフィールへ</a>
    </div>

    @if(!$group)
        <div class="alert alert-warning">
            グループに参加してから設定できます。
        </div>
    @elseif(!$unlocked)
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <strong>管理者確認</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.unlock') }}" class="settings-unlock-form">
                    @csrf

                    <div class="mb-3">
                        <label for="password" class="form-label">管理者パスワード</label>
                        <input id="password"
                               type="password"
                               name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required
                               autocomplete="current-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button class="btn btn-primary">設定を開く</button>
                </form>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <strong>正規練記録</strong>
            </div>
            <div class="card-body">
                @if(session('status') === 'settings-updated')
                    <div class="alert alert-success">設定を保存しました。</div>
                @endif

                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="official_tates_per_page" class="form-label">1ページの最大立数</label>
                        <select id="official_tates_per_page"
                                name="official_tates_per_page"
                                class="form-select @error('official_tates_per_page') is-invalid @enderror">
                            @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}" {{ (int) old('official_tates_per_page', $group->official_tates_per_page ?? 5) === $i ? 'selected' : '' }}>
                                    {{ $i }}立
                                </option>
                            @endfor
                        </select>
                        @error('official_tates_per_page')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button class="btn btn-primary">保存</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
