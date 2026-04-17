@extends('layouts.user')

@section('content')

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
.camera-wrapper { max-width: 600px; margin: 0 auto; text-align: center; }
.video-container { position: relative; }

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

#recordingIndicator {
    position: absolute;
    top: 10px;
    left: 10px;
    color: red;
    font-weight: bold;
    font-size: 18px;
    display: none;
    animation: blink 1s infinite;
}

@keyframes blink {
    0% { opacity: 1; }
    50% { opacity: 0.2; }
    100% { opacity: 1; }
}

#videoList img {
    width:120px;
    height:90px;
    object-fit:cover;
}
</style>

<div class="camera-wrapper">
    <h2>弓道AIカメラ</h2>

    <div class="video-container">
        <video id="camera" autoplay playsinline></video>
        <canvas id="canvas"></canvas>
        <div id="recordingIndicator">● REC</div>
    </div>

    <button onclick="startCamera()">カメラ起動</button><br>
    <button onclick="startRecording()">録画開始</button>
    <button onclick="stopRecording()">録画停止</button>
    <button onclick="shareVideo()">保存 / 共有</button>

    <h3>録画一覧</h3>
    <div id="videoList" style="display:flex;flex-wrap:wrap;gap:10px;"></div>

    <h3>再生</h3>
    <video id="player" controls playsinline></video>
</div>

<script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose"></script>
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/drawing_utils"></script>

<script>
let videoElement, canvasElement, canvasCtx, pose;
let isPoseRunning = false;

// 録画
let mediaRecorder, recordedChunks = [], isRecording = false;
let lastBlob = null;

document.addEventListener('DOMContentLoaded', () => {

    videoElement = document.getElementById('camera');
    canvasElement = document.getElementById('canvas');
    canvasCtx = canvasElement.getContext('2d');

    loadVideos();

    pose = new Pose({
        locateFile: file => `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${file}`
    });

    pose.setOptions({
        modelComplexity: 1,
        smoothLandmarks: true,
        minDetectionConfidence: 0.3,
        minTrackingConfidence: 0.5
    });

    pose.onResults(results => {

        if (!videoElement.videoWidth) return;

        canvasElement.width = videoElement.videoWidth;
        canvasElement.height = videoElement.videoHeight;

        canvasCtx.drawImage(videoElement, 0, 0);

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

    await new Promise(resolve => {
        videoElement.onloadedmetadata = () => {
            videoElement.play();
            resolve();
        };
    });

    isPoseRunning = false;
    startPoseLoop();
}

// ===== 骨格ループ =====
function startPoseLoop() {

    if (isPoseRunning) return;
    isPoseRunning = true;

    async function loop() {

        if (videoElement.readyState >= 2) {
            await pose.send({ image: videoElement });
        }

        requestAnimationFrame(loop);
    }

    loop();
}

// ===== 録画開始 =====
function startRecording() {

    document.getElementById('recordingIndicator').style.display = 'block';

    const canvasStream = canvasElement.captureStream(30);

    recordedChunks = [];

    let options = { mimeType: 'video/webm;codecs=vp9' };

    if (!MediaRecorder.isTypeSupported(options.mimeType)) {
        options = { mimeType: 'video/webm' };
    }

    try {
        mediaRecorder = new MediaRecorder(canvasStream, options);
    } catch {
        alert('録画非対応');
        return;
    }

    mediaRecorder.ondataavailable = e => {
        if (e.data.size > 0) recordedChunks.push(e.data);
    };

    mediaRecorder.onstop = async () => {

        document.getElementById('recordingIndicator').style.display = 'none';

        const blob = new Blob(recordedChunks, { type: 'video/webm' });

        lastBlob = blob; // 🔥 共有用に保持

        // ===== サーバー保存 =====
        const formData = new FormData();
        formData.append('video', blob, 'video.webm');

        try {
            const res = await fetch('/video/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await res.json();
            createThumbnail(data.url);

        } catch {
            console.log('サーバー保存失敗');
        }
    };

    mediaRecorder.start();
    isRecording = true;
}

// ===== 録画停止 =====
function stopRecording() {

    if (!mediaRecorder || !isRecording) return;

    mediaRecorder.stop();
    isRecording = false;
}

// ===== 🔥 共有（iPhone対応） =====
async function shareVideo() {

    if (!lastBlob) {
        alert('録画がありません');
        return;
    }

    const file = new File([lastBlob], 'kyudo.webm', { type: 'video/webm' });

    if (navigator.canShare && navigator.canShare({ files: [file] })) {

        try {
            await navigator.share({
                files: [file],
                title: '弓道動画'
            });
            return;
        } catch (e) {
            console.log('共有キャンセル or 失敗');
        }
    }

    // fallback
    const url = URL.createObjectURL(lastBlob);

    const a = document.createElement('a');
    a.href = url;
    a.download = 'kyudo_' + Date.now() + '.webm';

    document.body.appendChild(a);
    a.click();
    a.remove();

    URL.revokeObjectURL(url);

    alert('共有非対応のためダウンロードしました');
}

// ===== 一覧取得 =====
async function loadVideos() {

    try {
        const res = await fetch('/video/list');
        const videos = await res.json();

        videos.forEach(v => createThumbnail(v.url));
    } catch {}
}

// ===== サムネ =====
function createThumbnail(videoUrl) {

    const video = document.createElement('video');
    video.src = videoUrl;
    video.muted = true;
    video.playsInline = true;

    video.addEventListener('loadeddata', () => {

        video.play().then(() => {

            setTimeout(() => {

                video.pause();

                const canvas = document.createElement('canvas');
                canvas.width = 160;
                canvas.height = 120;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const img = document.createElement('img');
                img.src = canvas.toDataURL();
                img.style.cursor = 'pointer';

                img.onclick = () => {
                    const player = document.getElementById('player');
                    player.src = videoUrl;
                    player.load();
                    player.play().catch(()=>{});
                };

                document.getElementById('videoList').prepend(img);

            }, 200);

        }).catch(()=>{});
    });
}
</script>

@endsection