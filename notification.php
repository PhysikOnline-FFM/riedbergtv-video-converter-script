<?php
/*
Dieses php-script dient zum Versenden einer Notifikationsemail an den Benutzer.
Dabei nimmt es zwei Argumente: die mailaddresse des Empfängers und den Pfad zur
Datei, die als inhalt der mail dient
*/

$mail = $argv[1];
$logFile = $argv[2];

$beginn_mail = "Diese Email wurde dir zugeschickt, da du ein Video auf riedberg.tv hochgeladen und
konvertiert hast, sollte dies nicht der Fall gewesen sein, informiere support@riedberg.tv \n
Output des Konvertierungsskript, wichtig: am Ende werden die Dateien getested: \n";

//open logFile
$logFile_open = fopen($logFile, "r") or die("Unable to open $logFile!");
$mail_content = $beginn_mail . fread($logFile_open, filesize($logFile));
//close logFile
fclose($logFile_open);

//send mail
mail($mail, "[riedberg.tv] Konvertierung abgeschlossen", $mail_content);
