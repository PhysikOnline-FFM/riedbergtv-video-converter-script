<?php
/*
	TODO::
	- check why thumbnail generation does not work
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
	private $wikiUser = null;
	private $wikiOut = null;

	public static $allowed_filetarpathes = array(
		'Campus Riedberg' => 'campus/riedberg/', 
		'Interview (Biologie)' => 'interviews/biologie/', 
		'Interview (Chemie)' => 'interviews/chemie/', 
		'Interview (Meteorologie)' => 'interviews/meteo/', 
		'Interview (Pharmazie)' => 'interviews/pharmazie/', 
		'Interview (Physik)' => 'interviews/physik/', 
		'Interview (sonstige)' => 'interviews/', 
		'Nachrichten' => 'nachrichten/', 
		'Unterhaltung' => 'unterhaltung/', 
		'Veranstaltungen' => 'veranstaltungen/', 
		'sonstiges' => 'sonstiges/', 
		);
	
	public function __construct($wikiUser, $wikiOutput){
		$this->wikiUser = $wikiUser;
		$this->wikiOut = $wikiOutput;
		/* $username = $this->wikiUser->getName();
		$userpage = $this->wikiUser->getUserPage();
		$usermail = $this->wikiUser->getEmail();
		$this->wikiOut->addWikiText("Lieber [[$userpage|$username]], ich kann dir eine E-Mail an [mailto:$usermail $usermail] schicken."); */
		
		$this->tempFolder   = '/tmp';
		$this->uploadFolder = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
		
		$request = new SimpleRequest();
		$response = new SimpleResponse();
		parent::__construct($request, $response);
	}
	
	public function process(){
		// Erweiterung der Elternfunktion process()
		$get = $this->request->data('get');
		if (!empty($get)) {
			if (isset($get['allowed_filetarpathes'])){
				$this->echoJson(self::$allowed_filetarpathes);
				exit();
			}
		}
		
		// Dirty fix for MediaWiki integration:
		// by default their is a GET Parameter 'title'
		// dump = $this->resumableParams();
		// var_dump($dump);
		$params = $this->resumableParams();
		unset($params['title']);
		if (!empty($params)) {
            if (!empty($this->request->file())) {
                $this->handleChunk();
            } else {
                $this->handleTestChunk();
            }
			
			if (isset($this->returnData)){
				$this->echoJson($this->returnData);
			}
			exit(); # to prevent MediaWiki to return html output.
        }
		
		$this->pruneChunks(true); # on a random base. Other option is to implement a cron script
	}

	public function echoJson($data){
		header('Content-Type: application/json');
		echo json_encode($data);
	}
	
	# This is the thing we want to change, because we want to define the destination folder by POST parameter
	# here also an email could been sent to the person
	public function handleChunk(){
		$file = $this->request->file();
		$identifier = $this->resumableParam('identifier');
		$filename = $this->resumableParam('filename');
		$chunkNumber = $this->resumableParam('chunkNumber');
		$chunkSize = $this->resumableParam('chunkSize');
		$totalSize = $this->resumableParam('totalSize');

		if (!$this->isChunkUploaded($identifier, $filename, $chunkNumber)) {
			$chunkFile = $this->tmpChunkDir($identifier) . DIRECTORY_SEPARATOR . $this->tmpChunkFilename($filename, $chunkNumber);
			$this->moveUploadedFile($file['tmp_name'], $chunkFile);
		}

        if ($this->isFileUploadComplete($filename, $identifier, $chunkSize, $totalSize)) {
			
			// Sicherheitsaspekte nach http://php.net/manual/de/function.move-uploaded-file.php
			$post = $this->request->data('post'); # $post['filename'] is different to $this->resumableParam('filename'); !!
			
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
			# Add mp4 extension always
			$new_filename .= '.mp4';
			
			// Bestimme den Zielpfad
			if (in_array($post['filetarpath'], array_values(self::$allowed_filetarpathes)))
				$new_target_dir = $this->uploadFolder . DIRECTORY_SEPARATOR . $post['filetarpath'];
			else
				$new_target_dir = $this->uploadFolder . DIRECTORY_SEPARATOR . 'forbidden_tarpath/';
			// Erstelle Unterordner für das Video
			$new_target_dir = $new_target_dir . $subfolder4video . DIRECTORY_SEPARATOR;

			// Erzeuge zusammengesetzte Datei und nutze neuen Dateiname
			$filepathname = $new_target_dir . $new_filename;
            $this->createFileAndDeleteTmp($identifier, $filepathname);
			
			// Konvertierungsskript starten
			$usermail = $user->getEmail();
			$mail = isset($usermail) ? escapeshellarg(trim(strip_tags($usermail))) : 'elearning@th.physik.uni-frankfurt.de';
			$logfile = $new_target_dir . $subfolder4video . '.log';
			$cmd = "./convert.sh '$filepathname' '$new_target_dir' '$mail' '$logfile' > '$logfile' &";
			$ret = exec($cmd);
			// debug $this->returnData = array($cmd, $ret); 
			
			// info an Nutzer
			mail($mail, "[riedberg.tv] Upload abgeschlossen & Konvertierung gestartet", 
						"Der Upload ist abgeschlossen. Die Datei wurde in \"$filepathname'\" gespeichert und die Konvertierung gestartet. " 
						+"Sobald diese abgeschlossen ist, erhältst du das vollständige Log-File.");
						
			// Thumbnailskript starten
			$input_time = preg_replace("`[^0-9\:]+`i", '', $post['filethumbtime']); 
			$cmd = "./thumbnails.sh '$filepathname' '$new_target_dir' '$input_time' 1 2 > &1";
			exec($cmd);
			$this->returnData = array('thumbnail' => $new_target_dir . basename($filepathname) . "-v1-thumb640.jpg", 'cmd' => $cmd);
        }

        return $this->response->header(200);
    }
	
	# We need this function here, because Resumable did declare it private, 
	# but it has also been changed for our purposes
	protected function createFileAndDeleteTmp($identifier, $filepathname){
        $tmpFolder = new Folder($this->tmpChunkDir($identifier));
        $chunkFiles = $tmpFolder->read(true, true, true)[1];
        if ($this->createFileFromChunks($chunkFiles, $filepathname) 
			&& $this->deleteTmpFolder) {
            $tmpFolder->delete();
        }
    }
	
	# We need this function here, because Resumable did declare it private
	protected function resumableParam($shortName){
        $resumableParams = $this->resumableParams();
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