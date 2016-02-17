#!/usr/bin/env bash
# Erstellt Vorschaubilder fuer Videos auf dem Terminal

# $1 = Video (sollte klein sein sonst dauert es ewig)
# $2 = Ausgabebild (JPG, hat gleiches Format wie Video)
# $3 = Zeitpunkt zum Snapshot, Format hh:mm:ss[.xxx] z.B. 00:00:31

input_file=$1
target_dir=$2
thumb_time=$3

target_basename=${target_dir}$(basename $input_file)-orig
target_file=$target_basename.jpg

if [ -e $target_file ]; then
  rm $target_file ${target_basename}-thumb640.jpg ${target_basename}-thumb120.jpg
fi

ffmpeg -i $input_file -ss $thumb_time -vframes 1 -f image2 -loglevel panic  $target_file

# erstellt für alle *-vX.jpg Dateien im Ordner die richtigen thumbnails (wobei X eine Zahl ist), also zB. für huth-v2.jpg

#create thumbnail with 120 pixel width
find . -name '*-orig.jpg' | xargs -n1 -I {} convert  -quality 95 -resize 120x {} {}-thumb;
find . -name '*.jpg-thumb' | xargs -n1 rename 's/.jpg-thumb$/-thumb120.jpg/'

#create thumbnail with 640 pixel width
find . -name '*-orig.jpg' | xargs -n1 -I {} convert  -quality 95 -resize 640x {} {}-thumb;
find . -name '*.jpg-thumb' | xargs -n1 rename 's/.jpg-thumb$/-thumb640.jpg/'

exit 0
