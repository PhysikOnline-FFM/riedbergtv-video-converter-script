#!/usr/bin/env bash

input_file=$1
target_dir=$2
target_file=${target_dir}$(basename $input_file)

#SANITY-CHECKS
echo "checking..."
#Do all packages exist?
if (! dpkg -l ffmpeg > /dev/null ); then
  echo "Package ffmpeg is not installed! -- ABORT"
  exit 1
fi
if (! dpkg -l ffmpeg2theora > /dev/null ); then
  echo "Package ffmpeg2theora is not installed! -- ABORT"
  exit 1
fi
#Does input_file exists?
if [ ! -e $input_file ]; then
  echo "File does not exist! -- ABORT"
  exit 1
fi
#Can script read input_file?
if [ ! -r $input_file ]; then
  echo "Cannot read file! -- ABORT"
  exit 1
fi
#Does target_dir exist?
if [ ! -e $target_dir ]; then
  mkdir $target_dir
  if [ ! -e $target_dir ]; then
  echo "Cannot create target-directory! -- ABORT"
  exit 1
  fi
fi
#Can script write to target_dir?
if [ ! -w $target_dir ]; then
  echo "Cannot write to target-directory! -- ABORT"
  exit 1
fi
echo -e "checking done, no errors \n"

exit 0
