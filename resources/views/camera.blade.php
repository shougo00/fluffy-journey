@extends('layouts.user')

@section('content')

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
.video-container {
    position: relative;
    max-width: 600px;
    margin: auto;
    background: #000;
    border-radius: 10px;
    overflow: hidden;
}

video, canvas {
    width: 100%;
    border-radius: 10px;
}

canvas {
    position: absolute;
    top: 0;
    left: 0;
}

#phase {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 18px;
    color: yellow;
    background: rgba(0,0,0,0.6);
    padding: 10px;
    border-radius: 10px;
    z-index: 3;
}

#metrics {
    position: absolute;
    top: 10px;
    left: 10px;
    color: white;
    background: rgba(0,0,0,0.6);
    padding: 10px;
    border-radius: 10px;
    font-size: 12px;
    z-index: 3;
}

#checkpoints {
    max-width: 600px;
    margin: 10px auto;
    padding: 10px;
    background: #111;
    color: white;
    border-radius: 10px;
    font-size: 14px;
    text-align: center;
}

button {
    display: block;
    width: 100%;
    max-width: 600px;
    margin: 10px auto;
    padding: 12px;
    font-size: 16px;
    border-radius: 8px;
    border: none;
}

.btn-main {
    background: #0d6efd;
    color: white;
}

.btn-sub {
    background: #6c757d;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

#cameraInfo {
    max-width: 600px;
    margin: 8px auto;
    font-size: 13px;
    color: #555;
    text-align: center;
}
</style>

<div class="video-container">
    <video id="camera" autoplay playsinline muted></video>
    <canvas id="canvas"></canvas>

    <div id="phase">胴作り</div>
    <div id="metrics"></div>
</div>

<div id="checkpoints"></div>
<div id="cameraInfo">カメラ未起動</div>

<button class="btn-main" onclick="startCamera()">カメラ起動</button>
<button class="btn-sub" onclick="switchCamera()">カメラ切替</button>
<button class="btn-sub" onclick="toggleDirection()">向き切替</button>
<button class="btn-danger" onclick="resetPhase()">最初から</button>

<script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose"></script>

<script>
let video, canvas, ctx, pose;
let latestLandmarks = null;
let prevLandmarks = null;
let smoothLandmarksData = null;

let isProcessing = false;
let lastPoseTime = 0;

let currentStream = null;
let currentFacingMode = "environment";

const CHECKPOINTS = ["胴作り", "打起こし", "第三", "会", "離れ"];
let currentStep = 0;
let currentPhase = CHECKPOINTS[0];

let phaseHitCount = 0;
let lastPhaseChangeTime = 0;
let stillStart = null;

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

// ===== カメラ起動 PC・スマホ両対応 =====
async function startCamera() {
    try {
        stopCamera();

        let stream = null;

        try {
            // スマホ優先：背面カメラ
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: currentFacingMode },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });
        } catch (e) {
            // PC用：通常カメラ
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });
        }

        currentStream = stream;
        video.srcObject = stream;

        await video.play();

        document.getElementById('cameraInfo').innerText =
            currentFacingMode === "environment"
                ? "カメラ起動中：背面カメラ優先 / PCでは通常カメラ"
                : "カメラ起動中：前面カメラ優先 / PCでは通常カメラ";

        requestAnimationFrame(loop);

    } catch (error) {
        console.error(error);
        alert(
            'カメラを起動できません。\n\n' +
            '確認してください：\n' +
            '・HTTPSで開いているか\n' +
            '・ブラウザでカメラ許可しているか\n' +
            '・他アプリがカメラを使っていないか\n' +
            '・PCにカメラが接続されているか'
        );
    }
}

// ===== カメラ停止 =====
function stopCamera() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
        currentStream = null;
    }
}

// ===== カメラ切替 =====
async function switchCamera() {
    currentFacingMode = currentFacingMode === "environment" ? "user" : "environment";
    await startCamera();
}

// ===== メインループ =====
function loop(time) {
    if (!video || !video.videoWidth || !video.videoHeight) {
        requestAnimationFrame(loop);
        return;
    }

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    if (time - lastPoseTime > 100 && !isProcessing) {
        isProcessing = true;
        lastPoseTime = time;

        pose.send({ image: video }).finally(() => {
            isProcessing = false;
        });
    }

    if (latestLandmarks) {
        const lm = smoothLandmarks(latestLandmarks);

        if (!hasRequiredPoints(lm)) {
            requestAnimationFrame(loop);
            return;
        }

drawSkeleton(lm);

        const m = calcMetrics(lm);
        drawAngles(lm, m);
        updatePhase(m);

        document.getElementById('phase').innerText = currentPhase;

        document.getElementById('metrics').innerHTML = `
            左肘:${m.leftElbowAngle.toFixed(1)}°<br>
            右肘:${m.rightElbowAngle.toFixed(1)}°<br>
            手幅:${m.handWidth.toFixed(3)}<br>
            足幅:${m.footWidth.toFixed(3)}<br>
            動き:${m.move.toFixed(4)}<br>
            静止:${m.stillTime}ms<br>
            判定:${phaseHitCount}/5
        `;
    }

    requestAnimationFrame(loop);
}

// ===== 角度表示 =====
function drawAngles(lm, m) {
    ctx.font = "16px Arial";
    ctx.lineWidth = 4;

    drawTextAtPoint(lm[13], `左肘 ${m.leftElbowAngle.toFixed(0)}°`, "yellow");
    drawTextAtPoint(lm[14], `右肘 ${m.rightElbowAngle.toFixed(0)}°`, "yellow");

    const leftArmAngle = lineAngle(lm[11], lm[15]);
    const rightArmAngle = lineAngle(lm[12], lm[16]);

    drawTextAtPoint(lm[15], `左腕 ${leftArmAngle.toFixed(0)}°`, "cyan");
    drawTextAtPoint(lm[16], `右腕 ${rightArmAngle.toFixed(0)}°`, "cyan");
}

function drawTextAtPoint(p, text, color) {
    if (!p) return;

    const x = p.x * canvas.width;
    const y = p.y * canvas.height;

    ctx.strokeStyle = "black";
    ctx.fillStyle = color;

    ctx.strokeText(text, x + 8, y - 8);
    ctx.fillText(text, x + 8, y - 8);
}

function lineAngle(a, b) {
    if (!a || !b) return 0;

    const dx = b.x - a.x;
    const dy = b.y - a.y;

    let deg = Math.atan2(dy, dx) * 180 / Math.PI;

    if (deg < 0) deg += 360;

    return deg;
}

// ===== 必要点チェック =====
function hasRequiredPoints(lm) {
    const required = [0,11,12,13,14,15,16,23,24];
    return required.every(i => lm[i]);
}

// ===== なめらか化 =====
function smoothLandmarks(lm) {
    if (!smoothLandmarksData) {
        smoothLandmarksData = JSON.parse(JSON.stringify(lm));
        return smoothLandmarksData;
    }

    for (let i = 0; i < lm.length; i++) {
        if (!lm[i] || !smoothLandmarksData[i]) continue;

        smoothLandmarksData[i].x = smoothLandmarksData[i].x * 0.7 + lm[i].x * 0.3;
        smoothLandmarksData[i].y = smoothLandmarksData[i].y * 0.7 + lm[i].y * 0.3;
        smoothLandmarksData[i].z = smoothLandmarksData[i].z * 0.7 + lm[i].z * 0.3;
        smoothLandmarksData[i].visibility = lm[i].visibility;
    }

    return smoothLandmarksData;
}

// ===== 数値計算 =====
function calcMetrics(lm) {
    const noseY = lm[0].y;

    const leftShoulderX = lm[11].x;
    const leftShoulderY = lm[11].y;
    const rightShoulderX = lm[12].x;
    const rightShoulderY = lm[12].y;
    const shoulderY = (leftShoulderY + rightShoulderY) / 2;

    const leftHandX = lm[15].x;
    const leftHandY = lm[15].y;
    const rightHandX = lm[16].x;
    const rightHandY = lm[16].y;

    const rightElbowX = lm[14].x;
    const rightElbowY = lm[14].y;

    const leftElbowAngle = safeAngle(lm[11], lm[13], lm[15]);
    const rightElbowAngle = safeAngle(lm[12], lm[14], lm[16]);

    const handWidth = Math.abs(leftHandX - rightHandX);

    let footWidth = 0;
    if (lm[31] && lm[32]) {
        footWidth = Math.abs(lm[31].x - lm[32].x);
    }

    let move = 0;
    let rightHandDownMove = 0;
    let rightElbowSideMove = 0;

    if (prevLandmarks) {
        move =
            Math.abs(lm[15].x - prevLandmarks[15].x) +
            Math.abs(lm[15].y - prevLandmarks[15].y) +
            Math.abs(lm[16].x - prevLandmarks[16].x) +
            Math.abs(lm[16].y - prevLandmarks[16].y);

        rightHandDownMove = rightHandY - prevLandmarks[16].y;
        rightElbowSideMove = Math.abs(rightElbowX - prevLandmarks[14].x);
    }

    prevLandmarks = JSON.parse(JSON.stringify(lm));

    let stillTime = 0;
    if (move < 0.004) {
        if (!stillStart) stillStart = Date.now();
        stillTime = Date.now() - stillStart;
    } else {
        stillStart = null;
    }

    return {
        noseY,

        leftShoulderX,
        leftShoulderY,
        rightShoulderX,
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

        rightHandDownMove,
        rightElbowSideMove
    };
}

// ===== 判定ロジック =====
function updatePhase(m) {
    if (currentPhase === "胴作り") {
    judge(
        m.leftHandY < m.noseY + 0.05 &&
        m.rightHandY < m.noseY + 0.05 &&
        m.handWidth > 0.12
    );
}

    else if (currentPhase === "打起こし") {
        judge(
            m.leftElbowAngle >= 170 &&
            m.leftElbowAngle <= 180
        );
    }

  else if (currentPhase === "第三") {
            judge(
                m.move < 0.004 &&
                m.stillTime > 700 &&
                m.handWidth > 0.25
            );
        }

        else if (currentPhase === "会") {
            judge(
                m.rightElbowAngle >= 90
            );
        }
}

// ===== 連続判定 =====
function judge(condition) {
    if (condition) {
        phaseHitCount++;
    } else {
        phaseHitCount = 0;
    }

    if (phaseHitCount >= 5) {
        phaseHitCount = 0;

        if (currentPhase === "第三") {
            save("第三", lastMetricsSafe());
        }

        if (currentPhase === "会") {
            save("会", lastMetricsSafe());
        }

        next();
    }
}

function lastMetricsSafe() {
    if (!latestLandmarks) return {};
    return calcMetrics(smoothLandmarks(latestLandmarks));
}

// ===== 次へ =====
function next() {
    const now = Date.now();

    if (now - lastPhaseChangeTime < 800) return;

    if (currentStep < CHECKPOINTS.length - 1) {
        currentStep++;
        currentPhase = CHECKPOINTS[currentStep];
        lastPhaseChangeTime = now;
        stillStart = null;
        renderCheckpoints();
    }
}

// ===== リセット =====
function resetPhase() {
    currentStep = 0;
    currentPhase = CHECKPOINTS[0];

    phaseHitCount = 0;
    lastPhaseChangeTime = 0;
    stillStart = null;
    prevLandmarks = null;
    smoothLandmarksData = null;

    renderCheckpoints();
    document.getElementById('phase').innerText = currentPhase;
}

// ===== 向き切替 =====
function toggleDirection() {
    leftTargetDirection = !leftTargetDirection;

    alert(
        leftTargetDirection
            ? '向き：左手が画面左へ伸びる設定'
            : '向き：左手が画面右へ伸びる設定'
    );
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
    if (!m || !m.leftElbowAngle) return;

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

// ===== 骨格描画 =====
function drawSkeleton(lm) {
    const lines = [
        [11,13], [13,15],
        [12,14], [14,16],
        [11,12],
        [11,23], [12,24], [23,24],
        [23,25], [25,27], [27,31],
        [24,26], [26,28], [28,32]
    ];

    ctx.strokeStyle = "lime";
    ctx.lineWidth = 2;

    lines.forEach(([a, b]) => {
        const p1 = lm[a];
        const p2 = lm[b];
        if (!p1 || !p2) return;

        ctx.beginPath();
        ctx.moveTo(p1.x * canvas.width, p1.y * canvas.height);
        ctx.lineTo(p2.x * canvas.width, p2.y * canvas.height);
        ctx.stroke();
    });

    ctx.fillStyle = "red";
    [0,11,12,13,14,15,16,23,24,25,26,27,28,31,32].forEach(i => {
        if (!lm[i]) return;

        ctx.beginPath();
        ctx.arc(lm[i].x * canvas.width, lm[i].y * canvas.height, 4, 0, Math.PI * 2);
        ctx.fill();
    });
}

// ===== 角度 =====
function safeAngle(a, b, c) {
    if (!a || !b || !c) return 0;

    const ab = { x: a.x - b.x, y: a.y - b.y };
    const cb = { x: c.x - b.x, y: c.y - b.y };

    const dot = ab.x * cb.x + ab.y * cb.y;

    const mag =
        Math.sqrt(ab.x ** 2 + ab.y ** 2) *
        Math.sqrt(cb.x ** 2 + cb.y ** 2);

    if (!mag) return 0;

    let cos = dot / mag;
    cos = Math.max(-1, Math.min(1, cos));

    return Math.acos(cos) * 180 / Math.PI;
}
</script>

@endsection