@extends('layouts.user')

@section('content')

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
.video-container { position: relative; max-width: 600px; margin: auto; }
video, canvas { width: 100%; border-radius: 10px; }
canvas { position:absolute; top:0; left:0; }

#phase {
    position:absolute;
    top:10px;
    right:10px;
    font-size:20px;
    color:yellow;
    background:rgba(0,0,0,0.6);
    padding:10px;
    border-radius:10px;
}
</style>

<div class="video-container">
    <video id="camera" autoplay playsinline></video>
    <canvas id="canvas"></canvas>
    <div id="phase">待機</div>
</div>

<button onclick="startCamera()">カメラ起動</button>

<script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose"></script>

<script>
let video, canvas, ctx, pose;
let latestLandmarks = null;
let prevLandmarks = null;

let phase = "待機";
let isProcessing = false;
let lastPoseTime = 0;

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
});

// ===== カメラ =====
async function startCamera() {
    const stream = await navigator.mediaDevices.getUserMedia({ video:true });
    video.srcObject = stream;
    await new Promise(r => video.onloadedmetadata = r);
    loop();
}

// ===== メインループ =====
function loop(time){

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    ctx.drawImage(video,0,0);

    // AIは軽く
    if (time - lastPoseTime > 100 && !isProcessing) {
        isProcessing = true;
        lastPoseTime = time;

        pose.send({ image: video }).finally(()=>{
            isProcessing = false;
        });
    }

    if (latestLandmarks) {

        const lm = latestLandmarks;

        // ===== 骨格 =====
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

        // ===== 動き量 =====
        let move = 0;
        if (prevLandmarks) {
            move =
                Math.abs(lm[15].x - prevLandmarks[15].x) +
                Math.abs(lm[16].x - prevLandmarks[16].x);
        }
        prevLandmarks = JSON.parse(JSON.stringify(lm));

        // ===== 判定 =====
        const leftHand = lm[15];
        const rightHand = lm[16];
        const leftShoulder = lm[11];
        const rightShoulder = lm[12];

        const handY = (leftHand.y + rightHand.y) / 2;
        const shoulderY = (leftShoulder.y + rightShoulder.y) / 2;
        const hipY = (lm[23].y + lm[24].y) / 2;

        const handDist = Math.abs(leftHand.x - rightHand.x);

        const leftArmAngle = calcAngle(lm[11], lm[13], lm[15]);

        // ===== 八節判定（改良版） =====

        if (handY > hipY + 0.05) {
            phase = "待機";
        }

        else if (handY > hipY - 0.05 && handY < hipY + 0.05) {
            phase = "胴作り";
        }

        else if (handY < shoulderY) {
            phase = "打起こし";
        }

        else if (leftArmAngle > 155 && handDist < 0.3) {
            phase = "第三";
        }

        else if (handDist > 0.3 && move > 0.01) {
            phase = "引き分け";
        }

        else if (handDist > 0.4 && move < 0.005) {
            phase = "会";
        }

        document.getElementById('phase').innerText = phase;

        // ===== フェーズ強調 =====
        if (phase === "会") {
            ctx.strokeStyle = "red";
        }
    }

    requestAnimationFrame(loop);
}

// ===== 角度 =====
function calcAngle(a,b,c){
    const ab={x:a.x-b.x,y:a.y-b.y};
    const cb={x:c.x-b.x,y:c.y-b.y};
    const dot=ab.x*cb.x+ab.y*cb.y;
    const mag=Math.sqrt(ab.x**2+ab.y**2)*Math.sqrt(cb.x**2+cb.y**2);
    return Math.acos(dot/mag)*180/Math.PI;
}
</script>

@endsection
