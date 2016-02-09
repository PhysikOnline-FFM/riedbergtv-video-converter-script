#!/usr/bin/env bash
# Dieses Konvertierungsskript konvertiert eine .mp4 Datei in die drei Formate
# .mp4, .webm, .ogv sowohl in einer HD-Variante als auch in einer kleineren
# (640x360) Variante
#
# Als Argumente nimmt es zuerst den Pfad zur Quelldatei ($1) und dann den Pfad
# zum Zielordner ($2)
#
# Script created by Lars Gröber, 01.02.2016
#
# ToDo: Fehlermeldungen, parallel-Support, sanity-checks

input_file=$1
target_dir=$2
target_file=${target_dir}$(basename $input_file)
err=0
echo -e "Input: "$@

#1) SANITY-CHECKS

echo "checking..."
#Do all packages exist?
if (! dpkg -l ffmpeg > /dev/null ); then
  echo "Package ffmpeg is not installed! -- ABORT"
  exit 1
else
  echo "Package ffmpeg is installed -- OK"
fi
if (! dpkg -l ffmpeg2theora > /dev/null ); then
  echo "Package ffmpeg2theora is not installed! -- ABORT"
  exit 1
else
  echo "Package ffmpeg2theora is installed -- OK"
fi
#Does input_file exists?
if [ ! -e $input_file ]; then
  echo "File does not exist! -- ABORT"
  exit 1
else
  echo "Inputfile exist -- OK"
fi
#Can script read input_file?
if [ ! -r $input_file ]; then
  echo "Cannot read file! -- ABORT"
  exit 1
else
  echo "Can read Inputfile -- OK"
fi
#Does target_dir exist?
if [ ! -e $target_dir ]; then
  mkdir $target_dir
  if [ ! -e $target_dir ]; then
  echo "Cannot create target-directory! -- ABORT"
  exit 1
  fi
  echo "Created target-directory -- OK"
else
  echo "Target-directory exists -- OK"
fi
#Can script write to target_dir?
if [ ! -w $target_dir ]; then
  echo "Cannot write to target-directory! -- ABORT"
  exit 1
else
  echo "Can write to target-directory -- OK"
fi
echo -e "checking done \n"


#2) Start converting

echo -e "CONVERTING START: `date +%c` \n"

# Full-HD versions
#ffmpeg -i $input_file -f webm -vcodec libvpx -acodec libvorbis -ab 160000 -crf 22 ${target_file}.webm &

#ffmpeg -i $input_file -strict experimental -f mp4 -vcodec libx264 -acodec aac -ab 160000 -ac 2 -preset slow -crf 22  ${target_file}.mp4 &

#ffmpeg2theora $input_file --videoquality 8 --audioquality 6 --frontend -o ${target_file}.ogv &


# Small (640x360) versions
ffmpeg -i $input_file -strict experimental -f mp4 -vcodec libx264 -acodec aac -ab 160000 -ac 2 -preset slow -crf 22 -s 640x360  ${target_file}.small.mp4 &

ffmpeg -i $input_file -f webm -vcodec libvpx -acodec libvorbis -ab 160000 -crf 22 -s 640x360  ${target_file}.small.webm &

ffmpeg2theora $input_file --videoquality 8 --audioquality 6 --width 640  --frontend -o ${target_file}.small.ogv &


wait

#3) Check if all files exist

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

echo -e "\n""CONVERTING END: `date +%c`"
exit $err
