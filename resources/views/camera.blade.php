@extends('layouts.user')

@section('content')

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
.video-container { position: relative; max-width: 600px; margin: auto; }
video, canvas { width: 100%; border-radius: 10px; }
canvas { position:absolute; top:0; left:0; }

#phase {
    position:absolute;
    top:10px;
    right:10px;
    font-size:18px;
    color:yellow;
    background:rgba(0,0,0,0.6);
    padding:10px;
    border-radius:10px;
}

#metrics {
    position:absolute;
    top:10px;
    left:10px;
    color:white;
    background:rgba(0,0,0,0.6);
    padding:10px;
    border-radius:10px;
    font-size:12px;
}

#checkpoints {
    margin-top:10px;
    padding:10px;
    background:#111;
    color:white;
    border-radius:10px;
    font-size:14px;
    text-align:center;
}

button {
    display:block;
    width:100%;
    max-width:600px;
    margin:10px auto;
    padding:12px;
    font-size:16px;
}
</style>

<div class="video-container">
    <video id="camera" autoplay playsinline></video>
    <canvas id="canvas"></canvas>
    <div id="phase">胴作り</div>
    <div id="metrics"></div>
</div>

<div id="checkpoints"></div>

<button onclick="startCamera()">カメラ起動</button>
<button onclick="resetPhase()">最初から</button>

<script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose"></script>

<script>
let video, canvas, ctx, pose;
let latestLandmarks = null;
let prevLandmarks = null;

let isProcessing = false;
let lastPoseTime = 0;

const CHECKPOINTS = ["胴作り","打起こし","第三","引き分け","会","離れ"];
let currentStep = 0;
let currentPhase = CHECKPOINTS[0];

let stillStart = null;

// 立ち位置の向き
// true  = 左手が画面左へ伸びる想定
// false = 左手が画面右へ伸びる想定
let leftTargetDirection = true;

document.addEventListener('DOMContentLoaded', () => {

    video = document.getElementById('camera');
    canvas = document.getElementById('canvas');
    ctx = canvas.getContext('2d');

    pose = new Pose({
        locateFile: f => `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${f}`
    });

    pose.setOptions({
        modelComplexity: 0,
        smoothLandmarks: true,
        minDetectionConfidence: 0.5,
        minTrackingConfidence: 0.5
    });

    pose.onResults(res => {
        latestLandmarks = res.poseLandmarks;
    });

    renderCheckpoints();
});

// ===== カメラ =====
async function startCamera() {
    try {
        let stream;

        try {
            // スマホ用：背面カメラ
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: "environment" } },
                audio: false
            });
        } catch (e) {
            // PC用：通常カメラ
            stream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });
        }

        video.srcObject = stream;

        await video.play();

        requestAnimationFrame(loop);

    } catch (error) {
        console.error(error);
        alert('カメラを起動できません。HTTPS接続、カメラ許可、他アプリで使用中でないか確認してください。');
    }
}

// ===== メイン =====
function loop(time){

    if (!time) time = performance.now();

    if (!video.videoWidth || !video.videoHeight) {
        requestAnimationFrame(loop);
        return;
    }

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(video,0,0, canvas.width, canvas.height);

    if (time - lastPoseTime > 100 && !isProcessing) {
        isProcessing = true;
        lastPoseTime = time;

        pose.send({ image: video }).finally(()=>{
            isProcessing = false;
        });
    }

    if (latestLandmarks) {

        const lm = latestLandmarks;

        // 必要な点が取れてない時はスキップ
        if (!lm[0] || !lm[11] || !lm[12] || !lm[13] || !lm[14] || !lm[15] || !lm[16] || !lm[23] || !lm[24]) {
            requestAnimationFrame(loop);
            return;
        }

        drawSkeleton(lm);

        // ===== 角度 =====
        const leftElbowAngle  = safeAngle(lm[11], lm[13], lm[15]); // 左ひじ
        const rightElbowAngle = safeAngle(lm[12], lm[14], lm[16]); // 右ひじ

        // 肩・手・顔
        const noseY = lm[0].y;

        const leftShoulderX = lm[11].x;
        const leftShoulderY = lm[11].y;
        const rightShoulderY = lm[12].y;
        const shoulderY = (lm[11].y + lm[12].y) / 2;

        const leftHandX = lm[15].x;
        const leftHandY = lm[15].y;
        const rightHandX = lm[16].x;
        const rightHandY = lm[16].y;

        const rightElbowX = lm[14].x;
        const rightElbowY = lm[14].y;

        const handWidth = Math.abs(leftHandX - rightHandX);

        let footWidth = 0;
        if (lm[31] && lm[32]) {
            footWidth = Math.abs(lm[31].x - lm[32].x);
        }

        // ===== 前フレーム差分 =====
        let move = 0;
        let rightHandDownMove = 0;
        let rightElbowSideMove = 0;
        let prevRightHandY = rightHandY;
        let prevRightElbowX = rightElbowX;

        if (prevLandmarks) {
            move =
                Math.abs(lm[15].x - prevLandmarks[15].x) +
                Math.abs(lm[15].y - prevLandmarks[15].y) +
                Math.abs(lm[16].x - prevLandmarks[16].x) +
                Math.abs(lm[16].y - prevLandmarks[16].y);

            prevRightHandY = prevLandmarks[16].y;
            prevRightElbowX = prevLandmarks[14].x;

            rightHandDownMove = rightHandY - prevRightHandY;
            rightElbowSideMove = Math.abs(rightElbowX - prevRightElbowX);
        }

        prevLandmarks = JSON.parse(JSON.stringify(lm));

        // ===== 静止時間 =====
        let stillTime = 0;
        if (move < 0.004) {
            if (!stillStart) stillStart = Date.now();
            stillTime = Date.now() - stillStart;
        } else {
            stillStart = null;
        }

        const metrics = {
            noseY,

            leftShoulderX,
            leftShoulderY,
            rightShoulderY,
            shoulderY,

            leftHandX,
            leftHandY,
            rightHandX,
            rightHandY,

            rightElbowX,
            rightElbowY,

            leftElbowAngle,
            rightElbowAngle,

            handWidth,
            footWidth,
            move,
            stillTime,

            prevRightHandY,
            rightHandDownMove,
            rightElbowSideMove
        };

        updatePhase(metrics);

        document.getElementById('phase').innerText = currentPhase;

        document.getElementById('metrics').innerHTML = `
            左肘:${leftElbowAngle.toFixed(1)}°<br>
            右肘:${rightElbowAngle.toFixed(1)}°<br>
            手幅:${handWidth.toFixed(3)}<br>
            右手下げ:${rightHandDownMove.toFixed(4)}<br>
            動き:${move.toFixed(4)}<br>
            静止:${stillTime}ms
        `;
    }

    requestAnimationFrame(loop);
}

// ===== 骨格描画 =====
function drawSkeleton(lm) {
    const lines = [
        [11,13],[13,15],
        [12,14],[14,16],
        [11,12],
        [11,23],[12,24],[23,24],
        [23,25],[25,27],[27,31],
        [24,26],[26,28],[28,32]
    ];

    ctx.strokeStyle = "lime";
    ctx.lineWidth = 2;

    lines.forEach(([a,b]) => {
        const p1 = lm[a];
        const p2 = lm[b];
        if (!p1 || !p2) return;

        ctx.beginPath();
        ctx.moveTo(p1.x * canvas.width, p1.y * canvas.height);
        ctx.lineTo(p2.x * canvas.width, p2.y * canvas.height);
        ctx.stroke();
    });

    // 点
    ctx.fillStyle = "red";
    [0,11,12,13,14,15,16,23,24,31,32].forEach(i => {
        if (!lm[i]) return;
        ctx.beginPath();
        ctx.arc(lm[i].x * canvas.width, lm[i].y * canvas.height, 4, 0, Math.PI * 2);
        ctx.fill();
    });
}

// ===== 判定ロジック =====
function updatePhase(m) {

    // 胴作り → 打起こし
    // 両手が肩より上に上がったら
    if (currentPhase === "胴作り") {
        if (
            m.leftHandY < m.shoulderY - 0.03 &&
            m.rightHandY < m.shoulderY - 0.03
        ) {
            next();
        }
    }

    // 打起こし → 第三
    // 画像基準：
    // 左腕が伸びる
    // 左手が肩より外
    // 左手が肩より少し上
    // 右手が頭付近
    // 手幅が開いている
    else if (currentPhase === "打起こし") {

        let leftHandOutside = false;

        if (leftTargetDirection) {
            leftHandOutside = m.leftHandX < m.leftShoulderX - 0.10;
        } else {
            leftHandOutside = m.leftHandX > m.leftShoulderX + 0.10;
        }

        if (
            m.leftElbowAngle > 145 &&
            leftHandOutside &&
            m.leftHandY < m.leftShoulderY - 0.02 &&
            m.rightHandY < m.noseY + 0.12 &&
            m.handWidth > 0.22
        ) {
            next();
            save("第三", m);
        }
    }

    // 第三 → 引き分け
    // 右ひじ角度が狭くなり、右手が下がり、手幅がさらに広がる
    else if (currentPhase === "第三") {
        if (
            m.rightElbowAngle < 125 &&
            m.rightHandDownMove > 0.002 &&
            m.handWidth > 0.30
        ) {
            next();
            save("引き分け", m);
        }
    }

    // 引き分け → 会
    // 手幅が広く、動きが止まる
    else if (currentPhase === "引き分け") {
        if (
            m.handWidth > 0.38 &&
            m.stillTime > 700
        ) {
            next();
            save("会", m);
        }
    }

    // 会 → 離れ
    // 右ひじが横に抜ける
    else if (currentPhase === "会") {
        if (
            m.rightElbowSideMove > 0.015 &&
            m.move > 0.018
        ) {
            next();
        }
    }
}

// ===== 次へ =====
function next() {
    if (currentStep < CHECKPOINTS.length - 1) {
        currentStep++;
        currentPhase = CHECKPOINTS[currentStep];
        renderCheckpoints();
    }
}

// ===== チェックポイント表示 =====
function renderCheckpoints() {
    const html = CHECKPOINTS.map((p, i) => {
        if (i < currentStep) return `${p} ✓`;
        if (i === currentStep) return `<b style="color:yellow;">${p}</b>`;
        return p;
    }).join(' → ');

    document.getElementById('checkpoints').innerHTML = html;
}

// ===== 保存 =====
function save(phase, m) {
    fetch('/kyudo-pose-records', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            phase: phase,

            left_elbow_angle: m.leftElbowAngle,
            right_elbow_angle: m.rightElbowAngle,

            hand_width: m.handWidth,
            foot_width: m.footWidth,

            move: m.move,
            still_time: m.stillTime,

            left_hand_x: m.leftHandX,
            left_hand_y: m.leftHandY,
            right_hand_x: m.rightHandX,
            right_hand_y: m.rightHandY
        })
    }).catch(() => {});
}

// ===== 角度 =====
function safeAngle(a,b,c){
    if (!a || !b || !c) return 0;

    const ab = {x:a.x-b.x, y:a.y-b.y};
    const cb = {x:c.x-b.x, y:c.y-b.y};

    const dot = ab.x*cb.x + ab.y*cb.y;
    const mag =
        Math.sqrt(ab.x**2 + ab.y**2) *
        Math.sqrt(cb.x**2 + cb.y**2);

    if (!mag) return 0;

    let cos = dot / mag;
    cos = Math.max(-1, Math.min(1, cos));

    return Math.acos(cos) * 180 / Math.PI;
}
</script>

@endsection