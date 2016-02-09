#!/usr/bin/env bash
input_file=$1
target_dir=$2
log_file=$3

./convert.sh $input_file $target_dir >> $log_file &
echo $!

exit 0
