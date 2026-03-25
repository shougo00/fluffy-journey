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

button {
    margin-top: 8px;
    padding: 10px 15px;
}

#videoList img {
    width: 120px;
    height: 90px;
    object-fit: cover;
}
</style>

<div class="camera-wrapper">
    <h2>弓道カメラ（完全版）</h2>

    <div class="video-container">
        <video id="camera" autoplay playsinline></video>
        <canvas id="canvas"></canvas>
    </div>

    <button onclick="startCamera()">カメラ起動</button>
    <button onclick="switchCamera()">切替</button>
    <br>
    <button onclick="startRecording()">録画開始</button>
    <button onclick="stopRecording()">録画停止</button>

    <h3>録画一覧</h3>
    <div id="videoList" style="display:flex; flex-wrap:wrap; gap:10px;"></div>

    <h3>再生</h3>
    <video id="player" controls></video>
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

// 録画
let mediaRecorder;
let recordedChunks = [];
let isRecording = false;

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

        canvasElement.width = videoElement.videoWidth;
        canvasElement.height = videoElement.videoHeight;

        // カメラ映像描画
        canvasCtx.drawImage(videoElement, 0, 0,
            canvasElement.width,
            canvasElement.height
        );

        if (!results.poseLandmarks) return;

        drawConnectors(canvasCtx, results.poseLandmarks, POSE_CONNECTIONS);
        drawLandmarks(canvasCtx, results.poseLandmarks);
    });
});

// ===== カメラ =====

async function startCamera() {

    const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' },
        audio: false
    });

    videoElement.srcObject = stream;
    videoElement.play();

    await loadDevices();
    startPoseLoop();
}

async function loadDevices() {
    const devices = await navigator.mediaDevices.enumerateDevices();
    videoDevices = devices.filter(d => d.kind === 'videoinput');

    const backIndex = videoDevices.findIndex(d =>
        d.label.toLowerCase().includes('back') ||
        d.label.toLowerCase().includes('rear')
    );

    if (backIndex !== -1) currentDeviceIndex = backIndex;
}

function switchCamera() {

    if (videoDevices.length <= 1) return;

    currentDeviceIndex = (currentDeviceIndex + 1) % videoDevices.length;
    startStream(videoDevices[currentDeviceIndex].deviceId);
}

function startStream(deviceId) {

    navigator.mediaDevices.getUserMedia({
        video: { deviceId: { exact: deviceId } },
        audio: false
    })
    .then(stream => {

        if (videoElement.srcObject) {
            videoElement.srcObject.getTracks().forEach(track => track.stop());
        }

        videoElement.srcObject = stream;
        videoElement.play();

        startPoseLoop();
    });
}

function startPoseLoop() {
    async function loop() {
        await pose.send({ image: videoElement });
        requestAnimationFrame(loop);
    }
    loop();
}

// ===== 録画（骨格付き） =====

function startRecording() {

    if (!canvasElement) {
        alert('canvasなし');
        return;
    }

    recordedChunks = [];

    const canvasStream = canvasElement.captureStream(30);

    let options = { mimeType: 'video/webm' };
    if (!MediaRecorder.isTypeSupported('video/webm')) {
        options = {};
    }

    try {
        mediaRecorder = new MediaRecorder(canvasStream, options);
    } catch (e) {
        alert('録画非対応');
        return;
    }

    mediaRecorder.ondataavailable = e => {
        if (e.data.size > 0) recordedChunks.push(e.data);
    };

    mediaRecorder.onstop = () => {
        const blob = new Blob(recordedChunks, { type: 'video/webm' });
        const url = URL.createObjectURL(blob);

        createThumbnail(url);
    };

    mediaRecorder.start();
    isRecording = true;
}

function stopRecording() {

    if (!mediaRecorder || !isRecording) {
        alert('録画してない');
        return;
    }

    mediaRecorder.stop();
    isRecording = false;
}

// ===== サムネ生成 =====

function createThumbnail(videoUrl) {

    const video = document.createElement('video');
    video.src = videoUrl;
    video.muted = true;
    video.playsInline = true;

    // 🔥 重要：読み込み後にフレーム取得
    video.addEventListener('loadedmetadata', () => {
        video.currentTime = 0.1; // 少し進める
    });

    video.addEventListener('seeked', () => {

        const canvas = document.createElement('canvas');
        canvas.width = 160;
        canvas.height = 120;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const img = document.createElement('img');
        img.src = canvas.toDataURL();
        img.style.cursor = 'pointer';
        img.style.border = '1px solid #ccc';

        img.onclick = () => {
            const player = document.getElementById('player');
            player.src = videoUrl;
            player.play();
        };

        document.getElementById('videoList').appendChild(img);

    });
}
</script>

@endsection