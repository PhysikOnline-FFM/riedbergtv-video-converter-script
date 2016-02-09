#!/usr/bin/env bash
input_file=$1
target_dir=$2
target_file=${target_dir}$(basename $input_file)
err=0

echo $$ > pid

echo -e "CONVERTING START: `date +%c` \n"

# Full-HD versions
ffmpeg -i $input_file -f webm -vcodec libvpx -acodec libvorbis -ab 160000 -crf 22 ${target_file}.webm &

ffmpeg -i $input_file -strict experimental -f mp4 -vcodec libx264 -acodec aac -ab 160000 -ac 2 -preset slow -crf 22  ${target_file}.mp4 &

ffmpeg2theora $input_file --videoquality 8 --audioquality 6 --frontend -o ${target_file}.ogv &


# Small (640x360) versions
ffmpeg -i $input_file -strict experimental -f mp4 -vcodec libx264 -acodec aac -ab 160000 -ac 2 -preset slow -crf 22 -s 640x360  ${target_file}.small.mp4 &

ffmpeg -i $input_file -f webm -vcodec libvpx -acodec libvorbis -ab 160000 -crf 22 -s 640x360  ${target_file}.small.webm &

ffmpeg2theora $input_file --videoquality 8 --audioquality 6 --width 640  --frontend -o ${target_file}.small.ogv &


wait

echo -e "\n""CONVERTING END: `date +%c`"

#Check if all files exist

#array of all fileextensions
fileTypes=(".webm" ".mp4" ".ogv" ".small.webm" ".small.mp4" ".small.ogv")

echo -e "\n""checking files:"
for (( i=0;i<${#fileTypes[@]};i++ )); do
    if [ -e ${target_file}${fileTypes[${i}]} ]; then
      echo ${fileTypes[${i}]}" converting successful"
    else
      echo ${fileTypes[${i}]}" converting NOT successful, file does not exist -- ERROR"
      err+=1
    fi
done

exit $err
