@extends('layouts.user')

@section('content')

@vite(['resources/css/lineup/index.css', 'resources/js/lineup/index.js'])

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<div class="container py-3">

<div class="d-flex justify-content-between align-items-center mb-2">
    <h4 class="lineup-title mb-0">
        {{ $group->name }}｜立順設定
    </h4>

    <a href="/group/{{ $group->id }}/records?date={{ $date }}"
       class="btn btn-success">
        記録に戻る
    </a>
</div>

{{-- 日付移動 --}}
<form method="GET" action="/group/{{ $group->id }}/lineup" class="mb-3">
    <div class="date-nav">
        <a href="/group/{{ $group->id }}/lineup?date={{ \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d') }}"
           class="btn btn-outline-secondary nav-btn">＜</a>

        <input type="date"
               name="date"
               value="{{ $date }}"
               onchange="this.form.submit()"
               class="form-control text-center date-input">

        <a href="/group/{{ $group->id }}/lineup?date={{ \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d') }}"
           class="btn btn-outline-secondary nav-btn">＞</a>
    </div>
</form>

{{-- 操作ボタン --}}
<div class="lineup-toolbar mb-2">
    <select id="tateSize" class="form-select toolbar-item">
        @for($i = 3; $i <= 15; $i++)
            <option value="{{ $i }}" {{ $lineup->tate_size == $i ? 'selected' : '' }}>
                {{ $i }}人立
            </option>
        @endfor
    </select>
    <button type="button" class="btn btn-outline-primary toolbar-btn" onclick="addLineupRow()">
        ＋列追加
    </button>
    <form method="POST" action="/lineup/{{ $lineup->id }}/copy-previous" class="toolbar-form">
        @csrf
        <button type="submit" class="btn btn-outline-info toolbar-btn">
            前回コピー
        </button>
    </form>
    <button type="button" class="btn btn-secondary toolbar-btn" onclick="randomize()">
        ランダム配置
    </button>
    <button type="button" class="btn btn-outline-danger toolbar-btn" onclick="clearAll()">
    全員未配置
    </button>
</div>

<div id="saveStatus" class="save-status mb-2">
    保存済み
</div>

@if(session('success'))
    <div class="alert alert-success flash-message">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger flash-message">
        {{ session('error') }}
    </div>
@endif
<div id="grid" class="grid"></div>

<hr>

<p class="text-muted text-end operation-help">
    スマホ：長押しで欠席 / PC：ダブルクリックで欠席
</p>

<h5 class="pool-title">未配置</h5>
<div id="pool" class="pool"></div>

<div id="membersSource" style="display:none;">
@foreach($members as $m)
    <div class="source-member {{ $m->is_absent ? 'absent' : '' }}"
         data-id="{{ $m->id }}"
         data-position="{{ $m->position }}"
         data-gender="{{ $m->user->gender }}">
        {{ $m->user->name }}
    </div>
@endforeach
</div>

</div>
<script>
window.lineupData = {
    lineupId: {{ $lineup->id }},
};
</script>
@endsection