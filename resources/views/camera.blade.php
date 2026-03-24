@extends('layouts.user')

@section('content')

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
.camera-wrapper {
    max-width: 600px;
    margin: 0 auto;
    text-align: center;
}

.video-container {
    position: relative;
}

video, canvas {
    width: 100%;
    border-radius: 10px;
}

canvas {
    position: absolute;
    top: 0;
    left: 0;
    pointer-events: none;
}
</style>

<div class="camera-wrapper">
    <h2>カメラ＋骨格</h2>

    <div class="video-container">
        <video id="camera" autoplay playsinline></video>
        <canvas id="canvas"></canvas>
    </div>

    <button onclick="startCamera()">カメラ起動</button>
    <button onclick="switchCamera()">切替</button>
</div>

<!-- MediaPipe -->
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils"></script>

<script>
let videoElement;
let canvasElement;
let canvasCtx;
let pose;

let videoDevices = [];
let currentDeviceIndex = 0;
let stream;

// 初期化
document.addEventListener('DOMContentLoaded', () => {
    videoElement = document.getElementById('camera');
    canvasElement = document.getElementById('canvas');
    canvasCtx = canvasElement.getContext('2d');

    pose = new Pose({
        locateFile: file => `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${file}`
    });

    pose.setOptions({
        modelComplexity: 1,
        smoothLandmarks: true,
        minDetectionConfidence: 0.5,
        minTrackingConfidence: 0.5
    });

    pose.onResults(results => {
        if (!results.poseLandmarks) return;

        canvasElement.width = videoElement.videoWidth;
        canvasElement.height = videoElement.videoHeight;

        canvasCtx.clearRect(0, 0, canvasElement.width, canvasElement.height);
        drawConnectors(canvasCtx, results.poseLandmarks, POSE_CONNECTIONS);
        drawLandmarks(canvasCtx, results.poseLandmarks);
    });
});

// 外カメで起動
async function startCamera() {

    stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' },
        audio: false
    });

    videoElement.srcObject = stream;
    videoElement.play();

    await loadDevices();

    startPoseLoop(); // ← 🔥 これが重要
}

// カメラ一覧
async function loadDevices() {
    const devices = await navigator.mediaDevices.enumerateDevices();
    videoDevices = devices.filter(d => d.kind === 'videoinput');

    const backIndex = videoDevices.findIndex(d =>
        d.label.toLowerCase().includes('back') ||
        d.label.toLowerCase().includes('rear')
    );

    if (backIndex !== -1) {
        currentDeviceIndex = backIndex;
    }
}

// 切替
function switchCamera() {
    if (videoDevices.length <= 1) return;

    currentDeviceIndex = (currentDeviceIndex + 1) % videoDevices.length;
    startStream(videoDevices[currentDeviceIndex].deviceId);
}

// ストリーム開始
function startStream(deviceId) {

    navigator.mediaDevices.getUserMedia({
        video: { deviceId: { exact: deviceId } },
        audio: false
    })
    .then(newStream => {

        if (videoElement.srcObject) {
            videoElement.srcObject.getTracks().forEach(track => track.stop());
        }

        videoElement.srcObject = newStream;
        videoElement.play();

        startPoseLoop(); // ← 🔥 これ必須
    });
}

// 🔥 毎フレーム骨格解析
function startPoseLoop() {

    async function loop() {
        await pose.send({ image: videoElement });
        requestAnimationFrame(loop);
    }

    loop();
}
</script>

@endsection