#!/bin/sh

mkdir -p whisper_work_dir

for i in $(ls storage/app/private/output/*.mp3)
do
    basename="$(basename $i)"
    filename="${basename%.*}"
    mv "$i" whisper_work_dir/
    ffmpeg -i "whisper_work_dir/$basename" -ar 16000 -ac 1 "whisper_work_dir/$filename.wav"
    cd whisper_work_dir
    whisper "$filename.wav" --model medium
    rm "$filename.wav"
    rm "$filename.mp3"
    zip -m "../$filename.zip" $filename.*
    cd ..
    mv "$filename.zip" storage/app/private/input
done
