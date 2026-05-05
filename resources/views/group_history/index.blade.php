@extends('layouts.user')

@section('content')

<style>
.history-page {
    max-width: 900px;
    margin: 16px auto;
    padding: 10px;
}

.filter-box,
.rank-card {
    background: #fff;
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}

.filter-box {
    margin-bottom: 16px;
}

.section-title {
    background: #111;
    color: white;
    padding: 10px;
    border-radius: 10px;
    margin: 20px 0 10px;
    font-weight: bold;
}

.rank-card {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rank-no {
    font-size: 20px;
    font-weight: bold;
    width: 36px;
    text-align: center;
}

.rank-info {
    flex: 1;
}

.user-name {
    font-weight: bold;
    margin-bottom: 6px;
}

.score-line {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px solid #eee;
    padding: 3px 0;
    font-size: 14px;
}

.score-line:last-child {
    border-bottom: none;
}

.active-score {
    background: #fff3cd;
    font-weight: bold;
    border-radius: 6px;
    padding-left: 6px;
    padding-right: 6px;
}

.page-tabs {
    display: flex;
    gap: 8px;
    margin: 12px 0 16px;
    background: #eee;
    padding: 6px;
    border-radius: 12px;
}

.page-tab {
    flex: 1;
    text-align: center;
    padding: 10px;
    border-radius: 10px;
    text-decoration: none;
    color: #333;
    font-weight: bold;
    background: transparent;
}

.page-tab.active {
    background: #111;
    color: #fff;
}

.month-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.month-title {
    font-size: 18px;
    font-weight: bold;
}

.monthly-card {
    align-items: stretch;
}

.monthly-card .user-name {
    font-size: 16px;
}

.title-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

/* 通常画面では印刷用表は非表示 */
.print-area {
    display: none;
}

@media (max-width: 600px) {
    .rank-card {
        gap: 8px;
        padding: 10px;
    }

    .score-line {
        font-size: 13px;
    }
}

/* 印刷時だけ表を出す */
@media print {
    nav,
    header,
    footer,
    .navbar,
    .page-tabs,
    .filter-box,
    .month-nav,
    .rank-card,
    .title-bar button {
        display: none !important;
    }

    .history-page {
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .title-bar {
        display: block !important;
        text-align: center;
        margin-bottom: 8px;
    }

    .title-bar h3 {
        font-size: 18px;
        margin: 0 0 8px;
    }

    .print-area {
        display: block !important;
    }

    @page {
        size: A4 landscape;
        margin: 8mm;
    }

    table.print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }

    .print-table th,
    .print-table td {
        border: 1px solid #000;
        padding: 5px;
        text-align: center;
    }

    .print-table th {
        background: #eee !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .print-table .name-col {
        text-align: left;
        white-space: nowrap;
    }
}
</style>

@php
    $view = $view ?? request('view', 'ranking');
@endphp

<div class="history-page">

    <div class="title-bar">
        <h3>{{ $group->name }} 記録</h3>

        @if ($view === 'monthly')
            <button type="button"
                    class="btn btn-outline-primary btn-sm"
                    onclick="window.print()">
                印刷
            </button>
        @endif
    </div>

    <div class="page-tabs">
        <a href="{{ route('group.history', [
                'group' => $group->id,
                'view' => 'ranking',
                'score_type' => $scoreType,
                'period' => $period,
                'limit' => $limit
            ]) }}"
           class="page-tab {{ $view === 'ranking' ? 'active' : '' }}">
            ランキング
        </a>

        <a href="{{ route('group.history', [
                'group' => $group->id,
                'view' => 'monthly',
                'month' => $month ?? now()->format('Y-m'),
                'score_type' => $scoreType
            ]) }}"
           class="page-tab {{ $view === 'monthly' ? 'active' : '' }}">
            月間記録（{{ $currentMonth->format('Y年n月') }}）
        </a>
    </div>

    @if ($view === 'ranking')

        <form method="GET" class="filter-box">
            <input type="hidden" name="view" value="ranking">

            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label">集計</label>
                    <select name="score_type" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ $scoreType === 'all' ? 'selected' : '' }}>総合</option>
                        <option value="official" {{ $scoreType === 'official' ? 'selected' : '' }}>正規練</option>
                        <option value="self" {{ $scoreType === 'self' ? 'selected' : '' }}>自主練</option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">期間</label>
                    <select name="period" class="form-select" onchange="this.form.submit()">
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>今日</option>
                        <option value="week" {{ $period === 'week' ? 'selected' : '' }}>週間</option>
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>月間</option>
                        <option value="year" {{ $period === 'year' ? 'selected' : '' }}>年間</option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">表示人数</label>
                    <select name="limit" class="form-select" onchange="this.form.submit()">
                        <option value="5" {{ (string)$limit === '5' ? 'selected' : '' }}>上位5人</option>
                        <option value="10" {{ (string)$limit === '10' ? 'selected' : '' }}>上位10人</option>
                        <option value="20" {{ (string)$limit === '20' ? 'selected' : '' }}>上位20人</option>
                        <option value="all" {{ (string)$limit === 'all' ? 'selected' : '' }}>全員</option>
                    </select>
                </div>
            </div>
        </form>

        <div class="section-title">男子の部</div>

        @forelse ($maleRanking as $index => $row)
            @include('group_history.partials.rank_card', [
                'rank' => $index + 1,
                'row' => $row,
                'scoreType' => $scoreType
            ])
        @empty
            <p>男子の記録はありません。</p>
        @endforelse

        <div class="section-title">女子の部</div>

        @forelse ($femaleRanking as $index => $row)
            @include('group_history.partials.rank_card', [
                'rank' => $index + 1,
                'row' => $row,
                'scoreType' => $scoreType
            ])
        @empty
            <p>女子の記録はありません。</p>
        @endforelse

    @else

        <div class="month-nav">
            <a href="{{ route('group.history', [
                    'group' => $group->id,
                    'view' => 'monthly',
                    'month' => $prevMonth,
                    'score_type' => $scoreType
                ]) }}"
               class="btn btn-outline-secondary">
                ＜
            </a>

            <div class="month-title">
                {{ $currentMonth->format('Y年n月') }}
            </div>

            <a href="{{ route('group.history', [
                    'group' => $group->id,
                    'view' => 'monthly',
                    'month' => $nextMonth,
                    'score_type' => $scoreType
                ]) }}"
               class="btn btn-outline-secondary">
                ＞
            </a>
        </div>

        {{-- 画面表示用カード --}}
        @foreach ($monthlyRecords as $row)
            <div class="rank-card monthly-card">
                <div class="rank-info">

                    <div class="user-name">
                        {{ $row['user']->name }}
                    </div>

                    <div class="score-line">
                        <span>総合</span>
                        <span>
                            {{ $row['all']['shots'] }}射
                            {{ $row['all']['hits'] }}中
                            {{ $row['all']['rate'] }}%
                        </span>
                    </div>

                    <div class="score-line">
                        <span>正規練</span>
                        <span>
                            {{ $row['official']['shots'] }}射
                            {{ $row['official']['hits'] }}中
                            {{ $row['official']['rate'] }}%
                        </span>
                    </div>

                    <div class="score-line">
                        <span>自主練</span>
                        <span>
                            {{ $row['self']['shots'] }}射
                            {{ $row['self']['hits'] }}中
                            {{ $row['self']['rate'] }}%
                        </span>
                    </div>

                </div>
            </div>
        @endforeach

        {{-- 印刷用表 --}}
        <div class="print-area">
            <h3 style="text-align:center;">
                {{ $group->name }} 月間記録（{{ $currentMonth->format('Y年n月') }}）
            </h3>

            <table class="print-table">
                <thead>
                    <tr>
                        <th rowspan="2">名前</th>
                        <th colspan="3">正規練</th>
                        <th colspan="3">自主練</th>
                        <th colspan="3">総合</th>
                    </tr>
                    <tr>
                        <th>射数</th>
                        <th>的中数</th>
                        <th>的中率</th>

                        <th>射数</th>
                        <th>的中数</th>
                        <th>的中率</th>

                        <th>射数</th>
                        <th>的中数</th>
                        <th>的中率</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($monthlyRecords as $row)
                        <tr>
                            <td class="name-col">{{ $row['user']->name }}</td>

                            <td>{{ $row['official']['shots'] }}</td>
                            <td>{{ $row['official']['hits'] }}</td>
                            <td>{{ $row['official']['rate'] }}%</td>

                            <td>{{ $row['self']['shots'] }}</td>
                            <td>{{ $row['self']['hits'] }}</td>
                            <td>{{ $row['self']['rate'] }}%</td>

                            <td>{{ $row['all']['shots'] }}</td>
                            <td>{{ $row['all']['hits'] }}</td>
                            <td>{{ $row['all']['rate'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    @endif

</div>

@endsection