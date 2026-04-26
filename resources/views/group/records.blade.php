@extends('layouts.user')

@section('content')

<div class="container py-3">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
    <h4>{{ $group->name }}（正規連）</h4>

    <a href="/group/{{ $group->id }}/lineup?date={{ $date }}" class="btn btn-secondary">
        立順
    </a>
</div>

<form method="GET" action="/group/{{ $group->id }}/records" class="mb-3 d-flex gap-2">
    <input type="date" name="date" value="{{ $date }}" class="form-control">
    <button class="btn btn-outline-primary">表示</button>
</form>

<form method="POST" action="/group/{{ $group->id }}/add-tate" class="mb-3">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">
    <button class="btn btn-primary w-100">＋ 立を追加</button>
</form>

@if($lineupSlots->isEmpty())
    <div class="alert alert-warning">
        この日はまだ立順が設定されていません。
    </div>
@endif

<div class="score-scroll">

<div class="score-wrapper">

<div class="score-header">
    <div class="tate-label"></div>

    @foreach($lineupSlots as $slot)
        <div class="score {{ (($loop->index + 1) % $tateSize == 0) ? 'tate-border' : '' }}"
             data-user-id="{{ $slot->user?->id }}">
            @if(!$slot->is_empty)
                {{ $hitCounts[$slot->user->id] ?? 0 }}中
            @else
                -
            @endif
        </div>
    @endforeach
</div>

<div class="tate-area">
@foreach($tates as $tateNo)

    <div class="tate-row">

        <div class="tate-label">{{ $tateNo }}</div>

        @foreach($lineupSlots as $slot)

            @if($slot->is_empty)

                <div class="user-column empty-column {{ (($loop->index + 1) % $tateSize == 0) ? 'tate-border' : '' }}">
                    @for($i=1;$i<=4;$i++)
                        <div class="shot-btn empty-shot">空</div>

                        @if($i == 4 && !$loop->parent->first)
                            <div class="shot-separator"></div>
                        @endif
                    @endfor
                </div>

            @else

                @php
                    $user = $slot->user;
                    $userRecords = $records[$user->id] ?? collect();
                    $record = $userRecords->where('tate_no', $tateNo)->first();
                @endphp

                <div class="user-column {{ (($loop->index + 1) % $tateSize == 0) ? 'tate-border' : '' }}">

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

                        @if($i == 4 && !$loop->parent->first)
                            <div class="shot-separator"></div>
                        @endif
                    @endfor

                </div>

            @endif

        @endforeach

    </div>

@endforeach
</div>

<div class="name-row">
    <div class="name-spacer"></div>

    @foreach($lineupSlots as $slot)
        <div class="name {{ $slot->is_empty ? 'empty-name' : '' }} {{ (($loop->index + 1) % $tateSize == 0) ? 'tate-border' : '' }}">
            {{ $slot->is_empty ? '空き' : $slot->user->name }}
        </div>
    @endforeach
</div>

</div>
</div>
</div>

<style>
html, body {
    height: 100%;
    overflow: hidden;
}

.score-scroll {
    height: calc(85dvh - 200px);
    overflow: auto;
    border: 1px solid #eee;
}

.score-wrapper {
    min-width: max-content;
}

.score-header,
.tate-row,
.name-row {
    display: flex;
    flex-direction: row-reverse;
}

.tate-border {
    box-shadow: -2px 0 0 rgba(0,0,0,0.18);
}

.score-header {
    border-bottom: 2px solid #000;
    padding-bottom: 5px;
    position: sticky;
    top: 0;
    background: white;
    z-index: 20;
}

.score {
    width: 65px;
    min-width: 65px;
    text-align: center;
    font-weight: bold;
}

.tate-area {
    display: flex;
    flex-direction: column-reverse;
    padding-top: 10px;
}

.tate-row {
    align-items: center;
    margin-bottom: 10px;
}

.tate-label {
    width: 40px;
    min-width: 40px;
    text-align: center;
    font-weight: bold;
    position: sticky;
    right: 0;
    background: white;
    z-index: 15;
}

.user-column {
    display: flex;
    flex-direction: column;
    gap: 6px;
    width: 65px;
    min-width: 65px;
    align-items: center;
}

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
    user-select:none;
    touch-action: manipulation;
}

.empty-shot {
    border: 2px dashed #ddd;
    background: #f7f7f7;
    color: #aaa;
    cursor: default;
    font-size: 12px;
}

.empty-column {
    opacity: 0.75;
}

.shot-btn i { font-size:30px; }

.shot-hit i { color:#ff3b30; }
.shot-miss i { color:#007aff; }

.shot-none {
    border:2px dashed #ccc;
    color:#bbb;
}

.shot-separator {
    width: 80%;
    height: 1px;
    background: #ddd;
}

.name-row {
    border-top: 2px solid #000;
    position: sticky;
    bottom: 0;
    background: white;
    z-index: 20;
}

.name-spacer {
    width: 40px;
    min-width: 40px;
    position: sticky;
    right: 0;
    background: white;
    z-index: 25;
}

.name {
    width: 65px;
    min-width: 65px;
    writing-mode: vertical-rl;

    display: flex;
    align-items: center;     /* 横中央 */
    justify-content: center; /* 縦中央 */

    text-align: center;
    font-weight: bold;
}

.empty-name {
    color: #aaa;
    font-weight: normal;
}

@media (max-width: 600px) {
    .score-scroll {
        height: calc(85dvh - 210px);
    }

    .user-column {
        width: 55px;
        min-width: 55px;
    }

    .score,
    .name {
        width: 55px;
        min-width: 55px;
        font-size: 12px;
    }

    .shot-btn {
        width: 48px;
        height: 48px;
    }

    .shot-btn i {
        font-size: 24px;
    }

    .tate-border {
        margin-left: 4px;
        padding-left: 4px;
    }
}
</style>

<script>
function updateShot(el){

    const id = el.dataset.id;
    if(!id){
        alert('先に立を追加してください');
        return;
    }

    const userId = el.dataset.user;
    const current = el.dataset.result;

    const next =
        current==='hit' ? 'miss' :
        current==='miss' ? '' :
        'hit';

    el.dataset.result = next;

    el.innerHTML =
        next==='hit'
        ? '<i class="fa-regular fa-circle"></i>'
        : next==='miss'
        ? '<i class="fas fa-xmark"></i>'
        : '＋';

    el.classList.remove('shot-hit','shot-miss','shot-none');

    if(next==='hit') el.classList.add('shot-hit');
    else if(next==='miss') el.classList.add('shot-miss');
    else el.classList.add('shot-none');

    const scoreEl = document.querySelector(`.score[data-user-id="${userId}"]`);

    if(scoreEl){
        let count = parseInt(scoreEl.innerText) || 0;

        if(current !== 'hit' && next === 'hit') count++;
        if(current === 'hit' && next !== 'hit') count--;

        if(count < 0) count = 0;

        scoreEl.innerText = count + '中';
    }

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