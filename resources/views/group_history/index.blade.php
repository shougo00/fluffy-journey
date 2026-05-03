@extends('layouts.user')

@section('content')

<style>
.history-page {
    max-width: 900px;
    margin: 16px auto;
    padding: 10px;
}

.filter-box {
    background: #fff;
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
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
    background: white;
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}

.rank-no {
    font-size: 20px;
    font-weight: bold;
    width: 36px;
    text-align: center;
}

.avatar-area {
    width: 52px;
    height: 62px;
    position: relative;
    flex-shrink: 0;
}

.avatar-layer {
    position: absolute;
    object-fit: contain;
}

.avatar-layer.hair { top:0; left:0; width:100%; height:24px; z-index:6; }
.avatar-layer.face { top:13px; left:13px; width:26px; height:26px; z-index:5; }
.avatar-layer.top { top:32px; left:0; width:52px; height:18px; z-index:4; }
.avatar-layer.bottom { top:45px; left:0; width:52px; height:12px; z-index:3; }
.avatar-layer.shoes { top:56px; left:8px; width:36px; height:6px; z-index:2; }
.avatar-layer.accessory { top:0; left:8px; width:36px; height:12px; z-index:7; }

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

@media (max-width: 600px) {
    .rank-card {
        gap: 8px;
        padding: 10px;
    }

    .score-line {
        font-size: 13px;
    }
}
</style>

<div class="history-page">

    <h3>{{ $group->name }} 的中率ランキング</h3>

    <form method="GET" class="filter-box">
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

</div>

@endsection