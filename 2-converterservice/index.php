<meta charset="UTF-8">

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" enctype="multipart/form-data">
    <p>Wähle das Video zum upload und konvertieren aus:</p>
    <a href="Intro_RiedbergTv.mp4">zum testen</a><br>
    <input type="file" name="toUpload" id="toUpload">
    <br>
    <!-- <input type="text" value="" name="userTargetDir"> -->
    <input type="submit" value="upload und überprüfen" name="upload">


<?php
/*
Wichtig: Benutzer www-data muss vollen Zugriff auf alle benötigten Dateien und Ordner haben
ToDo: Sanity checks, userTargetDir, senden von mails, sammeln von Informationen über script, ($_SESSION)
*/
    //Define all variables
    //for upload
    session_start();
    $temp_dir = "temp/";
    $uploadOk = 1;
    $fileUploaded = 0;
    $log_dir = "logs/";

    //UPLOAD
    //upload file to $_SESSION['target_file']
    if (isset($_POST["upload"]) && $fileUploaded == 0){

        $_SESSION['basename'] = basename($_FILES["toUpload"]["name"]);
        $_SESSION['target_file'] = $temp_dir . $_SESSION['basename'];
        $fileType = pathinfo($_SESSION['target_file'],PATHINFO_EXTENSION);
        //for convert

        $_SESSION["final_dir"] = "video/test/";

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

        //CONVERT
        //When user uploaded video start convert process
        if ($fileUploaded == 1) {
          //are there any immediate errors?
          $output = array();
          $return = 0;
          exec("./convert.sh " . $_SESSION['target_file'] . " " . $_SESSION['final_dir'] . " --sanity", $output, $return);
          if ($return != 0) {
            echo "Es gibt Fehler: <br>";
            foreach ($output as $i) {
              echo "$i <br>";
            }
            session_unset();
          }
          else {
            echo '<input type="submit" value="konvertieren" name="convert">';
          }

        }
    }
    if (isset($_POST["convert"])) {
      //output muss in datei geschrieben werden, sonst wartet php bis skript zu ende ist
      exec ("./convert.sh " . $_SESSION['target_file'] . " " . $_SESSION['final_dir'] . " > $log_dir" . $_SESSION['basename'] . ".log");
      echo "Das Video wird konvertiert und im Ordner " . $_SESSION['final_dir'] . " gespeichert.";
    }
?>
</form>
