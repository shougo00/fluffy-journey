@extends('layouts.user')

@section('content')

<div class="container py-3">

<h4>{{ $group->name }}｜立順設定</h4>

<form method="GET" action="/group/{{ $group->id }}/lineup" class="mb-3 d-flex gap-2">
    <input type="date" name="date" value="{{ $date }}" class="form-control">
    <button class="btn btn-outline-primary">表示</button>
</form>

<div style="display:flex; gap:10px; margin-bottom:10px; justify-content:flex-end; flex-wrap:wrap;">
    <select id="tateSize" class="form-select" style="width:120px;">
        <option value="3" {{ $lineup->tate_size == 3 ? 'selected' : '' }}>3人立</option>
        <option value="4" {{ $lineup->tate_size == 4 ? 'selected' : '' }}>4人立</option>
        <option value="5" {{ $lineup->tate_size == 5 ? 'selected' : '' }}>5人立</option>
    </select>

    <button type="button" class="btn btn-secondary" onclick="randomize()">ランダム</button>
    <button type="button" class="btn btn-primary" onclick="save()">保存</button>
    <a href="/group/{{ $group->id }}/records?date={{ $date }}" class="btn btn-success">記録へ</a>
</div>

<p class="text-muted text-end">右端が1番です。未配置の人は記録画面に出ません。</p>

<div id="grid" class="grid"></div>

<hr>

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
.pool.drag-over {
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
}

.member.absent {
    background:#999;
    color:white;
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

const grid = document.getElementById('grid');
const pool = document.getElementById('pool');
const tateSelect = document.getElementById('tateSize');
const source = document.getElementById('membersSource');

function makeMember(sourceEl) {
    const div = document.createElement('div');
    div.className = 'member';

    if (sourceEl.classList.contains('absent')) {
        div.classList.add('absent');
    }

    div.draggable = true;
    div.dataset.id = sourceEl.dataset.id;
    div.textContent = sourceEl.textContent.trim();

    div.addEventListener('dragstart', () => {
        dragged = div;
        setTimeout(() => div.style.opacity = '0.5', 0);
    });

    div.addEventListener('dragend', () => {
        div.style.opacity = '1';
        dragged = null;
        clearDragOver();
    });

    div.addEventListener('click', () => {
        div.classList.toggle('absent');
    });

    return div;
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
                pool.appendChild(existing);
            }

            cell.appendChild(dragged);
            clearDragOver();
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

function collectAll() {
    getRealMembers().forEach(member => {
        pool.appendChild(member);
    });
}

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
});

tateSelect.addEventListener('change', () => {
    initMembers(true);
});

function randomize() {
    collectAll();

    const members = Array.from(pool.querySelectorAll('.member'))
        .filter(member => !member.classList.contains('absent'));

    const absentMembers = Array.from(pool.querySelectorAll('.member'))
        .filter(member => member.classList.contains('absent'));

    for (let i = members.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [members[i], members[j]] = [members[j], members[i]];
    }

    document.querySelectorAll('#grid .cell').forEach(cell => {
        const num = cell.querySelector('.cell-number');
        cell.innerHTML = '';
        if (num) cell.appendChild(num);
    });

    const cells = getCellsRightToLeft();

    members.forEach((member, index) => {
        if (cells[index]) cells[index].appendChild(member);
    });

    absentMembers.forEach(member => {
        pool.appendChild(member);
    });
}

function save() {
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
    .then(() => alert('保存OK'))
    .catch(() => alert('保存に失敗しました'));
}

initMembers(false);
</script>

@endsection