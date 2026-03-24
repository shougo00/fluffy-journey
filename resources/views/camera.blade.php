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
    width: 100%;
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
    margin-top: 10px;
    padding: 10px 20px;
    margin-right: 5px;
}
</style>

<div class="camera-wrapper">
    <h2>カメラ＋骨格検出（完全版）</h2>

    <div class="video-container">
        <video id="camera" autoplay playsinline></video>
        <canvas id="canvas"></canvas>
    </div>

    <button onclick="startCamera()">カメラ起動</button>
    <button onclick="switchCamera()">カメラ切替</button>
</div>

<!-- MediaPipe -->
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils"></script>

<script>
let videoElement;
let stream;
let videoDevices = [];
let currentDeviceIndex = 0;

// 初期化
document.addEventListener('DOMContentLoaded', () => {
    videoElement = document.getElementById('camera');
});

// ① 最初は外カメで起動
async function startCamera() {

    // まず外カメで起動（これ重要）
    stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' },
        audio: false
    });

    videoElement.srcObject = stream;
    videoElement.play();

    // ② ここで初めてカメラ一覧取得
    await loadDevices();
}

// カメラ一覧取得
async function loadDevices() {
    const devices = await navigator.mediaDevices.enumerateDevices();
    videoDevices = devices.filter(d => d.kind === 'videoinput');

    console.log(videoDevices);

    // 外カメを特定
    const backIndex = videoDevices.findIndex(d =>
        d.label.toLowerCase().includes('back') ||
        d.label.toLowerCase().includes('rear')
    );

    if (backIndex !== -1) {
        currentDeviceIndex = backIndex;
    }
}

// 切り替え
function switchCamera() {

    if (videoDevices.length <= 1) {
        alert('カメラ1個しかない');
        return;
    }

    currentDeviceIndex = (currentDeviceIndex + 1) % videoDevices.length;

    startStream(videoDevices[currentDeviceIndex].deviceId);
}

// deviceIdで起動
function startStream(deviceId) {

    navigator.mediaDevices.getUserMedia({
        video: { deviceId: { exact: deviceId } },
        audio: false
    })
    .then(newStream => {

        // 前のカメラ停止
        if (videoElement.srcObject) {
            videoElement.srcObject.getTracks().forEach(track => track.stop());
        }

        videoElement.srcObject = newStream;
        videoElement.play();
    })
    .catch(err => {
        alert(err);
    });
}
</script>
@endsection