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
        smoothLandmarks: true
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
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: "environment" } }
            });
        } catch {
            stream = await navigator.mediaDevices.getUserMedia({
                video: true
            });
        }

        video.srcObject = stream;
        await video.play();

        requestAnimationFrame(loop);

    } catch (e) {
        alert("カメラ起動失敗");
        console.error(e);
    }
}

// ===== メイン =====
function loop(time){

    if (!time) time = performance.now();

    if (!video.videoWidth) {
        requestAnimationFrame(loop);
        return;
    }

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    ctx.drawImage(video,0,0);

    if (time - lastPoseTime > 100 && !isProcessing) {
        isProcessing = true;
        lastPoseTime = time;

        pose.send({ image: video }).finally(()=>{
            isProcessing = false;
        });
    }

    if (latestLandmarks) {

        const lm = latestLandmarks;

        drawSkeleton(lm);

        // ===== 角度 =====
        const leftElbowAngle  = safeAngle(lm[11], lm[13], lm[15]);
        const rightElbowAngle = safeAngle(lm[12], lm[14], lm[16]);

        const noseY = lm[0].y;
        const leftShoulderX = lm[11].x;
        const leftShoulderY = lm[11].y;
        const shoulderY = (lm[11].y + lm[12].y) / 2;

        const leftHandX = lm[15].x;
        const leftHandY = lm[15].y;
        const rightHandY = lm[16].y;

        const handWidth = Math.abs(lm[15].x - lm[16].x);

        let move = 0;
        if (prevLandmarks) {
            move =
                Math.abs(lm[15].x - prevLandmarks[15].x) +
                Math.abs(lm[16].x - prevLandmarks[16].x);
        }

        prevLandmarks = JSON.parse(JSON.stringify(lm));

        // ===== 外判定 =====
        let outsideCheck = leftTargetDirection
            ? leftHandX < leftShoulderX - 0.06
            : leftHandX > leftShoulderX + 0.06;

        // ===== 判定 =====
        updatePhase({
            leftElbowAngle,
            rightElbowAngle,
            leftHandX,
            leftHandY,
            rightHandY,
            leftShoulderX,
            leftShoulderY,
            shoulderY,
            noseY,
            handWidth,
            move
        });

        // ===== 表示 =====
        document.getElementById('phase').innerText = currentPhase;

        document.getElementById('metrics').innerHTML = `
現在:${currentPhase}<br>
左肘:${leftElbowAngle.toFixed(1)}°<br>
右肘:${rightElbowAngle.toFixed(1)}°<br>
手幅:${handWidth.toFixed(3)}<br>
外:${outsideCheck ? 'OK':'NG'}<br>
第三条件:<br>
角度:${leftElbowAngle > 130 ? 'OK':'NG'} /
外:${outsideCheck ? 'OK':'NG'} /
幅:${handWidth > 0.15 ? 'OK':'NG'}
`;
    }

    requestAnimationFrame(loop);
}

// ===== 骨格 =====
function drawSkeleton(lm) {

    const lines = [
        [11,13],[13,15],
        [12,14],[14,16],
        [11,12]
    ];

    ctx.strokeStyle = "lime";
    ctx.lineWidth = 2;

    lines.forEach(([a,b])=>{
        ctx.beginPath();
        ctx.moveTo(lm[a].x*canvas.width,lm[a].y*canvas.height);
        ctx.lineTo(lm[b].x*canvas.width,lm[b].y*canvas.height);
        ctx.stroke();
    });

    // ===== 角度表示 =====
    const leftElbowAngle = safeAngle(lm[11], lm[13], lm[15]);
    const rightElbowAngle = safeAngle(lm[12], lm[14], lm[16]);

    ctx.fillStyle = "yellow";
    ctx.font = "16px Arial";

    ctx.fillText(
        `L:${leftElbowAngle.toFixed(0)}°`,
        lm[13].x*canvas.width+5,
        lm[13].y*canvas.height-5
    );

    ctx.fillText(
        `R:${rightElbowAngle.toFixed(0)}°`,
        lm[14].x*canvas.width+5,
        lm[14].y*canvas.height-5
    );
}

// ===== 判定 =====
function updatePhase(m) {

    if (currentPhase === "胴作り") {
        if (m.leftHandY < m.shoulderY) next();
    }

    else if (currentPhase === "打起こし") {

        let outside = leftTargetDirection
            ? m.leftHandX < m.leftShoulderX - 0.06
            : m.leftHandX > m.leftShoulderX + 0.06;

        if (
            m.leftElbowAngle > 130 &&
            outside &&
            m.handWidth > 0.15
        ) {
            next();
        }
    }

    else if (currentPhase === "第三") {
        if (m.rightElbowAngle < 120 && m.move > 0.01) next();
    }

    else if (currentPhase === "引き分け") {
        if (m.handWidth > 0.4) next();
    }

    else if (currentPhase === "会") {
        if (m.move > 0.02) next();
    }
}

// ===== 次 =====
function next() {
    currentStep++;
    currentPhase = CHECKPOINTS[currentStep] || "終了";
    renderCheckpoints();
}

// ===== 表示 =====
function renderCheckpoints() {
    document.getElementById('checkpoints').innerText =
        CHECKPOINTS.map((p,i)=>{
            if(i<currentStep) return "✓"+p;
            if(i===currentStep) return "▶"+p;
            return p;
        }).join(" → ");
}

// ===== 角度 =====
function safeAngle(a,b,c){
    const ab={x:a.x-b.x,y:a.y-b.y};
    const cb={x:c.x-b.x,y:c.y-b.y};
    const dot=ab.x*cb.x+ab.y*cb.y;
    const mag=Math.sqrt(ab.x**2+ab.y**2)*Math.sqrt(cb.x**2+cb.y**2);
    return mag ? Math.acos(dot/mag)*180/Math.PI : 0;
}
</script>

@endsection