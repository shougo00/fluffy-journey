@extends('layouts.user')

@section('content')
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<div class="container-fluid py-3 record-page">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
html, body {
    height: auto;
    overflow: auto;
}
body {
    overscroll-behavior: none;
}

.record-page {
    max-width: 100%;
}
@media (max-width: 1024px) {
    .container {
        max-width: 100% !important;
    }
}
.score-scroll {
    height: calc(100dvh - 280px);
    overflow: auto;
    border: 1px solid #eee;
    -webkit-overflow-scrolling: touch;
    touch-action: auto;
    overscroll-behavior: contain;
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
.print-only {
    display: none;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 4mm;
    }

    nav,
    header,
    footer,
    .navbar,
    .tabs,
    .nav-tabs,
    .btn,
    form,
    .score-scroll,
    h4 {
        display: none !important;
    }

    html, body {
        height: auto !important;
        overflow: visible !important;
    }

    .print-only {
        display: block !important;
    }

    .container {
        max-width: none !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .print-page {
        page-break-after: always;
        break-after: page;
        overflow: hidden;
        padding-bottom: 0;
    }

    .print-page:last-child {
        page-break-after: auto;
        break-after: auto;
    }

    .print-title {
        text-align: center;
        font-size: 13px;
        font-weight: bold;
        margin-bottom: 3px;
        line-height: 1.25;
    }

    .print-score-header,
    .print-tate-row,
    .print-name-row {
        display: flex;
        flex-direction: row-reverse;
        gap: 0 !important;
    }

    .print-tate-area {
        display: flex;
        flex-direction: column-reverse;
        gap: 0 !important;
    }

    .print-score-header {
        margin-bottom: 0;
    }

    .print-name-row {
        margin-top: 0;
    }

    .print-tate-row {
        margin-bottom: 0 !important;
        align-items: stretch;
    }

    .print-shot {
        width: 34px;
        height: 34px;
        border: 1px solid #333;
        border-radius: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: bold;
        line-height: 1;
        box-sizing: border-box;
    }

    .print-user-column {
        width: 34px;
        min-width: 34px;
        display: flex;
        flex-direction: column;
        gap: 0 !important;
        align-items: stretch;
    }

    .print-score {
        width: 34px;
        min-width: 34px;
        height: 24px;
        line-height: 24px;
        text-align: center;
        font-weight: bold;
        font-size: 12px;
        box-sizing: border-box;
        border: 1px solid #333;
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
    }

    .print-name {
        width: 34px;
        min-width: 34px;
        height: 64px;
        writing-mode: vertical-rl;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
        box-sizing: border-box;
        border: 1px solid #333;
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
    }

    .print-tate-label {
        width: 26px;
        min-width: 26px;
        border: 1px solid #333;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        box-sizing: border-box;
    }

    .print-name-spacer {
        width: 26px;
        min-width: 26px;
        height: 64px;
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
        box-sizing: border-box;
    }

    .print-tate-border {
        border-left: 2px solid #000;
    }
}
.record-calendar-box {
    display: none;
    max-width: 420px;
    margin: 0 auto 12px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 8px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.12);
}

.month-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.calendar-wrapper {
    overflow-x: auto;
}

.calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    border: 1px solid #ccc;
}

.day-header,
.day {
    border: 1px solid #ccc;
    min-height: 46px;
    padding: 3px 2px;
    text-align: center;
    font-size: 11px;
}

.day-header {
    background: #f0f0f0;
    font-weight: bold;
    min-height: auto;
}

.day {
    background: #fff;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}

.day.empty {
    background: #f7f7f7;
    cursor: default;
}

.day.has-lineup {
    background: #fff3cd;
}

.day.has-lineup .data {
    font-size: 9px;
    color: #9a6a00;
    font-weight: bold;
}

.day.selected {
    background: #d1e7dd !important;
    border: 2px solid #198754;
}

.day.sunday .date {
    color: red;
}

.day.saturday .date {
    color: blue;
}

.day .date {
    font-weight: bold;
}

.date-calendar-wrap {
    position: relative;
    z-index: 100;
}

#calendarBox {
    display: none;
    position: absolute;
    top: 48px;
    left: 50%;
    transform: translateX(-50%);
    width: 420px;
    max-width: calc(100vw - 20px);
    background: white;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 8px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.18);
    z-index: 9999;
}
</style>


<div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
    <h4>{{ $group->name }}（正規連）</h4>

    <div style="display:flex; gap:8px;">
        <button type="button" class="btn btn-outline-primary" onclick="reloadAndPrint()">
        印刷
    </button>
        <a href="/group/{{ $group->id }}/lineup?date={{ $date }}" class="btn btn-secondary">
            立順
        </a>
    </div>

</div>
<div class="date-calendar-wrap">
    {{-- 日付移動 --}}
    <form method="GET" action="/group/{{ $group->id }}/records" class="mb-2 text-center">
        <div class="d-flex justify-content-center align-items-center gap-3">

            <a href="/group/{{ $group->id }}/records?date={{ \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d') }}&month={{ \Carbon\Carbon::parse($date)->subDay()->format('Y-m') }}"
            class="btn btn-outline-secondary">
                ＜
            </a>

            <input type="text"
                value="{{ $date }}"
                readonly
                onclick="toggleCalendar(event)"
                class="form-control text-center"
                style="max-width:180px; cursor:pointer; background:white;">

            <a href="/group/{{ $group->id }}/records?date={{ \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d') }}&month={{ \Carbon\Carbon::parse($date)->addDay()->format('Y-m') }}"
            class="btn btn-outline-secondary">
                ＞
            </a>

        </div>
    </form>

    {{-- カレンダー --}}
    <div id="calendarBox" style="{{ request('open') ? 'display:block;' : 'display:none;' }}">

        <div class="month-nav">
            <a href="/group/{{ $group->id }}/records?date={{ \Carbon\Carbon::parse($prevMonth . '-01')->format('Y-m-d') }}&month={{ $prevMonth }}&open=1"
            class="btn btn-sm btn-outline-secondary">＜</a>

            <strong>{{ \Carbon\Carbon::parse($month . '-01')->format('Y年n月') }}</strong>

            <a href="/group/{{ $group->id }}/records?date={{ \Carbon\Carbon::parse($nextMonth . '-01')->format('Y-m-d') }}&month={{ $nextMonth }}&open=1"
            class="btn btn-sm btn-outline-secondary">＞</a>
        </div>

        <div class="calendar-wrapper">
            <div class="calendar">
                @php
                    $weekdays = ['日','月','火','水','木','金','土'];
                    $firstDay = \Carbon\Carbon::parse($month . '-01');
                    $days = $firstDay->daysInMonth;
                    $startWeek = $firstDay->dayOfWeek;
                @endphp

                @foreach($weekdays as $wd)
                    <div class="day-header">{{ $wd }}</div>
                @endforeach

                @for($i = 0; $i < $startWeek; $i++)
                    <div class="day empty"></div>
                @endfor

                @for($i = 1; $i <= $days; $i++)
                    @php
                        $dateObj = \Carbon\Carbon::parse($month . '-' . str_pad($i, 2, '0', STR_PAD_LEFT));
                        $dayDate = $dateObj->format('Y-m-d');
                        $dayOfWeek = $dateObj->dayOfWeek;

                        $dayClass = '';
                        if ($dayOfWeek === 0) $dayClass .= ' sunday';
                        if ($dayOfWeek === 6) $dayClass .= ' saturday';
                        if ($dayDate === $date) $dayClass .= ' selected';

                        $hasLineup = in_array($dayDate, $lineupDates ?? []);
                    @endphp

                    <a href="/group/{{ $group->id }}/records?date={{ $dayDate }}&month={{ $month }}"
                    class="day {{ $dayClass }} {{ $hasLineup ? 'has-lineup' : '' }}">
                        <div class="date">{{ $i }}</div>

                        @if($hasLineup)
                            <div class="data">記録あり</div>
                        @endif
                    </a>
                @endfor
            </div>
        </div>
    </div>
</div>

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

{{-- 印刷専用レイアウト --}}
<div class="print-only">

@php
    // 5立ごと
    $printTatePages = collect($tates)->chunk(5);

    // 1枚あたりの人数
    // 見切れる場合は 8 → 7 にしてください
    $printMemberPages = collect($lineupSlots)->chunk(17);
@endphp

@foreach($printTatePages as $pageTates)
    @foreach($printMemberPages as $pageSlots)

        <div class="print-page">

            <div class="print-title">
                {{ $group->name }}（正規連）<br>
                {{ \Carbon\Carbon::parse($date)->locale('ja')->isoFormat('YYYY年M月D日（ddd）') }}
            </div>

            {{-- 上：このページの的中 --}}
            <div class="print-score-header">
                <div class="print-tate-label"></div>

                @foreach($pageSlots as $slot)
                    <div class="print-score {{ (($loop->index + 1) % $tateSize == 0) ? 'print-tate-border' : '' }}">
                        @if(!$slot->is_empty)
                            @php
                                $user = $slot->user;
                                $pageHitCount = 0;

                                foreach ($pageTates as $printTateNo) {
                                    $printRecord = ($records[$user->id] ?? collect())
                                        ->where('tate_no', $printTateNo)
                                        ->first();

                                    if ($printRecord) {
                                        $pageHitCount += $printRecord->shots
                                            ->where('result', 'hit')
                                            ->count();
                                    }
                                }
                            @endphp

                            {{ $pageHitCount }}中
                        @else
                            -
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- 中：記録 --}}
            <div class="print-tate-area">
                @foreach($pageTates as $tateNo)
                    <div class="print-tate-row">
                        <div class="print-tate-label">{{ $tateNo }}</div>

                        @foreach($pageSlots as $slot)

                            @if($slot->is_empty)

                                <div class="print-user-column {{ (($loop->index + 1) % $tateSize == 0) ? 'print-tate-border' : '' }}">
                                    @for($i=1;$i<=4;$i++)
                                        <div class="print-shot"></div>
                                    @endfor
                                </div>

                            @else

                                @php
                                    $user = $slot->user;
                                    $userRecords = $records[$user->id] ?? collect();
                                    $record = $userRecords->where('tate_no', $tateNo)->first();
                                @endphp

                                <div class="print-user-column {{ (($loop->index + 1) % $tateSize == 0) ? 'print-tate-border' : '' }}">
                                    @for($i=1;$i<=4;$i++)
                                        @php
                                            $shot = $record
                                                ? $record->shots->where('shot_no',$i)->first()
                                                : null;
                                        @endphp

                                        <div class="print-shot">
                                            @if($shot?->result=='hit')
                                                ○
                                            @elseif($shot?->result=='miss')
                                                ×
                                            @else

                                            @endif
                                        </div>
                                    @endfor
                                </div>

                            @endif

                        @endforeach
                    </div>
                @endforeach
            </div>

            {{-- 下：名前 --}}
            <div class="print-name-row">
                <div class="print-name-spacer"></div>

                @foreach($pageSlots as $slot)
                    <div class="print-name {{ (($loop->index + 1) % $tateSize == 0) ? 'print-tate-border' : '' }}">
                        {{ $slot->is_empty ? '空き' : $slot->user->name }}
                    </div>
                @endforeach
            </div>

        </div>

    @endforeach
@endforeach

</div>



<script>

function reloadAndPrint() {
    const url = new URL(window.location.href);
    url.searchParams.set('print', '1');
    window.location.href = url.toString();
}

window.addEventListener('load', () => {
    const url = new URL(window.location.href);

    if (url.searchParams.get('print') === '1') {
        url.searchParams.delete('print');
        history.replaceState(null, '', url.toString());

        setTimeout(() => {
            window.print();
        }, 500);
    }
});
    
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
function scrollRight() {
    const el = document.querySelector('.score-scroll');
    if (el) el.scrollLeft = el.scrollWidth;
}

window.addEventListener('load', () => {
    setTimeout(scrollRight, 50);
});

function toggleCalendar(event) {
    event.stopPropagation();

    const box = document.getElementById('calendarBox');

    if (!box) return;

    box.style.display = box.style.display === 'block' ? 'none' : 'block';
}

</script>

@endsection