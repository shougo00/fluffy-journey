@extends('layouts.user')

@section('content')
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

<style>
* {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}
.lineup-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 14px;
}

.date-nav {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
}

.date-input {
    max-width: 180px;
}

.nav-btn {
    width: 44px;
}

.lineup-toolbar {
    display: grid;
    grid-template-columns: 120px 1fr 1fr 1fr 1fr;
    gap: 8px;
    align-items: center;
}

.toolbar-form {
    margin: 0;
}

.toolbar-item,
.toolbar-btn {
    width: 100%;
    white-space: nowrap;
}

.save-status {
    text-align: right;
    color: #6c757d;
    font-size: 13px;
}

.grid {
    display: grid;
    gap: 8px;
}

.cell {
    border: 2px dashed #ccc;
    height: 68px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fafafa;
    position: relative;
    border-radius: 8px;
}

.cell.drag-over,
.pool.drag-over,
.cell.tap-target,
.pool.tap-target {
    background: #eef6ff;
    border-color: #0d6efd;
}

.cell-number {
    position: absolute;
    top: 2px;
    right: 5px;
    font-size: 11px;
    color: #999;
    pointer-events: none;
}

.member {
    background: #eee;
    padding: 6px 10px;
    border-radius: 6px;
    cursor: grab;
    user-select: none;
    text-align: center;
    touch-action: manipulation;
}

.member.selected {
    background: #0d6efd;
    color: white;
    outline: 3px solid rgba(13,110,253,.25);
}
.member.male {
    background: #daf1fd;
    color: #084c61;
}

.member.female {
    background: #fedceb;
    color: #7a1f45;
}

/* 欠席は男女関係なく暗い色 */
.member.absent {
    background: #666 !important;
    color: white !important;
}

.member.absent.selected {
    background: #444 !important;
}

.operation-help {
    font-size: 13px;
}

.pool-title {
    font-size: 16px;
    font-weight: 700;
}

.pool {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    min-height: 80px;
    border: 2px dashed #ddd;
    padding: 10px;
    border-radius: 8px;
}
/* 立順マス内のメンバー */
.cell .member {
    width: 100%;
    height: 100%;
}

/* 未配置エリアのメンバーも同じ大きさに固定 */
.pool .member {
    width: 90px;
    height: 42px;
    flex: 0 0 90px;
}

/* スマホ */
@media (max-width: 600px) {
    .pool .member {
        width: 78px;
        height: 38px;
        flex: 0 0 78px;
    }
}

/* スマホ */
@media (max-width: 600px) {
    .lineup-title {
        font-size: 18px;
        text-align: center;
    }

    .date-nav {
        gap: 6px;
    }

    .date-input {
        max-width: 160px;
    }

    .nav-btn {
        width: 40px;
        padding-left: 0;
        padding-right: 0;
    }

    .lineup-toolbar {
        grid-template-columns: 1fr 1fr;
    }

    .toolbar-item {
        grid-column: span 2;
    }

    .toolbar-btn {
        font-size: 14px;
        padding: 8px 6px;
    }

    .cell {
        height: 62px;
    }

    .member {
        font-size: 13px;
        padding: 5px 8px;
    }

    .save-status {
        text-align: right;
        font-size: 12px;
    }
}
</style>

<script>
const grid = document.getElementById('grid');
const pool = document.getElementById('pool');
const tateSelect = document.getElementById('tateSize');
const source = document.getElementById('membersSource');
const saveStatus = document.getElementById('saveStatus');

let dragged = null;
let selectedMember = null;
let saveTimer = null;
let longPressTimer = null;
let longPressed = false;
let extraRows = 0;
let currentTateSize = parseInt(tateSelect.value);

function makeMember(sourceEl) {
    const div = document.createElement('div');
    div.className = 'member';

    if (sourceEl.dataset.gender === 'male') {
        div.classList.add('male');
    }

    if (sourceEl.dataset.gender === 'female') {
        div.classList.add('female');
    }

    if (sourceEl.classList.contains('absent')) {
        div.classList.add('absent');
    }

    div.draggable = true;
    div.dataset.id = sourceEl.dataset.id;
    div.textContent = sourceEl.textContent.trim();

    // ===== PCドラッグ =====
    div.addEventListener('dragstart', () => {
        dragged = div;
        setTimeout(() => div.style.opacity = '0.5', 0);
    });

    div.addEventListener('dragend', () => {
        div.style.opacity = '1';
        dragged = null;
        clearDragOver();
    });

    // ===== スマホ長押し（欠席） =====
    div.addEventListener('touchstart', () => {
        longPressed = false;
        longPressTimer = setTimeout(() => {
            longPressed = true;
            div.classList.toggle('absent');
            selectMember(null);
            autoSave();
        }, 600);
    }, { passive: true });

    div.addEventListener('touchend', () => {
        clearTimeout(longPressTimer);
    });

    div.addEventListener('touchmove', () => {
        clearTimeout(longPressTimer);
    });

    // ===== PCダブルクリック（欠席）←これ追加 =====
    div.addEventListener('dblclick', (e) => {
        e.stopPropagation();
        div.classList.toggle('absent');
        selectMember(null);
        autoSave();
    });

    // ===== タップ（選択・入れ替え） =====
    div.addEventListener('click', (e) => {
        e.stopPropagation();

        if (longPressed) {
            longPressed = false;
            return;
        }

        // 入れ替え
        if (selectedMember && selectedMember !== div) {
            swapMembers(selectedMember, div);
            selectMember(null);
            autoSave();
            return;
        }

        // 選択ON/OFF
        if (selectedMember === div) {
            selectMember(null);
        } else {
            selectMember(div);
        }
    });

    return div;
}

function swapMembers(a, b) {
    const aParent = a.parentElement;
    const bParent = b.parentElement;

    const aNext = a.nextSibling;
    const bNext = b.nextSibling;

    if (aParent === bParent) {
        bParent.insertBefore(a, bNext);
        aParent.insertBefore(b, aNext);
    } else {
        aParent.insertBefore(b, aNext);
        bParent.insertBefore(a, bNext);
    }
}

function selectMember(member) {
    document.querySelectorAll('.member.selected').forEach(el => {
        el.classList.remove('selected');
    });

    selectedMember = member;

    document.querySelectorAll('.cell, .pool').forEach(el => {
        el.classList.remove('tap-target');
    });

    if (member) {
        member.classList.add('selected');
        document.querySelectorAll('.cell').forEach(el => el.classList.add('tap-target'));
        pool.classList.add('tap-target');
    }
}

function moveSelectedTo(target) {
    if (!selectedMember) return;

    if (target.classList.contains('cell')) {
        const existing = target.querySelector('.member');

        if (existing && existing !== selectedMember) {
            // 空いてないマスなら入れ替え
            swapMembers(selectedMember, existing);
        } else {
            target.appendChild(selectedMember);
        }
    }

    if (target.id === 'pool') {
        pool.appendChild(selectedMember);
    }

    selectMember(null);
    autoSave();
}

function getCellsRightToLeft() {
    const size = parseInt(tateSelect.value);
    const cells = Array.from(document.querySelectorAll('#grid .cell'));
    let ordered = [];

    for (let i = 0; i < cells.length; i += size) {
        const row = cells.slice(i, i + size);
        ordered = ordered.concat(row.reverse());
    }

    return ordered;
}

function getRealMembers() {
    return Array.from(document.querySelectorAll('#grid .member, #pool .member'));
}

function clearDragOver() {
    document.querySelectorAll('.drag-over').forEach(el => {
        el.classList.remove('drag-over');
    });
}

function getMaxSavedPosition() {
    let max = 0;

    source.querySelectorAll('.source-member').forEach(el => {
        const pos = parseInt(el.dataset.position);
        if (!isNaN(pos)) {
            max = Math.max(max, pos);
        }
    });

    return max;
}
function renderGrid(minRows = 0) {
    const size = parseInt(tateSelect.value);
    const total = source.querySelectorAll('.source-member').length;

    const baseRows = Math.max(1, Math.ceil(total / size));
    const savedRows = Math.ceil(getMaxSavedPosition() / size);

    const rows = Math.max(baseRows, savedRows, minRows) + extraRows;

    grid.innerHTML = '';
    grid.style.gridTemplateColumns = `repeat(${size}, 1fr)`;

    for (let i = 0; i < rows * size; i++) {
        const cell = document.createElement('div');
        cell.className = 'cell';

        const num = document.createElement('span');
        num.className = 'cell-number';
        cell.appendChild(num);

        cell.addEventListener('click', () => {
            moveSelectedTo(cell);
        });

        cell.addEventListener('dragover', e => {
            e.preventDefault();
            clearDragOver();
            cell.classList.add('drag-over');
        });

        cell.addEventListener('drop', e => {
            e.preventDefault();

            if (!dragged) return;

            const existing = cell.querySelector('.member');

            if (existing && existing !== dragged) {
                swapMembers(dragged, existing);
            } else {
                cell.appendChild(dragged);
            }

            clearDragOver();
            autoSave();
        });

        grid.appendChild(cell);
    }

    setCellNumbers();
}
function getCellsRightToLeftBySize(size) {
    const cells = Array.from(document.querySelectorAll('#grid .cell'));
    let ordered = [];

    for (let i = 0; i < cells.length; i += size) {
        const row = cells.slice(i, i + size);
        ordered = ordered.concat(row.reverse());
    }

    return ordered;
}

function rerenderKeepingMembers(oldSize, newSize) {
    const items = [];
    let maxNewPosition = 0;

    getCellsRightToLeftBySize(oldSize).forEach((cell, index) => {
        const member = cell.querySelector('.member');

        if (member) {
            const oldPosition = index + 1;
            const oldTateNo = Math.ceil(oldPosition / oldSize);
            const indexInTate = ((oldPosition - 1) % oldSize) + 1;

            let newPosition = ((oldTateNo - 1) * newSize) + indexInTate;

            if (newSize < oldSize && indexInTate > newSize) {
                newPosition = null;
            }

            if (newPosition) {
                maxNewPosition = Math.max(maxNewPosition, newPosition);
            }

            items.push({
                member: member,
                position: newPosition
            });
        }
    });

    pool.querySelectorAll('.member').forEach(member => {
        items.push({
            member: member,
            position: null
        });
    });

    const requiredRows = Math.ceil(maxNewPosition / newSize);

    renderGrid(requiredRows);

    const cells = getCellsRightToLeft();

    items.forEach(item => {
        if (item.position && cells[item.position - 1]) {
            cells[item.position - 1].appendChild(item.member);
        } else {
            pool.appendChild(item.member);
        }
    });
}

function addLineupRow() {
    extraRows++;
    rerenderKeepingMembers(currentTateSize, currentTateSize);
    autoSave();
}

function setCellNumbers() {
    const cells = getCellsRightToLeft();

    cells.forEach((cell, index) => {
        const num = cell.querySelector('.cell-number');
        if (num) num.textContent = index + 1;
    });
}

function initMembers(reset = false) {
    pool.innerHTML = '';
    renderGrid();

    Array.from(source.querySelectorAll('.source-member')).forEach(sourceEl => {
        const member = makeMember(sourceEl);
        const position = reset ? null : sourceEl.dataset.position;

        if (position) {
            const cells = getCellsRightToLeft();
            const cell = cells[parseInt(position) - 1];

            if (cell) cell.appendChild(member);
            else pool.appendChild(member);
        } else {
            pool.appendChild(member);
        }
    });
}

pool.addEventListener('click', () => {
    moveSelectedTo(pool);
});

pool.addEventListener('dragover', e => {
    e.preventDefault();
    clearDragOver();
    pool.classList.add('drag-over');
});

pool.addEventListener('drop', e => {
    e.preventDefault();

    if (dragged) {
        pool.appendChild(dragged);
    }

    clearDragOver();
    autoSave();
});

tateSelect.addEventListener('change', () => {
    const newSize = parseInt(tateSelect.value);

    rerenderKeepingMembers(currentTateSize, newSize);

    currentTateSize = newSize;

    autoSave();
});

function randomize() {
    // 未配置にいる人だけ対象
    const members = Array.from(pool.querySelectorAll('.member'))
        .filter(member => !member.classList.contains('absent'));

    const absentMembers = Array.from(pool.querySelectorAll('.member'))
        .filter(member => member.classList.contains('absent'));

    for (let i = members.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [members[i], members[j]] = [members[j], members[i]];
    }

    // 空いているマスだけ取得
    const emptyCells = getCellsRightToLeft()
        .filter(cell => !cell.querySelector('.member'));

    members.forEach((member, index) => {
        if (emptyCells[index]) {
            emptyCells[index].appendChild(member);
        }
    });

    absentMembers.forEach(member => {
        pool.appendChild(member);
    });

    autoSave();
}

function autoSave() {
    saveStatus.innerText = '保存中...';

    clearTimeout(saveTimer);

    saveTimer = setTimeout(() => {
        save(false);
    }, 400);
}

function save(showAlert = false) {
    let list = [];
    const cells = getCellsRightToLeft();

    cells.forEach((cell, index) => {
        const member = cell.querySelector('.member');

        if (member) {
            list.push({
                id: member.dataset.id,
                position: index + 1,
                absent: member.classList.contains('absent')
            });
        }
    });

    document.querySelectorAll('#pool .member').forEach(member => {
        list.push({
            id: member.dataset.id,
            position: null,
            absent: member.classList.contains('absent')
        });
    });

    fetch(`/lineup/{{ $lineup->id }}/save`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            members: list,
            tate_size: tateSelect.value
        })
    })
    .then(res => res.json())
    .then(() => {
        saveStatus.innerText = '保存済み';
        if (showAlert) alert('保存OK');
    })
    .catch(() => {
        saveStatus.innerText = '保存失敗';
        if (showAlert) alert('保存に失敗しました');
    });
}
function clearAll() {

    document.querySelectorAll('#grid .member').forEach(member => {
        pool.appendChild(member);
    });

    selectMember(null);
    autoSave();
}
document.addEventListener('DOMContentLoaded', function () {
    const flashes = document.querySelectorAll('.flash-message');

    flashes.forEach(flash => {
        setTimeout(() => {
            flash.style.transition = 'opacity 0.5s, transform 0.5s';
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-10px)';

            setTimeout(() => {
                flash.remove();
            }, 500);
        }, 2000);
    });
});


initMembers(false);
</script>
@endsection