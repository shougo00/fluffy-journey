<div class="rank-card">
    <div class="rank-no">{{ $rank }}</div>

    <div class="avatar-area">
        @php
            $avatar = $row['user']->avatar;
        @endphp

        @if ($avatar)
            @foreach(['bottom','shoes','top','face','hair','accessory'] as $part)
                @if($avatar->$part)
                    <img src="{{ asset('avatars/'.$part.'/'.$avatar->$part->image_path) }}"
                         class="avatar-layer {{ $part }}">
                @endif
            @endforeach
        @else
            <img src="{{ asset('avatars/default.png') }}"
                 style="width:52px;height:62px;object-fit:contain;">
        @endif
    </div>

    <div class="rank-info">
        <div class="user-name">{{ $row['user']->name }}</div>

        <div class="score-line {{ $scoreType === 'all' ? 'active-score' : '' }}">
            <span>総合</span>
            <span>
                {{ $row['all']['shots'] }}射
                {{ $row['all']['hits'] }}中
                {{ $row['all']['rate'] }}%
            </span>
        </div>

        <div class="score-line {{ $scoreType === 'official' ? 'active-score' : '' }}">
            <span>正規練</span>
            <span>
                {{ $row['official']['shots'] }}射
                {{ $row['official']['hits'] }}中
                {{ $row['official']['rate'] }}%
            </span>
        </div>

        <div class="score-line {{ $scoreType === 'self' ? 'active-score' : '' }}">
            <span>自主練</span>
            <span>
                {{ $row['self']['shots'] }}射
                {{ $row['self']['hits'] }}中
                {{ $row['self']['rate'] }}%
            </span>
        </div>
    </div>
</div>
