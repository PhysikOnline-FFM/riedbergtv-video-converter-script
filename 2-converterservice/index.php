<meta charset="UTF-8">

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" enctype="multipart/form-data">
    <p>Wähle das Video zum upload und konvertieren aus:</p>
    <a href="Intro_RiedbergTv.mp4">zum testen</a><br>
    <input type="file" name="toUpload">
    <br>
    <!--  -->
    <input type="submit" value="upload" name="upload">


<?php

/*
Wichtig: Benutzer www-data muss vollen Zugriff auf alle benötigten Dateien und Ordner haben, Skripte müssen
         ausführbar sein
TODO: bessere Benutzerfreundlichkeit, Möglichkeit Arbeit fortzusetzen, Löschen des Videos im temporären Ordner
*/
    session_start();
    //Define all variables
    //for upload
    $temp_dir = "temp/";
    $uploadOk = 1;
    $fileUploaded = 0;

    //UPLOAD
    //upload file to $_SESSION['target_file']
    if (isset($_POST["upload"]) && $fileUploaded == 0){

        $_SESSION['basename'] = input_sec(basename($_FILES["toUpload"]["name"]));
        $_SESSION['target_file'] = $temp_dir . $_SESSION['basename'];

        $fileType = pathinfo($_SESSION['target_file'],PATHINFO_EXTENSION);
        //for convert



        //sanity checks
        if ($fileType != "mp4") {
        echo "Bitte nur .mp4 Dateien hochladen!";
        $uploadOk = 0;
        }
          /*just for testing
          if ($_FILES["toUpload"]["size"] > 2000000) {
              echo "Das Video ist zu groß! <br>";
              $uploadOk = 0;
          }
          */
        //missing: does file already exist, do I have suitable permissions

        //Actual Upload
        if ($uploadOk == 0) {
            echo "Das Video wurde nicht hochgeladen! <br>";
        }
        else {
            if (move_uploaded_file($_FILES["toUpload"]["tmp_name"], $_SESSION['target_file'])) {
                echo "Das Video " . $_SESSION["basename"] . " wurde hochgeladen. <br>";
                $fileUploaded = 1;
            } else {
                echo "Sorry, es gab einen Fehler. <br>".print_r($_FILES);
                session_unset();
            }
        }

        //When video is uploaded, make user enter further information
        if ($fileUploaded == 1) {
          echo 'Email: <input type="email" value="" name="userMail"> <br>
                Ordner: <input type="text" value="video/test/" name="userTargetDir"> <br>
                <input type="submit" value="überprüfen und konvertieren" name="convert">';
        }
    }

    //CONVERT
    if (isset($_POST["convert"])) {
      //log-Directory used to store output from convert.sh
      $log_dir = "logs/";
      $log_file = $log_dir . $_SESSION['basename'] . ".log";
      //file that needs to be converted
      $video_file = $_SESSION['target_file'];
      //directory used to store converted files
      $final_dir = input_sec($_POST["userTargetDir"]);
      $_SESSION['final_dir'] = $final_dir;
      //file with content for mail
      $beginn_file = $log_dir . "/_beginn.log";
      //mailaddress used to send notification
      $mail = ($_POST["userMail"] == "" ? "lars@groeber-hg.de" : input_sec($_POST["userMail"]));

      //command to convert video and save output in file:
      $check_cmd = "./check.sh $video_file $final_dir";
      $convert_cmd = "./convert.sh $video_file $final_dir >> $log_file &";
      //$start_cmd = "./start_convert.sh $video_file $final_dir $log_file";

      //copy content of beginn_file to new log_file to send mail with nice information
      copy($beginn_file, $log_file);

      //are there any immediate errors?
      $output = array();
      $return = 0;
      exec($check_cmd, $output, $return);
      if ($return != 0) {
        echo "Es gibt Fehler, kontaktiere einen Administrator, wenn du nicht weißt, woran es liegt: <br>";
        print_r($output);
        session_unset();
        die();
      }

      echo "Das Video wird konvertiert und im Ordner $final_dir gespeichert. <br>";
      exec ($convert_cmd);
      sleep(1);
      //convert.sh needs to store own process id in file "pid"
      $pid = trim(file_get_contents("pid"));

      if ($pid == null || $pid == "") {
        die("Skript pid Nummer nicht gefunden!");
      }

      //start notification.php to send mail
      $notification_cmd = "php notification.php '$pid' '$mail' '$log_file' > $log_dir/_debugNotification.log 2>&1 &";
      exec($notification_cmd);

      echo "Dir wird eine E-Mail ($mail) zugeschickt, sobald die Konvertierung abgeschlossen ist. <br>
            Erstelle ein Thumbnail: <br>
            <input type='text' value='hh:mm:ss' name='input_time'>
            <input type='submit' value='Thumbnail erstellen' name='thumbnail'>";
      //session_unset();
    }

    if (isset($_POST["thumbnail"])) {
      $video_file = $_SESSION['target_file'];
      $final_dir = $_SESSION['final_dir'];
      //get time for thumbnail from user
      $input_time = input_sec($_POST['input_time']);

      $thumbnail_cmd = "./thumbnails.sh $video_file $final_dir $input_time 1 2>&1";
      //echo "Dieser Command wird ausgeführt: $thumbnail_cmd <br>";

      //make thumbnail in $final_dir
      exec ($thumbnail_cmd);

      //thumbnail_file depends on thumbnails.sh script
      $thumbnail_file = $final_dir . basename($video_file) . "-v1-thumb640.jpg";
      //show thumbnail to user and ask for confirmation
      echo "<br> <img src='$thumbnail_file' alt='Thumbnail not found'> <br>
            <input type='text' value='hh:mm:ss' name='input_time'>
            <input type='submit' value='neues Thumbnail erzeugen' name='thumbnail'> <br>
            <input type='submit' value='Oder Erzeugung abschließen' name='thumbnail_end'>";
    }

    if (isset($_POST["thumbnail_end"])) {
      echo "<br> Dein Video und Thumbnail wurden im Ordner " . $_SESSION["final_dir"] . " gespeichert. <br>
            Bis zum nächsten Mal!";
      session_unset();

    }

    function input_sec($data) {
      $data = htmlspecialchars($data);
      $data = trim($data);
      $data = stripslashes($data);
      escapeshellarg($data);
      return $data;
    }


?>
</form>
