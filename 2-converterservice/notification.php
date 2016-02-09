<?php
/*
Dieses php-script dient zum Versenden einer Notifikationsemail an den Benutzer,
sobald ein Programm beendet wurde.
Dabei nimmt es drei Argumente: pid des Skripts, die mailaddresse des Empfängers
und den Pfad zur Datei, die als inhalt der mail dient
*/
$pid = $argv[1];
$mail = $argv[2];
$logFile = $argv[3];
//echo "$pid $mail $logFile";

echo "Dies sind die verwendeten Argumente: \n";
print_r($argv);

//check if script is running
$i = 1;
while (file_exists( "/proc/".$pid)) {
  sleep (5);
  echo "Skript is running for " . $i*5 . " Seconds \n";
  $i+=1;
}
echo "Skript is not running \n";
//open logFile
$logFile_open = fopen($logFile, "r") or die("Unable to open $logFile!");

//send mail
mail ($mail, "Konvertierung abgeschlossen", fread($logFile_open, filesize($logFile)));

//close logFile
fclose($logFile_open);
?>
