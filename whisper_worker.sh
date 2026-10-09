#!/bin/sh
#
# Holt wartende MP3s per API aus dem Cockpit, transkribiert sie mit whisper
# und lädt das Ergebnis (.zip) wieder hoch.
#
# Benötigt: curl, ffmpeg, whisper, zip
#
#   COCKPIT_URL=https://cockpit.example.org WHISPER_API_TOKEN=geheim ./whisper_worker.sh
#
# Optional: WHISPER_MODEL (Standard: medium), WHISPER_WORK_DIR (Standard: ./whisper_work_dir)

set -eu

: "${COCKPIT_URL:?COCKPIT_URL muss gesetzt sein}"
: "${WHISPER_API_TOKEN:?WHISPER_API_TOKEN muss gesetzt sein}"
WHISPER_MODEL="${WHISPER_MODEL:-medium}"
WORK_DIR="${WHISPER_WORK_DIR:-./whisper_work_dir}"
API="${COCKPIT_URL%/}/api/whisper/jobs"

mkdir -p "$WORK_DIR"

while true
do
    # Nächsten Auftrag abholen (HTTP 204 = nichts zu tun)
    job=$(curl -sS --fail -X POST -H "Authorization: Bearer $WHISPER_API_TOKEN" -H "Accept: application/json" "$API/next")
    [ -z "$job" ] && break

    id=$(echo "$job" | sed -n 's/.*"id":\([0-9]*\).*/\1/p')
    name=$(echo "$job" | sed -n 's/.*"name":"\([^"]*\)".*/\1/p')
    echo "Transkribiere Auftrag $id ($name)"

    job_dir="$WORK_DIR/$id"
    rm -rf "$job_dir"
    mkdir -p "$job_dir"

    curl -sS --fail -H "Authorization: Bearer $WHISPER_API_TOKEN" -o "$job_dir/$name.mp3" "$API/$id/audio"

    ffmpeg -loglevel error -i "$job_dir/$name.mp3" -ar 16000 -ac 1 "$job_dir/$name.wav"
    whisper "$job_dir/$name.wav" --model "$WHISPER_MODEL" --output_dir "$job_dir"
    rm "$job_dir/$name.wav" "$job_dir/$name.mp3"
    (cd "$job_dir" && zip -q -m "$name.zip" "$name".*)

    curl -sS --fail -H "Authorization: Bearer $WHISPER_API_TOKEN" -H "Accept: application/json" \
        -F "result=@$job_dir/$name.zip;type=application/zip" "$API/$id/result"
    echo

    rm -rf "$job_dir"
done
