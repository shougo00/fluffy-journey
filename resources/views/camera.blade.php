@extends('layouts.user')

@section('content')

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
.camera-wrapper {
    max-width: 600px;
    margin: 0 auto;
    text-align: center;
}

video {
    width: 100%;
    border-radius: 10px;
    background: black;
}
</style>

<div class="camera-wrapper">
    <h2>カメラ表示</h2>

    <video id="camera" autoplay playsinline></video>

    <br><br>

    <button onclick="startCamera()">カメラ起動</button>
</div>

<script>
let stream;

function startCamera() {
    navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: 'environment' // 背面カメラ
        },
        audio: false
    })
    .then(s => {
        stream = s;
        const video = document.getElementById('camera');
        video.srcObject = stream;
        video.play();
    })
    .catch(err => {
        alert('カメラ起動エラー: ' + err);
    });
}
</script>

@endsection