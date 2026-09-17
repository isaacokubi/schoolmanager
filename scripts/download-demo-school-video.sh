#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET_DIR="$ROOT_DIR/storage/app/public/school-media"

mkdir -p "$TARGET_DIR"

if ! command -v curl >/dev/null 2>&1; then
  echo "curl is required. Install curl and run this script again." >&2
  exit 1
fi
if ! command -v file >/dev/null 2>&1; then
  echo "file is required. Install file and run this script again." >&2
  exit 1
fi

# Match the seeded school_media database paths so local and Vercel render the
# same demo records without committing binary media to Git.
declare -A SOURCES=(
  ["demo-20260917201809-3b9f556a57.jpg"]="https://samplefile.com/samples/download/image/jpeg/jpeg_1000x600_sample_file_36KB.jpeg/"
  ["demo-20260917201810-f6989e8c29.jpg"]="https://samplefile.com/samples/download/image/jpeg/jpeg_2000x1200_sample_file_72KB.jpeg/"
  ["demo-20260917201812-02fc9a8cbc.jpg"]="https://samplefile.com/samples/download/image/jpeg/jpeg_500x300_sample_file_15KB.jpeg/"
  ["demo-20260917201836-4b089bf7e9.mp4"]="https://samplefile.com/samples/download/video/mp4/mp4_60s_sample_file_3.4MB.mp4/"
)

for name in "${!SOURCES[@]}"; do
  target="$TARGET_DIR/$name"
  curl -L --fail --silent --show-error --retry 3 -o "$target" "${SOURCES[$name]}"

  if [[ "$name" == *.mp4 ]]; then
    bytes="$(stat -c%s "$target")"
    if [ "$bytes" -ge 4300000 ]; then
      echo "Downloaded demo video is too large: $bytes bytes" >&2
      rm -f "$target"
      exit 1
    fi
    file "$target" | grep -Eiq 'ISO Media|MPEG-4|MP4' || {
      echo "Downloaded file does not appear to be a valid MP4: $target" >&2
      rm -f "$target"
      exit 1
    }
  else
    file "$target" | grep -Eiq 'JPEG|image' || {
      echo "Downloaded file does not appear to be a valid JPEG: $target" >&2
      rm -f "$target"
      exit 1
    }
  fi
done

echo "Demo school media ready: $TARGET_DIR"
find "$TARGET_DIR" -maxdepth 1 -type f -printf '%f | %s bytes\n' | sort
