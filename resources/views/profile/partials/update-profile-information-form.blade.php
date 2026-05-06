<section class="container mt-5">

    <!-- メール確認用フォーム（非表示） -->
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label for="name" class="form-label">名前</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @if($errors->get('name'))
                <div class="text-danger mt-1">
                    {{ $errors->first('name') }}
                </div>
            @endif
        </div>

       <div class="mb-3">
            <label for="username" class="form-label">ユーザー名</label>
            <input type="text"
                class="form-control"
                id="username"
                name="username"
                value="{{ old('username', $user->username) }}"
                required
                autocomplete="username">

            @if($errors->get('username'))
                <div class="text-danger mt-1">
                    {{ $errors->first('username') }}
                </div>
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">試合区分</label>

            <div>
                <label class="me-3">
                    <input type="radio" name="gender" value="male"
                        {{ old('gender', $user->gender) === 'male' ? 'checked' : '' }}
                        required>
                    男子の部
                </label>

                <label>
                    <input type="radio" name="gender" value="female"
                        {{ old('gender', $user->gender) === 'female' ? 'checked' : '' }}
                        required>
                    女子の部
                </label>
            </div>

            @error('gender')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
        @if($user->groups()->exists())
        <div class="mb-3">
            <label class="form-label">ホストユーザー</label>

            <div class="form-check form-switch">
                <input class="form-check-input"
                    type="checkbox"
                    id="is_admin"
                    name="is_admin"
                    value="1"
                    {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>

                <label class="form-check-label" for="is_admin">
                    タブレットなどで使用するホストユーザーとしてON
                </label>
            </div>

            @error('is_admin')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
        @endif

        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-primary">保存</button>

            @if (session('status') === 'profile-updated')
                <p id="savedMessage" class="text-muted mb-0" style="transition: opacity 0.5s;">保存しました。</p>
                <script>
                    setTimeout(() => {
                        document.getElementById('savedMessage').style.opacity = 0;
                    }, 2000);
                </script>
            @endif
        </div>
       <hr class="my-4">

        
    </form>
    <div class="text-center">
        <form method="POST" action="{{ route('logout') }}"
            onsubmit="return confirm('ログアウトしますか？');">
            @csrf
            <button class="btn btn-outline-danger px-4">
                ログアウト
            </button>
        </form>
    </div>
</section>
