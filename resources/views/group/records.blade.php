
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

        <!-- ===== 上ヘッダー（的中数） ===== -->
        <div class="score-header">
            <div class="tate-label"></div>
            @foreach($group->users as $user)
                <div class="score" data-user-id="{{ $user->id }}">
                    {{ $user->hits ?? 0 }}中
                </div>
            @endforeach
        </div>

        <!-- ===== 本体 ===== -->
        <div class="tate-area">
            @foreach($tates as $tateNo)
                <div class="tate-row">

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
                                    {{ !$shot || $shot->result==null?'shot-none':'' }}"
                                    data-id="{{ $shot->id ?? '' }}"
                                    data-user="{{ $user->id }}"
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

                                <!-- 4射ごとに線 -->
                                @if($i == 4 && !$loop->parent->first)
                                    <div class="shot-separator"></div>
                                @endif

                            @endfor

                        </div>
                    @endforeach

                </div>
            @endforeach
        </div>

        <!-- ===== 名前 ===== -->
        <div class="name-row">
            @foreach($group->users as $user)
                <div class="name">{{ $user->name }}</div>
            @endforeach
        </div>

    </div>

</div>

</div>

<style>

html, body {
    height: 100%;
    overflow: hidden; /* ←これ重要 */
}
.container {
    height: 100vh;
    display: flex;
    flex-direction: column;
}

/* スクロール */
.score-scroll {
    overflow: auto;
    max-height: 75vh;
}

/* 横幅 */
.score-wrapper {
    min-width: max-content;
}

/* 上ヘッダー */
.score-header {
    display: flex;
    border-bottom: 2px solid #000;
    padding-bottom: 5px;
    position: sticky;
    top: 0;
    background: white;
    z-index: 20;
    
}

/* 的中 */
.score {
    width: 65px;
    text-align: center;
    font-weight: bold;
}

/* 下→上 */
.tate-area {
    display: flex;
    flex-direction: column-reverse;
}

/* 行 */
.tate-row {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

/* 立番号 */
.tate-label {
    width: 40px;
    text-align: center;
    font-weight: bold;
    position: sticky;
    left: 0;
    background: white;
    z-index: 5;
}
/* 列 */
.user-column {
    display: flex;
    flex-direction: column;
    gap: 6px;
    width: 65px;
    align-items: center;
}

/* ボタン */
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

.shot-hit i { color:#ff3b30; }
.shot-miss i { color:#007aff; }

.shot-none {
    border:2px dashed #ccc;
    color:#bbb;
}

/* 区切り線 */
.shot-separator {
    width: 80%;
    height: 1px;
    background: #ddd;
}

/* 名前 */
.name-row {
    display: flex;
    border-top: 2px solid #000;
    position: sticky;
    bottom: 0;
    background: white;
    padding-left: 40px; 
}

.name {
    width: 65px;
    text-align: center;
    writing-mode: vertical-rl;
    font-weight: bold;
    transform: translateX(-18px);
}

/* スマホ */
@media (max-width: 600px) {
    .user-column { width: 55px; }
    .score, .name { width: 55px; font-size: 12px; }
    .shot-btn { width: 48px; height: 48px; }
    .shot-btn i { font-size: 24px; }
}


</style>

<script>
function updateShot(el){

    let id = el.dataset.id;
    if(!id) return;

    let userId = el.dataset.user;

    let current = el.dataset.result;
    let next = current==='hit'?'miss':current==='miss'?'':'hit';

    // UI変更
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

    // 的中数更新
    const scoreEl = document.querySelector(`.score[data-user-id="${userId}"]`);
    if(scoreEl){
        let currentHits = parseInt(scoreEl.innerText) || 0;

        if(current !== 'hit' && next === 'hit') currentHits++;
        if(current === 'hit' && next !== 'hit') currentHits--;

        scoreEl.innerText = currentHits + '中';
    }

    // DB保存
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
