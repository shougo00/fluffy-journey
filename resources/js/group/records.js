function reloadAndPrint() {
    const url = new URL(window.location.href);
    url.searchParams.set('print', '1');
    window.location.href = url.toString();
}

window.addEventListener('load', () => {
    const url = new URL(window.location.href);

    if (url.searchParams.get('print') === '1') {
        url.searchParams.delete('print');
        history.replaceState(null, '', url.toString());

        setTimeout(() => {
            window.print();
        }, 500);
    }
});
    
function updateShot(el){

    const id = el.dataset.id;
    if(!id){
        alert('先に立を追加してください');
        return;
    }

    const userId = el.dataset.user;
    const current = el.dataset.result;

    const next =
        current==='hit' ? 'miss' :
        current==='miss' ? '' :
        'hit';

    el.dataset.result = next;

    el.innerHTML =
        next==='hit'
        ? '<i class="fa-regular fa-circle"></i>'
        : next==='miss'
        ? '<i class="fas fa-xmark"></i>'
        : '＋';

    el.classList.remove('shot-hit','shot-miss','shot-none');

    if(next==='hit') el.classList.add('shot-hit');
    else if(next==='miss') el.classList.add('shot-miss');
    else el.classList.add('shot-none');

    const scoreEl = document.querySelector(`.score[data-user-id="${userId}"]`);

    if(scoreEl){
        let count = parseInt(scoreEl.innerText) || 0;

        if(current !== 'hit' && next === 'hit') count++;
        if(current === 'hit' && next !== 'hit') count--;

        if(count < 0) count = 0;

        scoreEl.innerText = count + '中';
    }

    fetch(`/group/shot/${id}`,{
        method:'POST',
        headers:{
            'Content-Type':'application/json',
            'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ result: next })
    });
}
function scrollRight() {
    const el = document.querySelector('.score-scroll');
    if (el) el.scrollLeft = el.scrollWidth;
}

window.addEventListener('load', () => {
    setTimeout(scrollRight, 50);
});

function toggleCalendar(event) {
    event.stopPropagation();

    const box = document.getElementById('calendarBox');

    if (!box) return;

    box.style.display = box.style.display === 'block' ? 'none' : 'block';
}
window.updateShot = updateShot;
window.toggleCalendar = toggleCalendar;
window.reloadAndPrint = reloadAndPrint;
window.scrollRight = scrollRight;