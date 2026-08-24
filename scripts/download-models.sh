#!/bin/bash
# Download face-api.js models locally for offline use
# This makes camera startup INSTANT (no CDN download needed each time)

MODELS_DIR="/home/z/my-project/download/smart-attendance/public/assets/models"
mkdir -p "$MODELS_DIR"
cd "$MODELS_DIR"

echo "Downloading face-api.js models to: $(pwd)"
echo "This will download ~6MB total."
echo ""

BASE_URL="https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights"

FILES=(
    "tiny_face_detector_model-weights_manifest.json"
    "tiny_face_detector_model-shard1"
    "face_landmark_68_model-weights_manifest.json"
    "face_landmark_68_model-shard1"
    "face_recognition_model-weights_manifest.json"
    "face_recognition_model-shard1"
    "face_recognition_model-shard2"
    "face_expression_model-weights_manifest.json"
    "face_expression_model-shard1"
)

for f in "${FILES[@]}"; do
    echo "Downloading: $f"
    curl -sL -o "$f" "$BASE_URL/$f"
    if [ -s "$f" ]; then
        echo "  OK ($(du -h "$f" | cut -f1))"
    else
        echo "  FAILED"
        rm -f "$f"
    fi
done

echo ""
echo "=== Done! ==="
echo "Models saved in: $MODELS_DIR"
