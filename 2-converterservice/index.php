<meta charset="UTF-8">

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" enctype="multipart/form-data">
    <p>Wähle das Video zum upload und konvertieren aus:</p>
    <a href="Intro_RiedbergTv.mp4">zum testen</a><br>
    <input type="file" name="toUpload" id="toUpload">
    <br>
    <!--  -->
    <input type="submit" value="upload" name="upload">


<?php
/*
Wichtig: Benutzer www-data muss vollen Zugriff auf alle benötigten Dateien und Ordner haben
ToDo: security! , userTargetDir, senden von mails, sammeln von Informationen über script,
*/
    //Define all variables
    //for upload
    session_start();
    $temp_dir = "temp/";
    $uploadOk = 1;
    $fileUploaded = 0;


    //UPLOAD
    //upload file to $_SESSION['target_file']
    if (isset($_POST["upload"]) && $fileUploaded == 0){

        $_SESSION['basename'] = basename($_FILES["toUpload"]["name"]);
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
          echo '<input type="text" value="email" name="userMail"> <br>
                <input type="text" value="video/test/" name="userTargetDir"> <br>
                <input type="submit" value="überprüfen und konvertieren" name="convert">';
        }
    }
    //CONVERT
    if (isset($_POST["convert"])) {

      //IMPORTANT: improve security!
      $log_dir = "logs/";
      $final_dir = $_POST["userTargetDir"];
      $mail = ($_POST["userMail"] == "email" ? "lars@groeber-hg.de" : $_POST["userMail"]);
      //command to convert video and save output in file:
      $convert_cmd = "./convert.sh ".$_SESSION['target_file']." $final_dir";
      $log_file = $log_dir . $_SESSION['basename'] . ".log";

      //are there any immediate errors?
      $output = array();
      $return = 0;
      exec($convert_cmd . " --sanity", $output, $return);
      if ($return != 0) {
        echo "Es gibt Fehler: <br>";
        print_r($output);
        session_unset();
        die();
      }

      echo "Das Video wird konvertiert und im Ordner $final_dir gespeichert. <br>";
      $pid = shell_exec ($convert_cmd . " > " . $log_file . " & echo $!");
      $pid = trim($pid);


      //start notification.php to send mail
      if ($pid == null) {
        die("Skript pid Nummer nicht gefunden!");
      }

      $notification_cmf = "php notification.php '$pid' '$mail' '$log_file' &";
      shell_exec($notification_cmf);
      echo "Dir wird eine E-Mail ($mail) zugeschickt, sobald die Konvertierung abgeschlossen ist.";
    }
?>
</form>
