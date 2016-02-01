<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post" enctype="multipart/form-data">
    <p>Wähle das Video zum upload und konvertieren aus:</p>
    <a href="Intro_RiedbergTv.mp4">zum testen</a><br>
    <input type="file" name="toUpload" id="toUpload">
    <br>
    <!-- <input type="text" value="" name="userTargetDir"> -->
    <input type="submit" value="upload und konvertieren" name="upload">
</form>

<?php
/*
Wichtig: Benutzer www-data muss vollen Zugriff auf alle benötigten Dateien und Ordner haben
ToDo: Sanity checks, userTargetDir, senden von mails, sammeln von Informationen über script, ($_SESSION)
*/
    //Define all variables
    //for upload
    $target_dir = "temp/";
    $uploadOk = 1;
    $fileUploaded = 0;

    //UPLOAD
    //upload file to $target_file
    if (isset($_POST["upload"]) && $fileUploaded == 0){
        $target_file = $target_dir . basename($_FILES["toUpload"]["name"]);
        $fileType = pathinfo($target_file,PATHINFO_EXTENSION);
        //for convert
        $final_dir = "video/test/";

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
            if (move_uploaded_file($_FILES["toUpload"]["tmp_name"], $target_file)) {
                echo "Das Video ". basename( $_FILES["toUpload"]["name"]). " wurde hochgeladen. <br>";
                $fileUploaded = 1;
            } else {
                echo "Sorry, es gab einen Fehler. <br>".print_r($_FILES);
            }
        }

        //CONVERT
        //When user uploaded video start convert process
        if ($fileUploaded == 1) {
            //output muss in datei geschrieben werden, sonst wartet php bis skript zu ende ist
            exec ("./convert.sh $target_file $final_dir > temp/" . basename($_FILES["toUpload"]["name"]) . ".log");
            echo "Das Video wird konvertiert und im Ordner $final_dir gespeichert.";
        }
    }
?>
