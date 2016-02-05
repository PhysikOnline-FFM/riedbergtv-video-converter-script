<?php
/*
Dieses php-script dient zum Versenden einer Naofikationsemail an den Benutzer,
sobald ein Programm beendet wurde.
Dabei nimmt es drei Argumente: pid des Skripts, die mailaddresse des Empfängers
und den Pfad zur Datei, die als inhalt der mail dient
*/
$pid = $argv[1];
$mail = $argv[2];
$logFile = $argv[3];
//echo "$pid $mail $logFile";						 

print_r($argv);

//check if script is running
while (file_exists( "/proc/".$pid)) {
  sleep (5);
}
echo "Skript is not running";
//open logFile
$logFile_open = fopen($logFile, "r") or die("Unable to open file!");

//send mail
mail ($mail, "Konvertierung abgeschlossen", fread($logFile_open, filesize($logFile)));

//close logFile
fclose($logFile_open);
?>
