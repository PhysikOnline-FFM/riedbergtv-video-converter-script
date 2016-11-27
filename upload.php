<?php

//error_reporting(E_ALL); 
//ini_set('display_errors', 1); 

    /*
        TODO::
        - improve mail notification (try to use media wiki or shell)
        - check file type
        - remove old folders of unsecussful upload attempts (low prio)
    */
    
    require_once(__DIR__ .'/vendor/autoload.php');
    
    use Cake\Filesystem\File; 
    use Cake\Filesystem\Folder; 
    use Dilab\Network\SimpleRequest;
    use Dilab\Network\SimpleResponse;
    use Dilab\Resumable;
    
    // Creating out own class of Resumable to overload some functions.
    // The benefit is that we have not to change the original source code (Y).
    class RTVResumable extends Resumable {
        
        protected $returnData = null;
        private $absHtdcs;
        private $wikiUser;
        private $wikiOut;
        private $specialPage;
        
        protected static $allowed_filetarpathes = array(
            'Campus Riedberg' => 'campus/riedberg/', 
            'Interview (Biologiewissenschaften)' => 'interviews/biologie/', 
            'Interview (Chemie)' => 'interviews/chemie/', 
            'Interview (Geowissenschaften)' => 'interviews/geow/', 
            'Interview (Informatik)' => 'interviews/info/', 
            'Interview (Mathematik)' => 'interviews/mathe/', 
            'Interview (Meteorologie)' => 'interviews/meteo/', 
            'Interview (Pharmazie)' => 'interviews/pharmazie/', 
            'Interview (Physik)' => 'interviews/physik/', 
            'Interview (sonstige)' => 'interviews/', 
            'Nachrichten' => 'nachrichten/', 
            'Praktikum' => 'praktikum/', 
            'Unterhaltung' => 'unterhaltung/', 
            'Veranstaltungen' => 'veranstaltungen/', 
            'Vorlesungen' => 'vorlesungen/', 
            'sonstiges' => 'sonstiges/', 
		);
        
        public function __construct($wikiSpecialPage){
            $this->specialPage = $wikiSpecialPage;
            $this->wikiUser = $wikiSpecialPage->getUser();
            $this->wikiOut = $wikiSpecialPage->getOutput();
            
            // Pfade
            #$this->tempFolder   = __DIR__ . '/tmp_uploads'; # Ordner zum Sammeln der Chunks bis Upload vollständig.
            $this->tempFolder   = '/tmp/rtv-video-uploads'; # Ordner zum Sammeln der Chunks bis Upload vollständig.
            $this->absHtdcs = '/home/riedbergtv/www.riedberg.tv'; # Pfad zum Webordner
            $this->webUploadFolder = '/rtv-videos'; # Ordner innerhalb des Webordners für die fertigen Uploads
            $this->uploadFolder = $this->absHtdcs . $this->webUploadFolder; # absoluter Pfad zu den fertigen Uploads
            
            if(!is_dir($this->tempFolder)){
                mkdir($this->tempFolder);
            }
			if(!is_dir($this->uploadFolder)){
                mkdir($this->uploadFolder);
            }

            $request = new SimpleRequest();
            $response = new SimpleResponse();
            parent::__construct($request, $response);
        }
        
        public function process(){
            // Erweiterung der Elternfunktion process()
            $post = $this->request->data('post');
            $get  = $this->request->data('get');
            unset($get['title']); // dirty MediaWiki fix: /wiki/foo -> /w/index.php?title=foo
            
            if (isset($get['allowed_filetarpathes'])){
                $this->echoJson(self::$allowed_filetarpathes);
                exit();
            }
            
            if (!empty($this->request->file())) {
                $this->handleChunk();
                if (isset($this->returnData))
                    $this->echoJson($this->returnData);
                
                // Fehlermeldung "Uncommitted DB writes": MediaWiki mag es wahrscheinlich nicht,
                // wenn man exit() aufruft. vgl. http://stackoverflow.com/a/22695318	
                $lb = wfGetLBFactory();
                $lb->shutdown();
                exit(); # prevent MediaWiki to output something
            } elseif(!empty($get)) {
                $this->handleTestChunk();
                if (isset($this->returnData)) 
				$this->echoJson($this->returnData);
                exit(); # prevent MediaWiki to output something
            }
            
            $this->pruneChunks(true); # [true=always|false=random base]. Other option is to implement a cron script
        }
        
        public function echoJson($data){
            header('Content-Type: application/json');
            echo json_encode($data);
        }
        
        protected function myUploadFolder($inputPath, $absoluteOutput=false){
            // Keine Überprüfung des Inputs, also nicht für Benutzereingaben geeignet!
            if ($absoluteOutput == true)
                return $this->uploadFolder . DIRECTORY_SEPARATOR . $inputPath;
            else
                return $this->webUploadFolder . DIRECTORY_SEPARATOR . $inputPath;
        }
        
        # This is the function we want to change, because we want to define the destination folder by POST parameter
        public function handleChunk(){
            #print_r($_FILES['file']);
            $file = $this->request->file();
            $identifier = $this->resumableParam('identifier');
            $filename = $this->resumableParam('filename');
            $chunkNumber = $this->resumableParam('chunkNumber');
            $chunkSize = $this->resumableParam('chunkSize');
            $totalSize = $this->resumableParam('totalSize');
            
            if ($file['error'] != 0){
                throw new Exception("Error: Unable to upload file (Error code ".$file['error'].")", E_WARNING);                
            }
            
            if (!$this->isChunkUploaded($identifier, $filename, $chunkNumber)) {
                $chunkFile = $this->tmpChunkDir($identifier) . DIRECTORY_SEPARATOR . $this->tmpChunkFilename($filename, $chunkNumber);
                
                $this->moveUploadedFile($file['tmp_name'], $chunkFile);
            }
            
        	if ($this->isFileUploadComplete($filename, $identifier, $chunkSize, $totalSize)){
				#print "FileUploadComplete!\n";	
				// Sicherheitsaspekte nach http://php.net/manual/de/function.move-uploaded-file.php
				$post = $this->request->data('post'); #!! $post['filename'] is different to $this->resumableParam('filename'); !!
				
				$new_filename = isset($post['filename']) ? $post['filename'] : substr(uniqid(),0,15);
				# Replace unallowed characters from filename string
				$new_filename = preg_replace("`[^-0-9A-Z_\.]+`i", '_', $post['filename']); 
				# Limit filename length
				$new_filename = substr($new_filename, 0, 255);
				# Remove file extension_loaded
				$new_filename = pathinfo($new_filename, PATHINFO_FILENAME);
				# Trim
				$new_filename = trim($new_filename);
				$subfolder4video = date("Y-m-d") . '_' . substr($new_filename, 0, 25);
				# Make sure there is a filename
				if (strlen($new_filename) < 3) $new_filename = date("Y-m-d") .'_'. $new_filename;
				# File extension added later
				$filename_suffix = '.orig.mp4';
				
				// Bestimme den Zielpfad
				if (in_array($post['filetarpath'], array_values(self::$allowed_filetarpathes)))
                    $new_target_dir = $post['filetarpath'];
				else
                    return $this->response->header(400); # 400 - Bad Request
                
				// Erstelle Unterordner für das Video
				$new_target_dir_abs = $this->myUploadFolder($new_target_dir . $subfolder4video . DIRECTORY_SEPARATOR, true);
				$new_target_dir_web = $this->myUploadFolder($new_target_dir . $subfolder4video . DIRECTORY_SEPARATOR, false);
                
				// Erzeuge zusammengesetzte Dateipfad und nutze neuen Dateiname
				$filepathname_abs = $new_target_dir_abs . $new_filename . $filename_suffix;
				$filepathname_web = $new_target_dir_web . $new_filename . $filename_suffix;
                $this->createFileAndDeleteTmp($identifier, $filepathname_abs);
                
				// Hack: Wechsle mit PHP in das Verzeichnis dieses Skripts.
				// Das ist wichtig, damit auch alle Shellskripte sich huebsch gegenseitig aufrufen koennen.
				chdir(__DIR__ );
                
				// Konvertierungsskript starten
				$usermail = $this->wikiUser->getEmail();
				$mail = isset($usermail) ? escapeshellarg(trim(strip_tags($usermail))) : 'elearning@th.physik.uni-frankfurt.de';
				$logfile = escapeshellarg($new_target_dir_abs . $subfolder4video . '.log');
				$errlogs = escapeshellarg($new_target_dir_abs . $subfolder4video . '.errors.log');
				$cmd = "./convert.sh '$filepathname_abs' '$new_target_dir_abs' '$mail' '$logfile' 1>'$logfile' 2>'$errlogs' &";
				$ret = exec($cmd);
				
				// info an Nutzer
				$this->wikiUser->sendMail(
                    "[riedberg.tv] Upload abgeschlossen & Konvertierung gestartet", 
                    "Der Upload ist abgeschlossen. Die Datei wurde in \"$filepathname_web'\" gespeichert und die Konvertierung gestartet. " 
                    +"Sobald diese abgeschlossen ist, erhältst du das vollständige Log-File."
				);
                
				// Thumbnailskript starten
				$input_time = preg_replace("`[^0-9\:]+`i", '', $post['filethumbtime']); 
				$cmd = "./thumbnails.sh '$filepathname_abs' '$new_target_dir_abs' '$input_time' 1>'$logfile' 2>'$errlogs'";
				exec($cmd);
                
                // Ermittle Videolänge mit ffprobe
                $cmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 -sexagesimal '$filepathname_abs'";
                $videoduration = exec($cmd); # Format HOURS:MM:SS.MICROSECONDS zB 0:00:30.024000
                $videoduration = explode('.', $videoduration); # split Microseconds from other stuff
                $videoduration = $videoduration[0]; # just keep the H:MM:SS
                
                // Wikiseite wird von SpecialPage angelegt
				$wikipage_title = isset($post['filewikititel']) ? trim(strip_tags($post['filewikititel'])) : 'Neues Video ohne Titel';
				$template_vars = array(
                    'VIDEO_TITLE'    => (isset($post['filevideotitel']) ? trim(strip_tags($post['filevideotitel'])) : 'VIDEO TITEL ändern!'),
                    'VIDEO_SUBTITLE' => (isset($post['filevideountertitel']) ? trim(strip_tags($post['filevideountertitel'])) : 'VIDEO UNTERTITEL ändern!'),
                    'LENGTH' => $videoduration,
                    'VIDEO_PATH' => $new_target_dir_web, //sowas wie "/videos/biologie/ordner"
                    'FILE_PREFIX' => $new_filename, //sowas wie "VideoMaentele (ohne Dateiendung)
                    'DATE' => date('d.m.Y g:i:s'),
				);
				$page = $this->specialPage->createWikiPage($wikipage_title, $template_vars);
				$title = $page->getTitle();
				$wikipage_link = $title->getCanonicalURL();
				$wikipage_editlink = $title->getEditURL();
                
				$this->returnData = array(
                    'thumbnail_webpath' => str_replace($filename_suffix, '', $filepathname_web) . '-thumb640.jpg',
                    'wikipage_title' => $wikipage_title,
                    'wikipage_link' => $wikipage_link,
                    'wikipage_editlink' => $wikipage_editlink,
                    'username' => $this->wikiUser->getName(),
                    'emailaddr'=> $this->wikiUser->getEmail(),
				);
            } 
            
            return $this->response->header(200);
        }
        
        # We need this function here, because Resumable did declare it private, 
        # but it has also been changed for our purposes
        protected function createFileAndDeleteTmp($identifier, $filepathname){
            $tmpFolder = new Folder($this->tmpChunkDir($identifier));
            $chunkFiles = $tmpFolder->read(true, true, true)[1];
            if ($this->createFileFromChunks($chunkFiles, $filepathname) && $this->deleteTmpFolder) {
                $tmpFolder->delete();
            }
        }
        
        # We need this function here, because Resumable did declare it private and later we also changed it for our purpose
        protected function resumableParam($shortName){
            $resumableParams = $_REQUEST;
            if (!isset($resumableParams['resumable' . ucfirst($shortName)])) {
                return null;
            }
            return $resumableParams['resumable' . ucfirst($shortName)];
        }
        
        # New functionality to delete old chunks, which did not upload completely
        public function pruneChunks($force=false, $expirationTime=172800, $folder=Null){
            if ($force || 1 == mt_rand(1, 100)){
                $chunksFolder = (isset($folder)) ? $folder : $this->tempFolder;
                
                $handle = opendir($chunksFolder);
                if (!$handle)
                    throw new Exception('Failed to open folder: '.$chunksFolder);
                
                while (false !== ($entry = readdir($handle))){
                    if ($entry == "." || $entry == ".." || $entry == ".gitignore" || $entry == ".htaccess")
                        continue;
                    
                    $path = $chunksFolder . DIRECTORY_SEPARATOR . $entry;
                    if (is_dir($path)){
                        # I decided to step into dir recursively to remove also files inside.
                        $this->pruneChunks(true, $expirationTime, $path); #continue;
                        try {
                            (new Folder($path))->delete();
                        } catch (Exception $e) {}
                    }
                    elseif (time() - filemtime($path) > $expirationTime)
                        unlink($path);
                }
                closedir($handle);
            }
        }
    }
