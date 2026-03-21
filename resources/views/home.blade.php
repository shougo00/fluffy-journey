@extends('layouts.user')

@section('content')
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ===== 全体 ===== */
.my-container {
    width: 100%;
    max-width: 700px;
    margin: 0 auto;
    transition: background 0.3s;
}

/* ===== ボタン共通 ===== */
.shot-btn {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    font-size: 26px;
    border: 2px solid #ccc;
    background: white;
    transition: 0.2s;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    -webkit-text-size-adjust: 100%;
    touch-action: manipulation;
    transform-origin: center;
}
.shot-btn:active { transform: scale(1.05); }

.shot-hit { color: red; font-weight: bold; font-size: 34px; transform: scale(1.1); }
.shot-miss { color: blue; font-weight: bold; font-size: 38px; transform: scale(1.15); }
.shot-none { color: #bbb; border: 2px dashed #ccc; }
.shot-none:hover { border-color: #666; background: #f8f9fa; }

.container.self-bg { background-color: #e6f2ff; }
.container.official-bg { background-color: #ffe6e6; }

/* ボタンタイプ */
.practice-type-buttons .btn { padding: 0.25rem 0.5rem; font-size: 0.85rem; }
.btn-official { background-color: #ff4d4d; color: white; border-color: #ff4d4d; }
.btn-outline-official { background-color: white; color: #ff4d4d; border-color: #ff4d4d; }
.btn-self { background-color: #4da6ff; color: white; border-color: #4da6ff; }
.btn-outline-self { background-color: white; color: #4da6ff; border-color: #4da6ff; }
.btn-official:hover, .btn-outline-official:hover { background-color: #e60000; color: white; }
.btn-self:hover, .btn-outline-self:hover { background-color: #0066cc; color: white; }

.delete-record {
    padding: 2px 6px;
    font-size: 16px;
    color: #7b7b7b;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: color 0.2s, transform 0.1s;
    outline: none;
    box-shadow: none;
}
.delete-record:hover { color: #434242; }
.delete-record:active { transform: scale(0.95); color: #434242; background: transparent; }
</style>

<div class="container py-3 {{ $type=='self' ? 'self-bg' : 'official-bg' }}" id="records-container" data-type="{{ $type }}">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">的中記録</h4>

        <div class="practice-type-buttons">
            <a href="{{ route('home', ['date'=>$date, 'type'=>'official']) }}" 
            class="btn btn-sm {{ ($type ?? 'official') == 'official' ? 'btn-danger' : 'btn-outline-danger' }}">
                正規練
            </a>

            <a href="{{ route('home', ['date'=>$date, 'type'=>'self']) }}" 
            class="btn btn-sm {{ ($type ?? 'official') == 'self' ? 'btn-primary' : 'btn-outline-primary' }}">
                自主練
            </a>
        </div>
    </div>

    <!-- 日付ナビ -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('home', ['date' => $prevDate, 'type'=>$type]) }}" class="btn btn-outline-secondary">＜</a>

        <form id="date-form" method="GET" action="{{ route('home') }}">
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="date" name="date" value="{{ $date }}" class="form-control text-center" id="date-picker">
        </form>

        <a href="{{ route('home', ['date' => $nextDate, 'type'=>$type]) }}" class="btn btn-outline-secondary">＞</a>
    </div>

    <!-- 立追加 -->
    <form method="POST" action="{{ route('records.store') }}" class="mb-3">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="practice_type" value="{{ $type }}">
        <button class="btn btn-primary w-100">＋ 立を追加</button>
    </form>

    <!-- 一覧 -->
    @foreach($records as $record)
        <div class="card mb-3 p-2" data-record-id="{{ $record->id }}">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <strong>{{ $record->tate_no }}立目</strong>
                    <button class="delete-record ms-2" data-id="{{ $record->id }}" title="立を削除">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
                <span id="result-{{ $record->id }}">{{ $record->shots->where('result', 'hit')->count() }}/4</span>
            </div>
            <div class="d-flex justify-content-around mt-2">
                @foreach($record->shots as $shot)
                    <button class="shot-btn {{ $shot->result == 'hit' ? 'shot-hit' : '' }} {{ $shot->result == 'miss' ? 'shot-miss' : '' }} {{ $shot->result == null ? 'shot-none' : '' }}"
                            data-id="{{ $shot->id }}"
                            data-record="{{ $record->id }}"
                            data-result="{{ $shot->result }}"
                            title="クリックで入力">
                        {{ $shot->result == 'hit' ? '○' : ($shot->result == 'miss' ? '×' : '＋') }}
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 日付変更でフォーム送信
    document.getElementById('date-picker').addEventListener('change', function() {
        this.form.submit();
    });

    // 以下は既存の射ボタン・削除ボタン処理
    const container = document.getElementById('records-container');
    let type = container.dataset.type;

    function updateBackground() {
        container.classList.remove('self-bg','official-bg');
        container.classList.add(type === 'self' ? 'self-bg' : 'official-bg');
    }

    function initShotButtons() {
        document.querySelectorAll('.shot-btn').forEach(btn => {
            btn.addEventListener('click', shotClickHandler);
        });
    }

    function initDeleteButtons() {
        document.querySelectorAll('.delete-record').forEach(btn => {
            btn.addEventListener('click', deleteClickHandler);
        });
    }

    function shotClickHandler() {
        let btn = this;
        let current = btn.dataset.result;
        let next = current==='hit'?'miss':current==='miss'?null:'hit';

        btn.dataset.result = next;
        btn.innerText = next==='hit'?'○':next==='miss'?'×':'＋';
        btn.classList.remove('shot-hit','shot-miss','shot-none');
        btn.classList.add(next==='hit'?'shot-hit':next==='miss'?'shot-miss':'shot-none');

        let recordId = btn.dataset.record;
        let parent = document.querySelectorAll(`[data-record='${recordId}']`);
        let hits = 0;
        parent.forEach(b => { if(b.dataset.result==='hit') hits++; });
        document.getElementById(`result-${recordId}`).innerText = hits+'/4';

        fetch(`/shots/${btn.dataset.id}`, {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
            },
            body:JSON.stringify({result:next})
        }).catch(err=>console.error(err));
    }

    function deleteClickHandler() {
        let btn = this;
        if(!confirm('この立を削除しますか？')) return;
        let recordId = btn.dataset.id;

        fetch(`/records/${recordId}`,{
            method:'DELETE',
            headers:{
                'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,
                'Accept':'application/json'
            }
        })
        .then(res=>res.json())
        .then(data=>{
            if(data.success){
                btn.closest('.card').remove();
                document.querySelectorAll('.card').forEach((card,index)=>{
                    card.querySelector('strong').innerText = (index+1)+'立目';
                    let recordIdElem = card.querySelector('.delete-record');
                    if(recordIdElem) recordIdElem.dataset.id = card.dataset.recordId;
                });
            } else alert('削除に失敗しました');
        })
        .catch(err=>console.error(err));
    }

    updateBackground();
    initShotButtons();
    initDeleteButtons();
});
</script>
@endsection