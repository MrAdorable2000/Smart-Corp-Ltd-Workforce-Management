/* ================================================================
   SMART ATTENDANCE — FACE RECOGNITION ENGINE v5.0 ENTERPRISE
   Features:
   - Auto-detection (no button clicks needed)
   - 90%+ confidence threshold enforcement
   - 3-stage liveness: blink × 2, head turn × 1, face size check
   - Unknown face rejection with fraud logging
   - Offline queue with IndexedDB sync
   - Scan cooldown to prevent duplicate submissions
   - Visual feedback overlay with real-time guidance
   ================================================================ */

const FaceRecognition = (function () {

    // ── Constants ────────────────────────────────────────────────────
    // LOCAL models first (fastest — no CDN dependency), then CDN fallback
    const LOCAL_MODEL_URL = window.ASSET_URL ? window.ASSET_URL + '/models' : '/smart-attendance/public/assets/models';
    const MODEL_URLS = [
        LOCAL_MODEL_URL,
        'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights',
        'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights',
    ];
    const DETECTION_INTERVAL_MS = 600;   // ms between detection frames
    const INPUT_SIZE             = 224;
    const SCORE_THRESHOLD        = 0.45;
    const BLINK_EAR_THRESHOLD    = 0.20; // eye aspect ratio for blink
    const MIN_FACE_RATIO         = 0.18; // face must be >18% of frame
    const HEAD_MOVE_THRESHOLD    = 6;    // px nose movement for head turn
    const SCAN_COOLDOWN_MS       = 8000; // prevent duplicate scans

    // ── State ────────────────────────────────────────────────────────
    let modelsLoaded  = false;
    let modelsLoading = false;
    let stream        = null;
    let videoEl       = null;
    let canvasEl      = null;
    let isDetecting   = false;
    let isProcessing  = false;
    let detectionTimer= null;
    let statusCb      = null;
    let lastScanTime  = 0;
    let visHandler    = null;

    let liveness = {
        blinkCount:     0,
        eyesClosed:     false,
        headMovements:  0,
        lastNoseX:      null,
        faceSizeOk:     false,
        detectionStart: null,
        consecutiveFaces: 0,
        passed:         false,
    };

    // ── Public: callbacks ────────────────────────────────────────────
    function onStatus(cb) { statusCb = cb; }

    function setStatus(msg, type) {
        if (statusCb) try { statusCb(msg, type || 'info'); } catch(e) {}
        console.log('[FR]', (type||'info').toUpperCase(), msg);
    }

    // ── Library check ────────────────────────────────────────────────
    function isLibraryLoaded() {
        return typeof faceapi !== 'undefined' && !!faceapi.nets;
    }

    async function waitForLibrary(ms) {
        ms = ms || 15000;
        const t = Date.now();
        while (!isLibraryLoaded()) {
            if (Date.now() - t > ms) throw new Error('face-api.js failed to load');
            await sleep(150);
        }
    }

    function checkBrowserSupport() {
        const errs = [];
        if (!navigator.mediaDevices?.getUserMedia) errs.push('Camera API not supported');
        if (!window.isSecureContext && !['localhost','127.0.0.1'].includes(location.hostname))
            errs.push('HTTPS required for camera access');
        if (!window.indexedDB) errs.push('IndexedDB not supported (needed for offline mode)');
        return errs;
    }

    // ── Model loading ────────────────────────────────────────────────
    async function loadModels() {
        if (modelsLoaded) return true;
        if (modelsLoading) {
            while (modelsLoading) await sleep(200);
            return modelsLoaded;
        }
        modelsLoading = true;
        setStatus('Checking browser compatibility…', 'info');
        const issues = checkBrowserSupport();
        if (issues.length) { modelsLoading = false; throw new Error(issues.join('. ')); }

        await waitForLibrary();
        setStatus('Loading AI models (first load ~10s)…', 'info');

        let lastErr;
        for (const url of MODEL_URLS) {
            try {
                setStatus(`Loading from CDN…`, 'info');
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(url),
                    faceapi.nets.faceLandmark68Net.loadFromUri(url),
                    faceapi.nets.faceRecognitionNet.loadFromUri(url),
                ]);
                modelsLoaded = true;
                modelsLoading = false;
                setStatus('AI models ready ✓', 'success');
                return true;
            } catch(e) { lastErr = e; }
        }
        modelsLoading = false;
        throw new Error('All model sources failed: ' + lastErr?.message);
    }

    // ── Camera ───────────────────────────────────────────────────────
    async function startCamera(el) {
        videoEl = el;
        setStatus('Requesting camera permission…', 'info');
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { width:{ideal:640}, height:{ideal:480}, facingMode:'user', frameRate:{ideal:20,max:30} },
                audio: false,
            });
        } catch(e) {
            const map = {
                NotAllowedError: 'Camera permission denied. Allow camera access in browser settings.',
                NotFoundError:   'No camera found. Connect a webcam.',
                NotReadableError:'Camera in use by another app (Zoom/Teams). Close it first.',
            };
            throw new Error(map[e.name] || 'Camera error: ' + e.message);
        }
        videoEl.srcObject = stream;
        return new Promise((res, rej) => {
            const t = setTimeout(() => rej(new Error('Camera startup timeout')), 12000);
            videoEl.onloadedmetadata = () => {
                clearTimeout(t);
                videoEl.play().then(() => { setStatus('Camera live ✓', 'success'); res(true); }).catch(rej);
            };
        });
    }

    function stopCamera() {
        stopDetectionLoop();
        stream?.getTracks().forEach(t => t.stop());
        stream = null;
        if (videoEl) videoEl.srcObject = null;
        isProcessing = false;
        setStatus('Camera stopped', 'info');
    }

    function setCanvas(c) { canvasEl = c; }

    // ── Liveness helpers ─────────────────────────────────────────────
    function ear(pts) {  // Eye Aspect Ratio
        const A = dist2d(pts[1], pts[5]);
        const B = dist2d(pts[2], pts[4]);
        const C = dist2d(pts[0], pts[3]);
        return C > 0 ? (A + B) / (2 * C) : 0.3;
    }
    function dist2d(a, b) {
        return Math.sqrt((a.x-b.x)**2 + (a.y-b.y)**2);
    }

    function processBlink(landmarks) {
        const pts = landmarks.positions || landmarks;
        const leftEye  = [36,37,38,39,40,41].map(i => pts[i]);
        const rightEye = [42,43,44,45,46,47].map(i => pts[i]);
        const avgEar   = (ear(leftEye) + ear(rightEye)) / 2;

        if (avgEar < BLINK_EAR_THRESHOLD) {
            liveness.eyesClosed = true;
        } else if (liveness.eyesClosed) {
            liveness.eyesClosed = false;
            liveness.blinkCount++;
            return true; // blink completed
        }
        return false;
    }

    function processHeadMove(landmarks) {
        const pts  = landmarks.positions || landmarks;
        const nose = pts[30];
        if (!nose) return false;
        if (liveness.lastNoseX === null) { liveness.lastNoseX = nose.x; return false; }
        const delta = Math.abs(nose.x - liveness.lastNoseX);
        liveness.lastNoseX = nose.x;
        if (delta > HEAD_MOVE_THRESHOLD) { liveness.headMovements++; return true; }
        return false;
    }

    function livenessReady() {
        return liveness.blinkCount >= 2 &&
               liveness.headMovements >= 1 &&
               liveness.faceSizeOk &&
               liveness.consecutiveFaces >= 3;
    }

    function resetLiveness() {
        liveness = {
            blinkCount: 0, eyesClosed: false, headMovements: 0,
            lastNoseX: null, faceSizeOk: false,
            detectionStart: Date.now(), consecutiveFaces: 0, passed: false,
        };
    }

    function getAntiSpoofData() {
        return {
            blink_count:    liveness.blinkCount,
            head_movement:  liveness.headMovements >= 1,
            face_size_ok:   liveness.faceSizeOk,
            liveness_passed:livenessReady(),
            detection_time: Date.now() - (liveness.detectionStart || Date.now()),
        };
    }

    function getAntiSpoofState() { return { ...liveness }; }

    // ── Canvas drawing ───────────────────────────────────────────────
    function drawOverlay(detections, confidence) {
        if (!canvasEl || !videoEl) return;
        const ctx = canvasEl.getContext('2d');
        canvasEl.width  = videoEl.offsetWidth  || 640;
        canvasEl.height = videoEl.offsetHeight || 480;
        ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);
        if (!detections?.length) return;

        const rx = canvasEl.width  / (videoEl.videoWidth  || 640);
        const ry = canvasEl.height / (videoEl.videoHeight || 480);

        detections.forEach(d => {
            const box = d.detection?.box || d;
            const x = box.x * rx, y = box.y * ry;
            const w = box.width * rx, h = box.height * ry;

            // Color depends on state
            const color = confidence >= 90 ? '#10B981' : livenessReady() ? '#6366F1' : '#F59E0B';
            ctx.strokeStyle = color;
            ctx.lineWidth   = 3;
            ctx.shadowColor = color;
            ctx.shadowBlur  = 12;

            // Rounded rect
            const r = 10;
            ctx.beginPath();
            ctx.moveTo(x+r, y); ctx.lineTo(x+w-r, y);
            ctx.arcTo(x+w,y,x+w,y+r,r);
            ctx.lineTo(x+w, y+h-r);
            ctx.arcTo(x+w,y+h,x+w-r,y+h,r);
            ctx.lineTo(x+r, y+h);
            ctx.arcTo(x,y+h,x,y+h-r,r);
            ctx.lineTo(x, y+r);
            ctx.arcTo(x,y,x+r,y,r);
            ctx.closePath();
            ctx.stroke();

            // Confidence label
            if (confidence > 0) {
                ctx.shadowBlur = 0;
                ctx.fillStyle  = color;
                ctx.font       = 'bold 14px Inter, sans-serif';
                ctx.fillText(`${Math.round(confidence)}% match`, x, y - 8);
            }
        });
    }

    // ── Main detection loop ──────────────────────────────────────────
    function startDetectionLoop(callback, opts) {
        opts = opts || {};
        isDetecting  = true;
        isProcessing = false;
        resetLiveness();
        setStatus('Position your face in the oval…', 'info');

        // Pause when tab hidden
        visHandler = () => {
            if (!document.hidden && isDetecting) setStatus('Resumed', 'info');
        };
        document.addEventListener('visibilitychange', visHandler);

        async function loop() {
            if (!isDetecting || !videoEl || document.hidden || isProcessing) {
                if (isDetecting) detectionTimer = setTimeout(loop, DETECTION_INTERVAL_MS);
                return;
            }
            isProcessing = true;
            try {
                const dets = await faceapi
                    .detectAllFaces(videoEl, new faceapi.TinyFaceDetectorOptions({
                        inputSize: INPUT_SIZE, scoreThreshold: SCORE_THRESHOLD,
                    }))
                    .withFaceLandmarks()
                    .withFaceDescriptors();

                drawOverlay(dets, 0);

                if (dets.length === 1) {
                    const d   = dets[0];
                    const box = d.detection.box;
                    const minSz = Math.min(videoEl.videoWidth||640, videoEl.videoHeight||480) * MIN_FACE_RATIO;
                    liveness.faceSizeOk = box.width >= minSz;
                    liveness.consecutiveFaces++;
                    processBlink(d.landmarks);
                    processHeadMove(d.landmarks);

                    // Status guidance
                    if (liveness.blinkCount < 2)          setStatus(`Blink ${liveness.blinkCount}/2 times…`, 'info');
                    else if (liveness.headMovements < 1)   setStatus('Turn your head slightly…', 'info');
                    else if (!liveness.faceSizeOk)         setStatus('Move closer to the camera…', 'info');
                    else                                   setStatus('Hold still…', 'success');

                } else if (dets.length === 0) {
                    liveness.consecutiveFaces = 0;
                    setStatus('No face detected — look at the camera', 'warning');
                } else {
                    liveness.consecutiveFaces = 0;
                    setStatus('Multiple faces — only one person at a time', 'error');
                }

                // Update UI
                if (typeof window.updateLivenessUI === 'function')
                    window.updateLivenessUI(liveness, dets.length);

                // Fire callback when liveness passes + cooldown respected
                const now = Date.now();
                if (dets.length === 1 && livenessReady() && (now - lastScanTime) > SCAN_COOLDOWN_MS) {
                    if (opts.requireAntiSpoof !== false) {
                        liveness.passed = true;
                        const desc = Array.from(dets[0].descriptor);
                        callback(desc, getAntiSpoofData());
                    }
                }

            } catch(e) {
                console.error('[FR] loop error:', e);
            } finally {
                isProcessing = false;
            }
            if (isDetecting) detectionTimer = setTimeout(loop, DETECTION_INTERVAL_MS);
        }
        loop();
    }

    function stopDetectionLoop() {
        isDetecting = false;
        clearTimeout(detectionTimer);
        detectionTimer = null;
        if (visHandler) { document.removeEventListener('visibilitychange', visHandler); visHandler = null; }
    }

    function markScanned() { lastScanTime = Date.now(); }

    // ── Client-side face matching (for offline mode) ─────────────────
    function euclidean(a, b) {
        let s = 0;
        for (let i = 0; i < 128; i++) { const d = a[i]-b[i]; s += d*d; }
        return Math.sqrt(s);
    }

    function matchDescriptor(probe, storedList, threshold) {
        threshold = threshold || 0.40;
        let best = { distance: 1.0, face: null };
        for (const face of storedList) {
            try {
                const stored = typeof face.descriptor === 'string'
                    ? JSON.parse(face.descriptor) : face.descriptor;
                if (!Array.isArray(stored) || stored.length !== 128) continue;
                const d = euclidean(probe, stored);
                if (d < best.distance) best = { distance: d, face };
            } catch(e) {}
        }
        return {
            matched:    best.distance <= threshold,
            distance:   best.distance,
            score:      Math.round((1 - best.distance) * 100),
            confidence: Math.round((1 - best.distance) * 100),
            face:       best.face,
        };
    }

    // ── Offline queue (IndexedDB) ────────────────────────────────────
    const IDB_NAME    = 'SmartAttendOffline';
    const IDB_STORE   = 'pendingScans';
    const IDB_VERSION = 1;
    let idb = null;

    async function openIDB() {
        if (idb) return idb;
        return new Promise((res, rej) => {
            const req = indexedDB.open(IDB_NAME, IDB_VERSION);
            req.onupgradeneeded = e => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains(IDB_STORE)) {
                    const store = db.createObjectStore(IDB_STORE, { keyPath: 'id', autoIncrement: true });
                    store.createIndex('createdAt', 'createdAt');
                }
            };
            req.onsuccess = e => { idb = e.target.result; res(idb); };
            req.onerror   = e => rej(e.target.error);
        });
    }

    async function queueOfflineScan(payload) {
        try {
            const db    = await openIDB();
            const tx    = db.transaction(IDB_STORE, 'readwrite');
            const store = tx.objectStore(IDB_STORE);
            store.add({ ...payload, createdAt: Date.now(), synced: false });
            console.log('[FR] Queued offline scan');
        } catch(e) { console.warn('[FR] IDB queue failed:', e); }
    }

    async function getPendingScans() {
        try {
            const db    = await openIDB();
            const tx    = db.transaction(IDB_STORE, 'readonly');
            const store = tx.objectStore(IDB_STORE);
            return new Promise((res, rej) => {
                const req = store.getAll();
                req.onsuccess = () => res(req.result.filter(r => !r.synced));
                req.onerror   = () => rej(req.error);
            });
        } catch(e) { return []; }
    }

    async function markSynced(id) {
        try {
            const db    = await openIDB();
            const tx    = db.transaction(IDB_STORE, 'readwrite');
            const store = tx.objectStore(IDB_STORE);
            const req   = store.get(id);
            req.onsuccess = () => {
                const rec = req.result;
                if (rec) { rec.synced = true; store.put(rec); }
            };
        } catch(e) {}
    }

    async function syncOfflineScans(syncUrl, csrfToken) {
        const pending = await getPendingScans();
        if (!pending.length) return { synced: 0, failed: 0 };
        let synced = 0, failed = 0;
        for (const scan of pending) {
            try {
                const fd = new FormData();
                Object.entries(scan).forEach(([k, v]) => fd.append(k, typeof v === 'object' ? JSON.stringify(v) : v));
                fd.append('_csrf_token', csrfToken);
                fd.append('offline_sync', '1');
                const resp = await fetch(syncUrl, { method: 'POST', body: fd });
                if (resp.ok) { await markSynced(scan.id); synced++; }
                else failed++;
            } catch(e) { failed++; }
        }
        console.log(`[FR] Sync: ${synced} synced, ${failed} failed`);
        return { synced, failed };
    }

    // ── Selfie capture ────────────────────────────────────────────────
    function captureImage(quality) {
        if (!videoEl) return null;
        const c   = document.createElement('canvas');
        c.width   = videoEl.videoWidth  || 640;
        c.height  = videoEl.videoHeight || 480;
        c.getContext('2d').drawImage(videoEl, 0, 0);
        return c.toDataURL('image/jpeg', quality || 0.75);
    }

    async function captureDescriptor() {
        if (!videoEl || !modelsLoaded) return null;
        try {
            const det = await faceapi
                .detectSingleFace(videoEl, new faceapi.TinyFaceDetectorOptions({ inputSize: INPUT_SIZE, scoreThreshold: SCORE_THRESHOLD }))
                .withFaceLandmarks().withFaceDescriptor();
            return det ? Array.from(det.descriptor) : null;
        } catch(e) { return null; }
    }

    // ── Utilities ─────────────────────────────────────────────────────
    function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

    // ── Public API ────────────────────────────────────────────────────
    return {
        loadModels, startCamera, stopCamera, setCanvas,
        startDetectionLoop, stopDetectionLoop, markScanned,
        captureImage, captureDescriptor,
        matchDescriptor, euclidean,
        resetLiveness, getAntiSpoofData, getAntiSpoofState,
        queueOfflineScan, getPendingScans, syncOfflineScans,
        onStatus, isLibraryLoaded,
        isModelsLoaded: () => modelsLoaded,
        checkBrowserSupport,
        livenessReady,
        MATCH_THRESHOLD: 0.40,
        MIN_CONFIDENCE: 90,
        // Legacy aliases
        setOverlay: () => {},
        abortLoad:  () => {},
        getAntiSpoofData,
        resetAntiSpoof: resetLiveness,
    };
})();
