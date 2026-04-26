@extends('layouts.user')

@section('content')
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<div class="container py-3">

<h4>{{ $group->name }}｜立順設定</h4>

<form method="GET" action="/group/{{ $group->id }}/lineup" class="mb-3 text-center">
    <div class="d-flex justify-content-center align-items-center gap-3">
        <a href="/group/{{ $group->id }}/lineup?date={{ \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d') }}"
           class="btn btn-outline-secondary">＜</a>

        <input type="date"
               name="date"
               value="{{ $date }}"
               onchange="this.form.submit()"
               class="form-control text-center"
               style="max-width:180px;">

        <a href="/group/{{ $group->id }}/lineup?date={{ \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d') }}"
           class="btn btn-outline-secondary">＞</a>
    </div>
</form>

<div style="display:flex; gap:10px; margin-bottom:10px; justify-content:flex-end; flex-wrap:wrap;">
    <select id="tateSize" class="form-select" style="width:120px;">
        @for($i = 3; $i <= 15; $i++)
            <option value="{{ $i }}" {{ $lineup->tate_size == $i ? 'selected' : '' }}>
                {{ $i }}人立
            </option>
        @endfor
    </select>
    <button type="button" class="btn btn-secondary" onclick="randomize()">ランダム配置</button>
    <a href="/group/{{ $group->id }}/records?date={{ $date }}" class="btn btn-success">記録へ</a>
</div>



<div id="saveStatus" class="text-end text-muted mb-2" style="font-size:13px;">
    保存済み
</div>

<div id="grid" class="grid"></div>

<hr>

<p class="text-muted text-end">
スマホ：長押しで欠席 / PC：ダブルクリックで欠席
</p>

<h5>未配置</h5>
<div id="pool" class="pool"></div>

<div id="membersSource" style="display:none;">
@foreach($members as $m)
    <div class="source-member {{ $m->is_absent ? 'absent' : '' }}"
         data-id="{{ $m->id }}"
         data-position="{{ $m->position }}">
        {{ $m->user->name }}
    </div>
@endforeach
</div>

</div>

<style>
.grid {
    display: grid;
    gap: 8px;
}

.cell {
    border: 2px dashed #ccc;
    height: 68px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fafafa;
    position: relative;
}

.cell.drag-over,
.pool.drag-over,
.cell.tap-target,
.pool.tap-target {
    background:#eef6ff;
    border-color:#0d6efd;
}

.cell-number {
    position:absolute;
    top:2px;
    right:5px;
    font-size:11px;
    color:#999;
    pointer-events:none;
}

.member {
    background:#eee;
    padding:6px 10px;
    border-radius:6px;
    cursor:grab;
    user-select:none;
    text-align:center;
    touch-action: manipulation;
}

.member.selected {
    background:#0d6efd;
    color:white;
    outline:3px solid rgba(13,110,253,.25);
}

.member.absent {
    background:#999;
    color:white;
}

.member.absent.selected {
    background:#555;
}

.pool {
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    min-height:80px;
    border:2px dashed #ddd;
    padding:10px;
}

@media (max-width:600px){
    .cell { height:62px; }
    .member { font-size:13px; padding:5px 8px; }
}
</style>

<script>
let dragged = null;
let selectedMember = null;
let saveTimer = null;
let longPressTimer = null;
let longPressed = false;

const grid = document.getElementById('grid');
const pool = document.getElementById('pool');
const tateSelect = document.getElementById('tateSize');
const source = document.getElementById('membersSource');
const saveStatus = document.getElementById('saveStatus');

function makeMember(sourceEl) {
    const div = document.createElement('div');
    div.className = 'member';

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

function renderGrid() {
    const size = parseInt(tateSelect.value);
    const total = source.querySelectorAll('.source-member').length;
    const rows = Math.max(1, Math.ceil(total / size));

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
    initMembers(true);
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

initMembers(false);
</script>
@endsection