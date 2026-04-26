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
</script>

@endsection
