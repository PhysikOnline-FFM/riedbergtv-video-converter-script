#!/usr/bin/env bash
#Dieses Skript konvertiert eine mp4 Datei in die #drei zwei Ausgabeformate .mp4, .webm #, .ogv

#Dabei nimmt es den Input-File, den Ausgabe-Ordner, die Mailadresse des Nutzers und den 
#verwendeten Logfile

#Die beiden Skripte check.sh und notification.php müssen im selben Ordner liegen

# TODO: really check videos

function show_usage
{
    echo "Usage: convert.sh -f INPUT_FILE [ -t TARGET_FILE ] [ -m USER_MAIL ] [ -l LOG_FILE ] [ -a LINK_TO_WEBSITE ]"
    echo "If option -t is not used, files will be generated in current directory."
    echo "      --no-checks     Does no sanity checks before converting."
    echo "      --no-convert    Debug option, does not convert video."
}

#
# Function to execute some sanity checks
#
function sanity_checks
{
    #SANITY-CHECKS
    echo "CHECK START"
    #Do all packages exist?
    if (! dpkg -l ffmpeg > /dev/null ); then
      echo "Package ffmpeg is not installed! -- ABORT"
      return 1
    fi
    if (! dpkg -l ffmpeg2theora > /dev/null ); then
      echo "Package ffmpeg2theora is not installed! -- ABORT"
      return 1
    fi
    #Does input_file exist?
    if [ ! -e $input_file ]; then
      echo "File does not exist! -- ABORT"
      exit 1
    fi
    #Can script read input_file?
    if [ ! -r $input_file ]; then
      echo "Cannot read file! -- ABORT"
      return 1
    fi
    #Does target_dir exist?
    if [ ! -e $target_dir ]; then
      mkdir $target_dir
      if [ ! -e $target_dir ]; then
      echo "Cannot create target-directory! -- ABORT"
      return 1
      fi
    fi
    #Can script write to target_dir?
    if [ ! -w $target_dir ]; then
      echo "Cannot write to target-directory! -- ABORT"
      return 1
    fi
}

#
# Function to convert the video
#
function convert_video
{
    echo -e "CONVERTING START: `date +%c` \n"
    
    #Actual Converting
    #Full-HD versions
    #mp4
    ffmpeg -i $input_file -strict experimental -f mp4 -vcodec libx264 -acodec aac -ab 160000 -ac 2 -preset slow -crf 22  ${target_file}.mp4 &
    
    #webm
    ffmpeg -i $input_file -c:v libvpx-vp9 -b:v 0 -crf 31 -threads 8 -speed 1 \
     -tile-columns 6 -frame-parallel 1 -auto-alt-ref 1 -lag-in-frames 25 \
     -c:a libopus -b:a 160K -f webm ${target_file}.webm &
    
    # Small (640x360) versions
    #mp4
    ffmpeg -i $input_file -strict experimental -f mp4 -vcodec libx264 -acodec aac -ab 160000 -ac 2 -preset slow -crf 22 -s 640x360  ${target_file}.small.mp4 &
    
    #webm
    ffmpeg -i $input_file -c:v libvpx-vp9 -b:v 0 -crf 30 -threads 8 -speed 1 \
      -tile-columns 6 -frame-parallel 1 -auto-alt-ref 1 -lag-in-frames 25 \
      -c:a libopus -b:a 160K -f webm -s 640x360 ${target_file}.small.webm &
    
    #wait until converting is done
    wait
    
    echo -e "\nCONVERTING END: `date +%c`"
}

function file_checks
{
    #Check if all files exist
    
    #array of all fileextensions
    fileTypes=(".webm" ".mp4" ".small.webm" ".small.mp4")
    
    echo -e "\n""START FILE CHECKING: "
    for (( i=0;i<${#fileTypes[@]};i++ )); do
        if [ -e ${target_file}${fileTypes[${i}]} ]; then
          echo "SUCCESSFUL "${fileTypes[${i}]}
        else
          echo "ERROR "${fileTypes[${i}]}"-file does not exist"
          err+=1
        fi
    done
}

function send_mail
{
    # send confirmation mail
    arg=""
    if [ "$log_file" != "" ]; then
        arg=${arg}"-a $log_file"
    fi

    mail -v $arg -s "[riedberg.tv] Konvertierung abgeschlossen" $user_mail <<EOF
Hi,

du bekommst diese Mail, da du ein Video "$file_base" auf riedberg.tv hochgeladen hast.
Dieses ist nun konvertiert, du kannst auf die erstellte Wikiseite zugreifen und sie erweitern:
$link_to_wikiSite
   
Dein RTV IT-Team

Falls etwas nicht funktioniert, kannst du dir hier die Logfiles anschauen:
http://riedberg.tv${target_dir#/home/riedbergtv/www.riedberg.tv}
EOF
}

do_sanity_check=true
do_convert=true
do_send_mail=false

input_file=""
target_dir="./"
user_mail=""
log_file=""
link_to_wikiSite="http://riedberg.tv"

# --no-checks   Does not do the sanity checks at the beginning
# -m            Option to send mail, takes address as argument
# -f            Inputfile
# -t            Directory to save files (w/o this will generate files in current directory)
# -l            Logfile to attach to mail
# -a            Link to website
# --no-convert  Debug option, does not actually convert file

TEMP=$(getopt -o m:f:t:l:a: --long no-checks,no-convert -n "convert.sh" -- "$@")

if [[ $? -ne 0 ]]; then echo "Terminating..." >&2; exit 1; fi

eval set -- "$TEMP"

while true; do
    case "$1" in
        -m ) user_mail="$2"; do_send_mail=true; shift 2 ;;
        -f ) input_file="$2"; shift 2 ;;
        -t ) target_dir="$2"; shift 2 ;;
        -l ) log_file="$2"; shift 2 ;;
        -a ) link_to_wikiSite="$2"; shift 2 ;;
        --no-checks ) do_sanity_check=false; shift ;;
        --no-convert ) do_convert=false; shift ;;
        -- ) shift; break ;;
        * ) break ;;
    esac
done

if [ "$input_file" == "" ]; then
    show_usage
    exit 2
fi

file_base=$(basename -s '.orig.mp4' $input_file)
target_file=${target_dir}$file_base

if [ "$do_sanity_check" = true ]; then
    sanity_checks
    if [ $? -ne 0 ]; then exit 1; fi
fi

if [ "$do_convert" = true ]; then
    #convert_video
    file_checks
fi

if [ "$do_send_mail" = true ]; then
    echo "Sending Mail..."
    send_mail
fi

exit 0
