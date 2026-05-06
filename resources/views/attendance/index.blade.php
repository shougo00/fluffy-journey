@extends('layouts.user')

@section('content')

<div class="container py-3">

<h4>{{ $group->name }}｜出欠確認</h4>

<form method="GET" action="/group/{{ $group->id }}/attendance" class="mb-4 text-center">
    <div class="d-flex justify-content-center align-items-center gap-3">

        <a href="/group/{{ $group->id }}/attendance?date={{ \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d') }}"
           class="btn btn-outline-secondary">
            ＜
        </a>

        <input type="date"
               name="date"
               value="{{ $date }}"
               onchange="this.form.submit()"
               class="form-control text-center"
               style="max-width:180px;">

        <a href="/group/{{ $group->id }}/attendance?date={{ \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d') }}"
           class="btn btn-outline-secondary">
            ＞
        </a>

    </div>
</form>

<div class="attendance-card">

    <div class="user-name">
        {{ $user->name }}
    </div>

    <div class="status-text mb-3" id="statusText">
        {{ $member->is_absent ? '現在：欠席' : '現在：出席' }}
    </div>

    <div class="d-flex gap-3 justify-content-center">
        <button type="button"
                id="presentBtn"
                class="btn {{ !$member->is_absent ? 'btn-success' : 'btn-outline-success' }}"
                onclick="setAttendance(false)">
            出席
        </button>

        <button type="button"
                id="absentBtn"
                class="btn {{ $member->is_absent ? 'btn-danger' : 'btn-outline-danger' }}"
                onclick="setAttendance(true)">
            欠席
        </button>
    </div>

    <div id="saveStatus" class="text-muted mt-3" style="font-size:13px;">
        保存済み
    </div>

    <div class="form-check form-switch d-flex justify-content-center align-items-center gap-2 mb-3">
        <input class="form-check-input"
            type="checkbox"
            id="allAbsentSwitch"
            {{ $user->all_absent ? 'checked' : '' }}
            onchange="setAllAbsent(this.checked)">

        <label class="form-check-label" for="allAbsentSwitch">
            全ての日を欠席にする
        </label>
    </div>
    <div class="mt-4 text-center">
    <div class="text-muted mb-2" style="font-size:13px;">
        グループLINE共有用URL
        <br>
        ※アナウンス機能を使うことで出欠確認フォームとして使用できます。
    </div>

    <input type="text"
           id="attendanceUrl"
           class="form-control text-center mb-2"
           value="https://pacific-mesa-32015-2c2948e94167.herokuapp.com/group/{{ $group->id }}/attendance"
           readonly>

    <button type="button"
            class="btn btn-outline-primary"
            onclick="copyAttendanceUrl()">
        URLをコピー
    </button>

    <div id="copyStatus" class="text-muted mt-2" style="font-size:13px;"></div>
</div>

</div>

</div>

<style>
.attendance-card {
    max-width: 400px;
    margin: 0 auto;
    padding: 24px;
    border: 1px solid #ddd;
    border-radius: 12px;
    text-align: center;
    background: #fff;
}

.user-name {
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 10px;
}

.status-text {
    font-size: 16px;
    font-weight: bold;
}

.attendance-card .btn {
    min-width: 120px;
    padding: 12px;
    font-size: 18px;
}
</style>

<script>
function setAttendance(isAbsent) {
    const presentBtn = document.getElementById('presentBtn');
    const absentBtn = document.getElementById('absentBtn');
    const statusText = document.getElementById('statusText');
    const saveStatus = document.getElementById('saveStatus');

    if (isAbsent) {
        presentBtn.className = 'btn btn-outline-success';
        absentBtn.className = 'btn btn-danger';
        statusText.innerText = '現在：欠席';
    } else {
        presentBtn.className = 'btn btn-success';
        absentBtn.className = 'btn btn-outline-danger';
        statusText.innerText = '現在：出席';
    }

    saveStatus.innerText = '保存中...';

    fetch('/group/{{ $group->id }}/attendance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            date: '{{ $date }}',
            absent: isAbsent
        })
    })
    .then(res => res.json())
    .then(() => {
        saveStatus.innerText = '保存済み';
    })
    .catch(() => {
        saveStatus.innerText = '保存失敗';
    });
}
function setAllAbsent(isAllAbsent) {
    const saveStatus = document.getElementById('saveStatus');

    saveStatus.innerText = '保存中...';

    fetch('/group/{{ $group->id }}/attendance/all-absent', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            all_absent: isAllAbsent
        })
    })
    .then(res => res.json())
    .then(() => {
        saveStatus.innerText = isAllAbsent
            ? '全ての日を欠席にしました'
            : '全ての日の欠席を解除しました';
    })
    .catch(() => {
        saveStatus.innerText = '保存失敗';
    });
}
function copyAttendanceUrl() {
    const urlInput = document.getElementById('attendanceUrl');
    const copyStatus = document.getElementById('copyStatus');

    navigator.clipboard.writeText(urlInput.value)
        .then(() => {
            copyStatus.innerText = 'URLをコピーしました';
        })
        .catch(() => {
            urlInput.select();
            document.execCommand('copy');
            copyStatus.innerText = 'URLをコピーしました';
        });
}
</script>

@endsection
