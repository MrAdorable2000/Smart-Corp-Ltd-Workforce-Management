<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Face Enrollment</h4>
        <p class="text-muted mb-0">Capture face data for <?= htmlspecialchars($employee['full_name']) ?></p>
    </div>
    <a href="<?= BASE_URL ?>/employees/<?= $employee['id'] ?>" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Back to Profile
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-camera-video me-2"></i>Live Face Capture</span>
                <span class="text-muted text-sm">Anti-spoofing enabled</span>
            </div>
            <div class="card-body">
                <div class="face-container" id="faceContainer">
                    <video id="video" class="face-video" autoplay muted playsinline></video>
                    <canvas id="overlay" class="face-canvas"></canvas>
                    <div class="face-overlay" id="faceOverlay">
                        <div class="face-frame"></div>
                    </div>
                    <div class="face-status" id="faceStatus">
                        <i class="bi bi-camera-video-off"></i> Click "Start Camera"
                    </div>
                </div>

                <div class="face-controls">
                    <button id="startBtn" class="btn btn-primary">
                        <i class="bi bi-camera-video"></i> Start Camera
                    </button>
                    <button id="captureBtn" class="btn btn-success" disabled>
                        <i class="bi bi-camera"></i> Capture Face
                    </button>
                    <button id="stopBtn" class="btn btn-light" disabled>
                        <i class="bi bi-stop-circle"></i> Stop
                    </button>
                </div>

                <!-- Anti-spoofing checks -->
                <div class="anti-spoof-checks mt-4">
                    <h6 class="mb-3"><i class="bi bi-shield-lock me-2"></i>Anti-Spoofing Checks</h6>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <div class="check-item" id="checkBlink">
                                <i class="bi bi-eye"></i>
                                <span>Blinks: <strong id="blinkCount">0</strong>/2</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="check-item" id="checkHead">
                                <i class="bi bi-arrow-left-right"></i>
                                <span>Head Move: <strong id="headMoveCount">0</strong>/1</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="check-item" id="checkSingle">
                                <i class="bi bi-person"></i>
                                <span>Single Face: <strong id="singleFace">-</strong></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="check-item" id="checkSize">
                                <i class="bi bi-fullscreen"></i>
                                <span>Face Size: <strong id="faceSize">-</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(Session::getInstance()->csrfToken()) ?>">
                <input type="hidden" name="employee_id" id="employee_id" value="<?= $employee['id'] ?>">
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person me-2"></i>Employee</div>
            <div class="card-body text-center">
                <div class="employee-avatar mb-3" style="width:80px;height:80px;font-size:24px;margin:0 auto;">
                    <?php if (!empty($employee['photo']) && file_exists(UPLOAD_PATH . '/' . $employee['photo'])): ?>
                        <img src="<?= UPLOAD_URL . '/' . $employee['photo'] ?>" alt="">
                    <?php else: ?>
                        <?= strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <h6 class="mb-0"><?= htmlspecialchars($employee['full_name']) ?></h6>
                <small class="text-muted"><?= htmlspecialchars($employee['employee_code']) ?></small>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Instructions</div>
            <div class="card-body">
                <ol class="small mb-0 ps-3">
                    <li>Click "Start Camera" to begin.</li>
                    <li>Position your face inside the frame.</li>
                    <li>Blink naturally at least 2 times.</li>
                    <li>Move your head slightly (left/right).</li>
                    <li>Ensure only ONE face is in the frame.</li>
                    <li>Click "Capture Face" when ready.</li>
                </ol>
            </div>
        </div>

        <?php if ($existingFaces): ?>
        <div class="card">
            <div class="card-header"><i class="bi bi-collection me-2"></i>Existing Captures</div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($existingFaces as $f): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-sm fw-600"><?= htmlspecialchars($f['label'] ?: 'Capture') ?></div>
                            <small class="text-muted"><?= date('M d, Y H:i', strtotime($f['captured_at'])) ?></small>
                        </div>
                        <?php if ($f['is_primary']): ?>
                            <span class="badge bg-primary">Primary</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.anti-spoof-checks .check-item {
    background: var(--light);
    padding: 12px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    border: 1px solid var(--border);
}
.anti-spoof-checks .check-item i {
    font-size: 18px;
    color: var(--text-muted);
}
.anti-spoof-checks .check-item.passed {
    background: #D1FAE5;
    border-color: var(--success);
    color: #065F46;
}
.anti-spoof-checks .check-item.passed i {
    color: var(--success);
}
.anti-spoof-checks .check-item.failed {
    background: #FEE2E2;
    border-color: var(--danger);
    color: #991B1B;
}
</style>

<?php
// This script gets rendered AFTER jQuery + face-api.js + face-recognition.js are loaded
$scripts = '
    var employeeId = ' . (int)$employee['id'] . ';
    var captureAttempted = false;

    // Status hook for detailed messages
    FaceRecognition.onStatus(function(message, type) {
        var color = type === "success" ? "text-success" : (type === "warning" ? "text-warning" : (type === "error" ? "text-danger" : "text-muted"));
        $("#faceStatus").html("<i class=\"bi bi-camera-video\"></i> " + message);
    });

    // Run diagnostic on load
    function showDiag() {
        var issues = FaceRecognition.checkBrowserSupport();
        if (issues.length > 0) {
            $("#faceStatus").html("<i class=\"bi bi-exclamation-triangle text-danger\"></i> Browser issue: " + issues.join(" "));
        }
    }
    showDiag();

    $("#startBtn").on("click", async function() {
        var btn = $(this);
        btn.prop("disabled", true).html("<span class=\"spinner-border spinner-border-sm\"></span> Loading models...");
        try {
            var issues = FaceRecognition.checkBrowserSupport();
            if (issues.length > 0) {
                throw new Error("Browser issue: " + issues.join(" "));
            }

            await FaceRecognition.loadModels();
            await FaceRecognition.startCamera(document.getElementById("video"));
            FaceRecognition.setCanvas(document.getElementById("overlay"));
            FaceRecognition.setOverlay(document.getElementById("faceOverlay"));

            var video = document.getElementById("video");
            var canvas = document.getElementById("overlay");
            canvas.width = video.offsetWidth;
            canvas.height = video.offsetHeight;

            $("#faceStatus").html("<i class=\"bi bi-camera-video text-success\"></i> Camera active — position your face");
            $("#captureBtn").prop("disabled", false);
            $("#stopBtn").prop("disabled", false);
            btn.html("<i class=\"bi bi-check-circle\"></i> Camera Started");
            FaceRecognition.resetAntiSpoof();
        } catch (err) {
            console.error("Start camera failed:", err);
            btn.prop("disabled", false).html("<i class=\"bi bi-camera-video\"></i> Start Camera");
            $("#faceStatus").html("<i class=\"bi bi-exclamation-triangle text-danger\"></i> " + (err.message || "Failed to start camera"));
            alert("Camera Error:\\n\\n" + (err.message || "Unknown error") + "\\n\\nCommon fixes:\\n1. Use Chrome/Firefox/Edge\\n2. Click camera icon in address bar → Allow\\n3. Close other apps using camera (Zoom, Teams)\\n4. Make sure you are on localhost or HTTPS");
        }
    });

    $("#captureBtn").on("click", async function() {
        var btn = $(this);
        btn.prop("disabled", true).html("<span class=\"spinner-border spinner-border-sm\"></span> Processing...");
        try {
            var descriptor = await FaceRecognition.captureDescriptor();
            if (!descriptor) {
                showToast("No face detected. Please position your face in the frame.", "warning");
                btn.prop("disabled", false).html("<i class=\"bi bi-camera\"></i> Capture Face");
                return;
            }

            var imageData = FaceRecognition.captureImage();
            var antiSpoof = FaceRecognition.getAntiSpoofData();

            var formData = new FormData();
            formData.append("_csrf_token", $("meta[name=\"csrf-token\"]").attr("content"));
            formData.append("employee_id", employeeId);
            formData.append("descriptor", JSON.stringify(descriptor));
            formData.append("image", imageData);
            formData.append("label", "front");
            formData.append("anti_spoof", JSON.stringify(antiSpoof));

            var response = await fetch("' . BASE_URL . '/face/store", {
                method: "POST",
                body: formData
            });
            var result = await response.json();

            if (result.success) {
                $(".face-frame").addClass("matched").removeClass("detecting rejected");
                $("#faceStatus").html("<i class=\"bi bi-check-circle\"></i> Face enrolled successfully!");
                showToast("Face data enrolled successfully!", "success");
                setTimeout(function() { window.location.href = "' . BASE_URL . '/employees/" + employeeId; }, 1500);
            } else {
                showToast(result.message || "Enrollment failed", "error");
                $(".face-frame").addClass("rejected");
                btn.prop("disabled", false).html("<i class=\"bi bi-camera\"></i> Capture Face");
            }
        } catch (err) {
            console.error(err);
            showToast("Capture failed: " + err.message, "error");
            btn.prop("disabled", false).html("<i class=\"bi bi-camera\"></i> Capture Face");
        }
    });

    $("#stopBtn").on("click", function() {
        FaceRecognition.stopCamera();
        $("#startBtn").prop("disabled", false).html("<i class=\"bi bi-camera-video\"></i> Start Camera");
        $("#captureBtn").prop("disabled", true);
        $(this).prop("disabled", true);
        $("#faceStatus").html("<i class=\"bi bi-camera-video-off\"></i> Camera stopped");
    });

    window.updateAntiSpoofUI = function(state, faceCount) {
        $("#blinkCount").text(state.blinkCount);
        $("#headMoveCount").text(state.headMovements);
        $("#singleFace").text(faceCount === 1 ? "Yes" : (faceCount > 1 ? "Multiple!" : "None"));
        $("#faceSize").text(state.faceSizeOk ? "OK" : "Too small");

        $("#checkBlink").toggleClass("passed", state.blinkCount >= 2).toggleClass("failed", state.blinkCount < 2);
        $("#checkHead").toggleClass("passed", state.headMovements >= 1).toggleClass("failed", state.headMovements < 1);
        $("#checkSingle").toggleClass("passed", faceCount === 1).toggleClass("failed", faceCount > 1);
        $("#checkSize").toggleClass("passed", state.faceSizeOk).toggleClass("failed", !state.faceSizeOk);
    };
';
?>
