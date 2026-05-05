@extends('layouts.user')

@section('content')

<style>
.monthly-page {
    max-width: 900px;
    margin: 16px auto;
    padding: 10px;
}

.month-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
}

.month-title {
    font-size: 20px;
    font-weight: bold;
    text-align: center;
}

.record-card {
    background: #fff;
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}

.user-name {
    font-weight: bold;
    margin-bottom: 8px;
}

.score-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
}

.no-record {
    color: #999;
}

@media (max-width: 600px) {
    .month-title {
        font-size: 17px;
    }

    .score-row {
        font-size: 13px;
    }
}
</style>

<div class="monthly-page">

    <h3>{{ $group->name }} 月間記録</h3>

    <div class="month-nav">
        <a href="{{ route('group.monthlyRecords', ['group' => $group->id, 'month' => $prevMonth]) }}"
           class="btn btn-outline-secondary">
            ＜
        </a>

        <div class="month-title">
            {{ $currentMonth->format('Y年n月') }}
        </div>

        <a href="{{ route('group.monthlyRecords', ['group' => $group->id, 'month' => $nextMonth]) }}"
           class="btn btn-outline-secondary">
            ＞
        </a>
    </div>

    @foreach ($monthlyRecords as $row)
        <div class="record-card">
            <div class="user-name">
                {{ $row['user']->name }}
            </div>

            @if ($row['shots'] > 0)
                <div class="score-row">
                    <span>射数：{{ $row['shots'] }}</span>
                    <span>的中：{{ $row['hits'] }}</span>
                    <span>的中率：{{ $row['rate'] }}%</span>
                </div>
            @else
                <div class="no-record">
                    記録なし
                </div>
            @endif
        </div>
    @endforeach

</div>

@endsection