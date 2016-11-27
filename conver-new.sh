#!/usr/bin/env bash

#
# This script is part of our video upload process and handles
# all converting + notification tasks
#
# This script converts a mp4 input file into four output files
# namely two larger .mp4 and .webm and two smaller .mp4 and .webm
# versions.
#
# It also generates a thumbnail from the input video file in two 
# sizes (120/640)
# 
# At last it can also send a mail to the user if everythings done#
# 
# author: Lars Gröber - 27.11.16
#


input_file=$1
user_mail=$2
thumb_time=$3

target_dir=$(dirname $input_file)"/"
target_video_base=${target_dir}$(basename -s '.orig.mp4' $input_file)

log_file=${target_video_base}"-log"
error_file=${target_video_base}"-error"

mail_content=""

#
# function to convert the video
# 
function convert
{
    # Full-HD versions
    # mp4
    ffmpeg -i $input_file -strict experimental -f mp4 -vcodec libx264 -acodec aac\
     -ab 160000 -ac 2 -preset slow -crf 22  ${target_video_base}.mp4 &

    # webm
    ffmpeg -i $input_file -c:v libvpx-vp9 -b:v 0 -crf 31 -threads 8 -speed 1 \
     -tile-columns 6 -frame-parallel 1 -auto-alt-ref 1 -lag-in-frames 25 \
     -c:a libopus -b:a 160K -f webm ${target_video_base}.webm &

    # Small (640x360) versions
    # mp4
    ffmpeg -i $input_file -strict experimental -f mp4 -vcodec libx264 -acodec aac\
     -ab 160000 -ac 2 -preset slow -crf 22 -s 640x360  ${target_video_base}.small.mp4 &

    # webm
    ffmpeg -i $input_file -c:v libvpx-vp9 -b:v 0 -crf 30 -threads 8 -speed 1 \
      -tile-columns 6 -frame-parallel 1 -auto-alt-ref 1 -lag-in-frames 25 \
      -c:a libopus -b:a 160K -f webm -s 640x360 ${target_video_base}.small.webm &

    wait
}

function initial_check
{
    printf("Initial checking START\n")

    # Do all packages exist?
    if (! dpkg -l ffmpeg > /dev/null ); then
        echo "Package ffmpeg is not installed! -- ABORT" 2> $error_file
        exit 1
    else
        echo "Package ffmpeg is installed -- OK"
    fi
    #Can script read input_file?
    if [ ! -r $input_file ]; then
        echo "Cannot read file! -- ABORT" 2> $error_file
        exit 1
    else
        echo "Can read file -- OK"
    fi
    if [ ! -w $target_dir ]; then
        mkdir $target_dir
        if [ ! -w $target_dir ]; then
            echo "Cannot create target-directory! -- ABORT" 2> $error_file
            exit 1
        fi
    else
        echo "Target directory exists -- OK"

    fi

    printf("Initial checking END\n")
}

function send_mail
{
    mail_bottom="\n\nDu bekommst diese Mail, da über einen Account auf \
        https://riedberg.tv mit deiner E-Mail Addresse ein Video hochgeladen wurde."

    echo ${2}${mail_content}$mail_bottom | mail -a $log_file -a $error_file -s $1 $user_mail  
}



trap '[ "$?" -eq 0 ] || send_mail "Fehler bei deinem Video" "\n\nLeider war die Konvertierung\
 nicht erfolgreich, bitte wende dich mit dieser E-Mail an das IT-Team!\n\n' EXIT ERR

