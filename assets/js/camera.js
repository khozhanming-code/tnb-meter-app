// Version 2 - Live Camera Capture
// This deliberately uses getUserMedia + <canvas> instead of <input type="file">,
// so there is no OS file/gallery picker available anywhere in this flow - the
// only way to produce a photo is to stream the live camera and capture a frame
// from it right now.

(function () {
    const video = document.getElementById('cameraStream');
    const canvas = document.getElementById('captureCanvas');
    const preview = document.getElementById('capturedPreview');
    const startBtn = document.getElementById('startCameraBtn');
    const captureBtn = document.getElementById('captureBtn');
    const retakeBtn = document.getElementById('retakeBtn');
    const photoDataInput = document.getElementById('photoData');
    const submitBtn = document.getElementById('submitBtn');

    let stream = null;

    function setSubmitEnabled(enabled) {
        submitBtn.disabled = !enabled;
    }

    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' },
                audio: false,
            });
            video.srcObject = stream;
            video.style.display = 'block';
            preview.style.display = 'none';
            captureBtn.disabled = false;
            startBtn.disabled = true;
        } catch (err) {
            alert('Unable to access camera: ' + err.message + '\nPlease allow camera permission in your browser.');
        }
    }

    function capturePhoto() {
        if (!stream) return;
        const w = video.videoWidth || 640;
        const h = video.videoHeight || 480;
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, w, h);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        photoDataInput.value = dataUrl;

        preview.src = dataUrl;
        preview.style.display = 'block';
        video.style.display = 'none';

        stopCamera();

        captureBtn.style.display = 'none';
        startBtn.style.display = 'none';
        retakeBtn.style.display = 'inline-block';

        setSubmitEnabled(true);
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach((t) => t.stop());
            stream = null;
        }
    }

    function retake() {
        photoDataInput.value = '';
        preview.style.display = 'none';
        setSubmitEnabled(false);

        captureBtn.style.display = 'inline-block';
        captureBtn.disabled = true;
        startBtn.style.display = 'inline-block';
        startBtn.disabled = false;
        retakeBtn.style.display = 'none';

        video.style.display = 'none';
    }

    startBtn.addEventListener('click', startCamera);
    captureBtn.addEventListener('click', capturePhoto);
    retakeBtn.addEventListener('click', retake);

    // Belt-and-braces: block submit if somehow triggered without a captured photo.
    document.getElementById('readingForm').addEventListener('submit', function (e) {
        if (!photoDataInput.value) {
            e.preventDefault();
            alert('Please capture a live meter photo before submitting.');
        }
    });

    setSubmitEnabled(false);
})();
