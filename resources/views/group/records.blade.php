@extends('layouts.user')

@section('content')

@vite(['resources/css/group/records.css', 'resources/js/group/records.js'])

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<div class="container-fluid py-3 record-page">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


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

@endsection