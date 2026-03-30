@extends('layouts.user')

@section('content')

<div class="container py-3">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<h4>{{ $group->name }}（正規連）</h4>

<form method="POST" action="/group/{{ $group->id }}/add-tate" class="mb-3">
    @csrf
    <button class="btn btn-primary w-100">＋ 立を追加</button>
</form>

<div class="score-scroll">

    <div class="score-wrapper">

        {{-- 立（下→上） --}}
        <div class="tate-area">
            @foreach($tates as $tateNo)
                <div class="tate-row">

                    {{-- ★立番号固定 --}}
                    <div class="tate-label">{{ $tateNo }}</div>

                    @foreach($group->users as $user)

                        @php
                            $record = $records[$user->id] ?? collect();
                            $record = $record->where('tate_no',$tateNo)->first();
                        @endphp

                        <div class="user-column">

                            @for($i=1;$i<=4;$i++)
                                @php
                                    $shot = $record
                                        ? $record->shots->where('shot_no',$i)->first()
                                        : null;
                                @endphp

                                <div class="shot-btn
                                    {{ $shot?->result=='hit'?'shot-hit':'' }}
                                    {{ $shot?->result=='miss'?'shot-miss':'' }}
                                    {{ !$shot || $shot->result==null?'shot-none':'' }}
                                    {{ $i==4 ? 'shot-divider' : '' }}"
                                    data-id="{{ $shot->id ?? '' }}"
                                    data-result="{{ $shot?->result ?? '' }}"
                                    onclick="updateShot(this)">

                                    @if($shot?->result=='hit')
                                        <i class="fa-regular fa-circle"></i>
                                    @elseif($shot?->result=='miss')
                                        <i class="fas fa-xmark"></i>
                                    @else
                                        ＋
                                    @endif
                                </div>
                            @endfor

                        </div>
                    @endforeach

                </div>
            @endforeach
        </div>

        {{-- ★名前フッター固定 --}}
        <div class="name-row">
            <div class="tate-label"></div>
            @foreach($group->users as $user)
                <div class="name">{{ $user->name }}</div>
            @endforeach
        </div>

    </div>

</div>
</div>

<style>

/* ===== スクロール ===== */
.score-scroll {
    overflow: auto;
    max-height: 75vh; /* ★縦スクロール発動 */
    -webkit-overflow-scrolling: touch;
}

/* 横に広げる */
.score-wrapper {
    display: flex;
    flex-direction: column;
    min-width: max-content;
}

/* ===== 下→上 ===== */
.tate-area {
    display: flex;
    flex-direction: column-reverse;
}

/* ===== 行 ===== */
.tate-row {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

/* ===== 立番号（左固定） ===== */
.tate-label {
    width: 40px;
    text-align: center;
    font-weight: bold;
    flex-shrink: 0;

    position: sticky;
    left: 0;
    background: white;
    z-index: 5;
}

/* ===== ユーザー列 ===== */
.user-column {
    display: flex;
    flex-direction: column;
    gap: 6px;
    width: 65px;
    align-items: center;
    flex-shrink: 0;
}

/* ===== ボタン ===== */
.shot-btn {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    border: 2px solid #ccc;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    background:white;
}

.shot-btn i { font-size:30px; }

/* ○ */
.shot-hit i { color:#ff3b30; }
.fa-circle { font-weight:400 !important; }

/* × */
.shot-miss i { color:#007aff; font-size:34px; }

/* 未入力 */
.shot-none {
    border:2px dashed #ccc;
    color:#bbb;
}

/* 4射区切り */
.shot-divider {
    margin-bottom:12px;
    padding-bottom:6px;
    border-bottom:2px solid #eee;
}

/* ===== 名前フッター固定 ===== */
.name-row {
    display: flex;
    border-top: 2px solid #000;
    padding-top: 5px;

    position: sticky;
    bottom: 0;
    background: white;
    z-index: 10;

    transform: translateX(-20px); /* 微調整 */
    box-shadow: 0 -2px 6px rgba(0,0,0,0.1); /* 浮かせる */
}

.name {
    width: 65px;
    text-align: center;
    writing-mode: vertical-rl;
    flex-shrink: 0;
    font-weight: bold;
}

/* ===== スマホ最適化 ===== */
@media (max-width: 600px) {

    .user-column {
        width: 55px;
    }

    .name {
        width: 55px;
        font-size: 12px;
    }

    .shot-btn {
        width: 48px;
        height: 48px;
    }

    .shot-btn i {
        font-size: 24px;
    }
}

</style>

<script>
function updateShot(el){

    let id = el.dataset.id;
    if(!id) return;

    let current = el.dataset.result;
    let next = current==='hit'?'miss':current==='miss'?'':'hit';

    el.dataset.result = next;

    el.innerHTML =
        next==='hit'
        ? '<i class="fa-regular fa-circle"></i>'
        : next==='miss'
        ? '<i class="fas fa-xmark"></i>'
        : '＋';

    el.classList.remove('shot-hit','shot-miss','shot-none');
    el.classList.add(
        next==='hit'?'shot-hit':
        next==='miss'?'shot-miss':'shot-none'
    );

    fetch(`/group/shot/${id}`,{
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ result: next })
    });
}
</script>

@endsection