@extends('layouts.user')

@section('content')

<style>
/* ===== 今日・月間・年間 ===== */
.summary-wrapper { display: flex; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; }
.summary-box { font-size: 13px; }
.summary-box .summary-title { color: #888; font-size: 11px; margin-bottom: 2px; }
.summary-box .summary-value { font-weight: 500; line-height: 1.4; white-space: pre-line; }

/* ===== 月ナビ ===== */
.month-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }

/* ===== タイプ切替 ===== */
.type-switch { display: flex; gap: 4px; justify-content: flex-end; margin-bottom: 6px; }
.type-switch button { flex: 0 0 auto; }

/* ===== カレンダー ===== */
.calendar-wrapper { position: relative; overflow-x: auto; }
.calendar {
    display: grid;
    grid-template-columns: repeat(7,1fr);
    gap: 0;
    border: 1px solid #ccc;
}
.day, .day-header {
    border: 1px solid #ccc;
    padding: 6px 2px;
    font-size: 11px;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 60px;
}
.day-header {
    font-weight: bold;
    background-color: #f0f0f0;
}
.day.sunday .date { color: red; }
.day.saturday .date { color: blue; }

/* カレンダー全体背景 */
.bg-official { background-color: #ffe6e6; }
.bg-self     { background-color: #e6f2ff; }
.bg-all      { background-color: #e0e0e0; }

/* スマホ対応 */
@media (max-width: 600px) {
    .summary-title{  font-size: 20px !important; }
    .summary-wrapper { flex-direction: column; gap: 15px; }
    .summary-box { text-align: center !important; font-size: 20px; }
    .month-nav strong { font-size: 20px; }
    .type-switch { overflow-x: auto; }
    .type-switch button { font-size: 20px; padding: 4px 6px; }
    .calendar .day, .calendar .day-header { min-height: 50px; font-size: 10px; padding: 4px 2px; }
}
</style>

<div class="container py-2">

    <!-- 今日 & 月間 & 年間 -->
    <div class="summary-wrapper">
        <div class="summary-box text-start" style="flex:1;">
            <div class="summary-title">今日の記録</div>
            <div class="summary-value" id="today-summary"></div>
        </div>
        <div class="summary-box text-center" style="flex:1;">
            <div class="summary-title">月間（<span id="month-label"></span>）</div>
            <div class="summary-value" id="month-summary"></div>
        </div>
        <div class="summary-box text-end" style="flex:1;">
            <div class="summary-title">年間（<span id="year-label">{{ date('Y') }}</span>）</div>
            <div class="summary-value" id="year-summary"></div>
        </div>
    </div>

    <!-- 月切替 -->
    <div class="month-nav">
        <a href="#" id="prevMonth" class="btn btn-sm btn-outline-secondary">＜</a>
        <strong>{{ $month }}</strong>
        <a href="#" id="nextMonth" class="btn btn-sm btn-outline-secondary">＞</a>
    </div>

    <!-- タイプ切替ボタン -->
    <div class="type-switch">
        <button onclick="changeType(event,'official')" id="btn-official" class="btn btn-sm btn-outline-danger">正規連</button>
        <button onclick="changeType(event,'self')" id="btn-self" class="btn btn-sm btn-outline-primary">自主練</button>
        <button onclick="changeType(event,'all')" id="btn-all" class="btn btn-sm btn-outline-success">総合</button>
    </div>

    <!-- カレンダー -->
    <div class="calendar-wrapper">
        <div class="calendar" id="calendar">
            @php
                $weekdays = ['日','月','火','水','木','金','土'];
                $firstDay = \Carbon\Carbon::parse($month.'-01');
                $days = $firstDay->daysInMonth;
                $startWeek = $firstDay->dayOfWeek;
            @endphp

            {{-- 曜日ヘッダー --}}
            @foreach($weekdays as $wd)
                <div class="day-header">{{ $wd }}</div>
            @endforeach

            {{-- 空セル --}}
            @for($i = 0; $i < $startWeek; $i++)
                <div class="day empty"></div>
            @endfor

            {{-- 日付 --}}
           @for($i = 1; $i <= $days; $i++)
                @php
                    $dateObj = \Carbon\Carbon::parse($month.'-'.str_pad($i,2,'0',STR_PAD_LEFT));
                    $date = $dateObj->format('Y-m-d');
                    $dayOfWeek = $dateObj->dayOfWeek;
                    $dayClass = '';
                    if($dayOfWeek === 0) $dayClass = 'sunday';
                    elseif($dayOfWeek === 6) $dayClass = 'saturday';
                @endphp
                <div class="day {{ $dayClass }}" data-date="{{ $date }}">
                    <div class="date">{{ $i }}</div>
                </div>
            @endfor
        </div>
    </div>

</div>

<script>
let currentType = new URL(location.href).searchParams.get('type') || @json($type);
const todayData = { official: @json($todayOfficial), self: @json($todaySelf), all: @json($todayAll) };
const monthData = { official: @json($monthOfficial), self: @json($monthSelf), all: @json($monthAll) };
const yearData = { official: @json($yearOfficial), self: @json($yearSelf), all: @json($yearAll) };
const calendarData = @json($calendar);
const prevMonth = @json($prevMonth);
const nextMonth = @json($nextMonth);
const currentMonth = "{{ $month }}";

document.getElementById('month-label').innerText = new Date(currentMonth+'-01').getMonth()+1 + '月';

function updateButtonStyles(){
    document.getElementById('btn-official').className = currentType==='official' ? 'btn btn-sm btn-danger' : 'btn btn-sm btn-outline-danger';
    document.getElementById('btn-self').className     = currentType==='self'     ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary';
    document.getElementById('btn-all').className      = currentType==='all'      ? 'btn btn-sm btn-success' : 'btn btn-sm btn-outline-success';
}

function updateMonthLinks(){
    document.getElementById('prevMonth').href = `?month=${prevMonth}&type=${currentType}`;
    document.getElementById('nextMonth').href = `?month=${nextMonth}&type=${currentType}`;
}

function renderSummary(){
    const t = todayData;
    const m = monthData;
    const y = yearData;
    document.getElementById('today-summary').innerText =
        `総合 ${t.all.shots}射 ${t.all.hits}中 ${t.all.rate}%\n` +
        `正規連 ${t.official.shots}射 ${t.official.hits}中 ${t.official.rate}%\n` +
        `自主練 ${t.self.shots}射 ${t.self.hits}中 ${t.self.rate}%`;
    document.getElementById('month-summary').innerText =
        `総合 ${m.all.shots}射 ${m.all.hits}中 ${m.all.rate}%\n` +
        `正規連 ${m.official.shots}射 ${m.official.hits}中 ${m.official.rate}%\n` +
        `自主練 ${m.self.shots}射 ${m.self.hits}中 ${m.self.rate}%`;
    document.getElementById('year-summary').innerText =
        `総合 ${y.all.shots}射 ${y.all.hits}中 ${y.all.rate}%\n` +
        `正規連 ${y.official.shots}射 ${y.official.hits}中 ${y.official.rate}%\n` +
        `自主練 ${y.self.shots}射 ${y.self.hits}中 ${y.self.rate}%`;
}

function renderCalendar(){
    const cal = document.getElementById('calendar');

    // カレンダー全体の背景
    cal.classList.remove('bg-official','bg-self','bg-all');
    if(currentType==='official') cal.classList.add('bg-official');
    else if(currentType==='self') cal.classList.add('bg-self');
    else cal.classList.add('bg-all');

    document.querySelectorAll('.day').forEach(day=>{
        if(day.classList.contains('empty')) return;
        const date = day.dataset.date;
        const data = calendarData[date]?.[currentType];

        if(data && data.shots > 0){
            day.innerHTML = `<div class="date">${date.split('-')[2]}</div>
                             <div class="data">${data.hits}/${data.shots}</div>
                             <div class="data">${data.rate}%</div>`;
        } else {
            day.innerHTML = `<div class="date">${date.split('-')[2]}</div>`;
        }

        // 総合(all)の時はリンク飛ばさない
        if(currentType !== 'all'){
            day.onclick = () => {
                location.href = `/home?date=${date}&type=${currentType}`;
            };
        } else {
            day.onclick = null; // クリック無効
        }
    });
}

function changeType(e,type){
    currentType = type;
    const url = new URL(window.location);
    url.searchParams.set('type', type);
    window.history.replaceState({}, '', url);
    renderAll();
}

function renderAll(){
    renderSummary();
    renderCalendar();
    updateButtonStyles();
    updateMonthLinks();
}

renderAll();
</script>

@endsection