# Dieses Konvertierungsskript konvertiert eine .mp4 Datei in die drei Formate
# .mp4, .webm, .ogv sowohl in einer HD-Variante als auch in einer kleineren
# (640x360) Variante
#
# Als Argumente nimmt es zuerst den Pfad zur Quelldatei ($1) und dann den Pfad
# zur Zieldatei ($2)
#
# ToDo: Fehlermeldungen, parallel-Support, sanity-checks

filename=$1
target_dir=$2
echo "Konvertierung gestartet"


# Full-HD Versionen
ffmpeg -i $filename -f webm -vcodec libvpx -acodec libvorbis -ab 160000 -crf 22 ${target_dir}.webm
echo "webm Konvertierung beendet"
ffmpeg -i $filename -strict experimental -f mp4 -vcodec libx264 -acodec aac -ab 160000 -ac 2 -preset slow -crf 22  ${target_dir}.mp4
echo "mp4 Konvertierung beendet"
ffmpeg2theora $filename --videoquality 8 --audioquality 6 --frontend -o ${target_dir}.ogv
echo "ogv Konvertierung beendet"

# Small (640x360) Versionen
ffmpeg -i $filename -strict experimental -f mp4 -vcodec libx264 -acodec aac -ab 160000 -ac 2 -preset slow -crf 22 -s 640x360  ${target_dir}.small.mp4
echo "mp4-small Konvertierung beendet"
ffmpeg -i $filename -f webm -vcodec libvpx -acodec libvorbis -ab 160000 -crf 22 -s 640x360  ${target_dir}.small.webm
echo "webm-small Konvertierung beendet"
ffmpeg2theora $filename --videoquality 8 --audioquality 6 --width 640  --frontend -o ${target_dir}.small.ogv
echo "ogv-small Konvertierung beendet"

wait

echo "Konvertierung beendet"
exit 0
