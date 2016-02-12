<?php
/*
Dieses php-script dient zum Versenden einer Notifikationsemail an den Benutzer,
sobald ein Programm beendet wurde.
Dabei nimmt es zwei Argumente: die mailaddresse des Empfängers
und den Pfad zur Datei, die als inhalt der mail dient
*/

$mail = $argv[1];
$logFile = $argv[2];
//echo "$mail $logFile";

$beginn_mail = "Diese Email wurde dir zugeschickt, da du ein Video auf riedberg.tv hochgeladen und \n
konvertiert hast, sollte dies nicht der Fall gewesen sein, ignoriere diese Mail. \n

Output des Konvertierungsskript, wichtig: am Ende werden die Dateien getested: \n\n ";

/*
echo "Dies sind die verwendeten Argumente: \n";
print_r($argv);
*/

//open logFile
$logFile_open = fopen($logFile, "r") or die("Unable to open $logFile!");

//send mail
$mail_content = $beginn_mail . fread($logFile_open, filesize($logFile));
mail ($mail, "Konvertierung abgeschlossen", $mail_content);

//close logFile
fclose($logFile_open);
?>
