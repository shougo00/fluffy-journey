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
    pointer-events: none; /* ← クリック貫通 */
}

button {
    margin-top: 10px;
    padding: 10px 20px;
    margin-right: 5px;
}
</style>

<div class="camera-wrapper">
    <h2>カメラ＋骨格検出</h2>

    <div class="video-container">
        <video id="camera" autoplay playsinline></video>
        <canvas id="canvas"></canvas>
    </div>

    <button onclick="startCamera()">カメラ起動</button>
    <button onclick="switchCamera()">内外カメラ切替</button>
</div>

<!-- MediaPipe -->
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils"></script>

<script>
let videoElement;
let canvasElement;
let canvasCtx;
let pose;
let cameraInstance;
let currentFacing = 'environment'; // 外カメスタート

// 初期化
document.addEventListener('DOMContentLoaded', () => {

    videoElement = document.getElementById('camera');
    canvasElement = document.getElementById('canvas');
    canvasCtx = canvasElement.getContext('2d');

    pose = new Pose({
        locateFile: (file) => {
            return `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${file}`;
        }
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

// カメラ起動
function startCamera() {
    startStream(currentFacing);
}

// カメラ開始処理（共通）
function startStream(facingMode) {

    navigator.mediaDevices.getUserMedia({
        video: { facingMode: facingMode },
        audio: false
    })
    .then(stream => {

        // 前のカメラ停止
        if (videoElement.srcObject) {
            videoElement.srcObject.getTracks().forEach(track => track.stop());
        }

        videoElement.srcObject = stream;
        videoElement.play();

        // MediaPipe停止→再起動
        if (cameraInstance) {
            cameraInstance.stop();
        }

        cameraInstance = new Camera(videoElement, {
            onFrame: async () => {
                await pose.send({ image: videoElement });
            },
            width: 640,
            height: 480
        });

        cameraInstance.start();
    })
    .catch(err => {
        alert(err.name + ": " + err.message);
    });
}

// カメラ切り替え
function switchCamera() {
    currentFacing = (currentFacing === 'environment') ? 'user' : 'environment';
    startStream(currentFacing);
}
</script>

@endsection