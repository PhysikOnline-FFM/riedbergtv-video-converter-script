#!/usr/bin/env bash
#Dieses Skript konvertiert eine mp4 Datei in die drei Ausgabeformate .mp4, .webm, .ogv

#Dabei nimmt es den Input-File, den Ausgabe-Ordner, die Mailadresse des Nutzers und den 
#verwendeten Logfile

#Die beiden Skripte check.sh und notification.php müssen im selben Ordner liegen

input_file=$1
target_dir=$2
user_mail=$3
log_file=$4


file_base=$(basename -s '.orig.mp4' $input_file)
target_file=${target_dir}$file_base
err=0

#run check.sh to check if there could be any errors
./check.sh $input_file $target_dir

#if check.sh finds any errors exit script
if [ ! $? -eq 0 ]; then 
	exit 1
fi

echo -e "CONVERTING START: `date +%c` \n"

#Actual Converting
#Full-HD versions
ffmpeg -i $input_file -f webm -vcodec libvpx -acodec libvorbis -ab 160000 -crf 22 ${target_file}.webm &
ffmpeg -i $input_file -strict experimental -f mp4 -vcodec libx264 -acodec aac -ab 160000 -ac 2 -preset slow -crf 22  ${target_file}.mp4 &
#ffmpeg2theora $input_file --videoquality 8 --audioquality 6 --no-skeleton --frontend -o ${target_file}.ogv &

# Small (640x360) versions
ffmpeg -i $input_file -strict experimental -f mp4 -vcodec libx264 -acodec aac -ab 160000 -ac 2 -preset slow -crf 22 -s 640x360  ${target_file}.small.mp4 &
ffmpeg -i $input_file -f webm -vcodec libvpx -acodec libvorbis -ab 160000 -crf 22 -s 640x360  ${target_file}.small.webm &
#ffmpeg2theora $input_file --videoquality 8 --audioquality 6 --width 640 --no-skeleton --frontend -o ${target_file}.small.ogv &

#wait until converting is done
wait

echo -e "\n""CONVERTING END: `date +%c`"

#Check if all files exist

#array of all fileextensions
fileTypes=(".webm" ".mp4" ".ogv" ".small.webm" ".small.mp4" ".small.ogv")

echo -e "\n""START FILE CHECKING: "
for (( i=0;i<${#fileTypes[@]};i++ )); do
    if [ -e ${target_file}${fileTypes[${i}]} ]; then
      echo "SUCCESSFUL "${fileTypes[${i}]}
    else
      echo "ERROR "${fileTypes[${i}]}"-file does not exist"
      err+=1
    fi
done

#start notification.phpto send user a mail
php notification.php $user_mail $log_file 2>&1

exit $err
