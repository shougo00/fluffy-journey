@extends('layouts.user')

@section('content')
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ===== 全体 ===== */
.my-container {
    width: 100%;           /* 親に対してフル幅 */
    max-width: 700px;      /* 最大幅 700px */
    margin: 0 auto;        /* 中央寄せ */
    transition: background 0.3s;
}
/* ===== ボタン共通 ===== */
.shot-btn {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    font-size: 26px;
    border: 2px solid #ccc; /* ← 常にグレーに固定 */
    background: white;
    transition: 0.2s;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent; 
    -webkit-text-size-adjust: 100%; 
    touch-action: manipulation; 
    transform-origin: center; /* アクティブ時のscaleも中心基準に */

}
.shot-btn:active {
    transform: scale(1.05); /* 小さめに */
}


/* ○ */
.shot-hit {
    color: red;
    font-weight: bold;
    font-size: 34px; /* 少し大きく */
    transform: scale(1.1);
}

/* ×（さらに大きく） */
.shot-miss {
    color: blue;
    font-weight: bold;
    font-size: 38px; /* ←ここ大事！！ */
    transform: scale(1.15);
}


/* 空（押せる感じ） */
.shot-none {
    color: #bbb;
    border: 2px dashed #ccc;
}

/* ホバーで押せる感 */
.shot-none:hover {
    border-color: #666;
    background: #f8f9fa;
}



/* スマホ最適化 */
@media (max-width: 576px) {
    .shot-btn {
        width: 55px;
        height: 55px;
        font-size: 22px;
    }
}


/* 自主練のとき薄赤に */
.container.self-bg {
    background-color: #e5f0ff; /* 薄い赤 */
}

/* 正規練のとき薄青に */
.container.official-bg {
    background-color: #ffe5e5;/* 薄い青 */
}

/* ボタンサイズ調整 */
.practice-type-buttons .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
}
/* 正規練ボタン（赤系） */
.btn-official {
    background-color: #ff4d4d; /* 赤 */
    color: white;
    border-color: #ff4d4d;
}
.btn-outline-official {
    background-color: white;
    color: #ff4d4d;
    border-color: #ff4d4d;
}

/* 自主練ボタン（水色系） */
.btn-self {
    background-color: #4da6ff; /* 水色 */
    color: white;
    border-color: #4da6ff;
}
.btn-outline-self {
    background-color: white;
    color: #4da6ff;
    border-color: #4da6ff;
}

/* ホバー効果 */
.btn-official:hover, .btn-outline-official:hover {
    background-color: #e60000;
    color: white;
}
.btn-self:hover, .btn-outline-self:hover {
    background-color: #0066cc;
    color: white;
}

/* 自主練：薄水色背景 */
.container.self-bg { background-color: #e6f2ff; }
/* 正規練：薄赤背景 */
.container.official-bg { background-color: #ffe6e6; }

/* ボタンの枠線・周りの塗りつぶしをなくす */
.delete-record {
    padding: 2px 6px;
    font-size: 16px;
    color: #7b7b7b;        /* 普通の灰色 */
    border: none;
    background-color: transparent;
    cursor: pointer;
    transition: color 0.2s, transform 0.1s;

    /* これで押した時の赤黒フラッシュを完全に消す */
    outline: none;
    box-shadow: none;
}

.delete-record:hover {
    color: #434242;         /* ホバーで少し濃く */
}

.delete-record:focus {
    outline: none;
    box-shadow: none;
}

.delete-record:active {
    color: #434242;         /* 押した時も色は濃い灰色 */
    background-color: transparent; /* 背景は透明 */
    transform: scale(0.95); /* 軽く縮小で押した感 */
}
</style>

<div class="container py-3 {{ $type=='self' ? 'self-bg' : 'official-bg' }}">

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

        <a href="{{ route('home', ['date' => $prevDate]) }}" class="btn btn-outline-secondary">
            ＜
        </a>

        <form id="date-form" method="GET" action="{{ route('home') }}">
            <input type="date" name="date" value="{{ $date }}" class="form-control text-center" id="date-picker">
        </form>

        <a href="{{ route('home', ['date' => $nextDate]) }}" class="btn btn-outline-secondary">
            ＞
        </a>
    </div>

    <!-- 立追加 -->
    <form method="POST" action="{{ route('records.store') }}" class="mb-3">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="practice_type" value="{{ $type }}"> <!-- 今見ている練習タイプ -->
        <button class="btn btn-primary w-100">＋ 立を追加</button>
    </form>

    <!-- 一覧 -->
    @foreach($records as $record)
        <div class="card mb-3 p-2" data-record-id="{{ $record->id }}">

            <!-- 上部 -->
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <strong>{{ $record->tate_no }}立目</strong>
                    <button class="delete-record ms-2" data-id="{{ $record->id }}" title="立を削除">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>

                <span id="result-{{ $record->id }}">
                    {{ $record->shots->where('result', 'hit')->count() }}/4
                </span>
            </div>

            <!-- 射 -->
            <div class="d-flex justify-content-around mt-2">
                @foreach($record->shots as $shot)
                    <button 
                        class="shot-btn 
                        {{ $shot->result == 'hit' ? 'shot-hit' : '' }}
                        {{ $shot->result == 'miss' ? 'shot-miss' : '' }}
                        {{ $shot->result == null ? 'shot-none' : '' }}"
                        data-id="{{ $shot->id }}"
                        data-record="{{ $record->id }}"
                        data-result="{{ $shot->result }}"
                        title="クリックで入力"
                    >
                        {{ $shot->result == 'hit' ? '○' : ($shot->result == 'miss' ? '×' : '＋') }}
                    </button>
                @endforeach
            </div>

        </div>
    @endforeach

</div>

<style>
.shot-btn {
    width: 65px;
    height: 65px;
    border-radius: 50%;
    font-size: 26px;
    border: 2px solid #ccc;
    background: white;
    transition: all 0.1s; /* 短くして高速切替 */
    cursor: pointer;
}
.shot-hit { color: red; font-weight: bold; font-size: 30px; transform: scale(1.1); }
.shot-miss { color: blue; font-weight: 900; font-size: 42px; display: flex; align-items: center; justify-content: center; }
.shot-none { color: #bbb; border: 2px dashed #ccc; }
.shot-none:hover { border-color: #666; background: #f8f9fa; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const datePicker = document.getElementById('date-picker');

    datePicker.addEventListener('change', () => {
        const date = datePicker.value;

        fetch(`/home?date=${date}&type={{ $type }}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            // 返ってきたHTMLの一部（射撃リスト）だけ差し替え
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newRecords = doc.querySelector('.container').innerHTML;
            document.querySelector('.container').innerHTML = newRecords;

            // 置き換え後にボタンのイベント再登録
            initShotButtons();
            initDeleteButtons();
        });
    });

    // 元々あった関数を切り出す
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

    // 既存のクリック処理を関数化
    function shotClickHandler() {
        let btn = this;
        let current = btn.dataset.result;
        let next = current === 'hit' ? 'miss' : current === 'miss' ? null : 'hit';

        btn.dataset.result = next;
        btn.innerText = next === 'hit' ? '○' : (next === 'miss' ? '×' : '＋');
        btn.classList.remove('shot-hit','shot-miss','shot-none');
        btn.classList.add(next === 'hit' ? 'shot-hit' : (next === 'miss' ? 'shot-miss' : 'shot-none'));

        let recordId = btn.dataset.record;
        let parent = document.querySelectorAll(`[data-record='${recordId}']`);
        let hits = 0;
        parent.forEach(b => { if(b.dataset.result==='hit') hits++; });
        document.getElementById(`result-${recordId}`).innerText = hits+'/4';

        fetch(`/shots/${btn.dataset.id}`, {
            method: 'POST',
            headers: {
                'Content-Type':'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ result: next })
        }).catch(err=>console.error(err));
    }

    function deleteClickHandler() {
        let btn = this;
        if(!confirm('この立を削除しますか？')) return;

        let recordId = btn.dataset.id;

        fetch(`/records/${recordId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept':'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                btn.closest('.card').remove();
                document.querySelectorAll('.card').forEach((card, index) => {
                    card.querySelector('strong').innerText = (index + 1) + '立目';
                    let recordIdElem = card.querySelector('.delete-record');
                    if(recordIdElem) recordIdElem.dataset.id = card.dataset.recordId;
                });
            } else {
                alert('削除に失敗しました');
            }
        })
        .catch(err => console.error(err));
    }

    // 初回ロード時にも登録
    initShotButtons();
    initDeleteButtons();
});
</script>

@endsection