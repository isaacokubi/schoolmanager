#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET_DIR="$ROOT_DIR/storage/app/public/school-media"
TARGET_FILE="$TARGET_DIR/demo-school-video-3-4mb.mp4"
SOURCE_URL="https://samplefile.com/samples/download/video/mp4/mp4_60s_sample_file_3.4MB.mp4/"

mkdir -p "$TARGET_DIR"

if ! command -v curl >/dev/null 2>&1; then
  echo "curl is required. Install curl and run this script again." >&2
  exit 1
fi

curl -L --fail --silent --show-error --retry 3 \
  -o "$TARGET_FILE" \
  "$SOURCE_URL"

BYTES="$(stat -c%s "$TARGET_FILE")"
if [ "$BYTES" -ge 4300000 ]; then
  echo "Downloaded demo video is too large: $BYTES bytes" >&2
  rm -f "$TARGET_FILE"
  exit 1
fi

if ! file "$TARGET_FILE" | grep -Eiq 'ISO Media|MPEG-4|MP4'; then
  echo "Downloaded file does not appear to be a valid MP4." >&2
  rm -f "$TARGET_FILE"
  exit 1
fi

echo "Demo MP4 ready: $TARGET_FILE"
echo "Size: $BYTES bytes"
