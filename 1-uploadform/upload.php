<?php
/*
	TODO::
	- check file type
	- remove old folders of unsecussful upload attempts (low prio)

*/

require_once('vendor/autoload.php');

use Cake\Filesystem\File; 
use Cake\Filesystem\Folder; 
use Dilab\Network\SimpleRequest;
use Dilab\Network\SimpleResponse;
use Dilab\Resumable;
 
// Creating out own class of Resumable to overload some functions.
// The benefit is that we have not to change the original source code (Y).
class RTVResumable extends Resumable {
	
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
	
	# parent::__construct() has not to be called if RTVResumable does not define it's own constructor.
	
	public function rtvprocess(){
		// Erweiterung der Elternfunktion process()
		$get = $this->request->data('get');
		if (!empty($get)) {
			if (isset($get['allowed_filetarpathes'])){
				header('Content-Type: application/json');
				echo json_encode(self::$allowed_filetarpathes);
				exit();
			}
		}
		
		$this->process(); // parent process
		
		$this->pruneChunks(true); # on a random base. Other option is to implement a cron script
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
			
			# Replace unallowed characters from filename string
			$new_filename = preg_replace("`[^-0-9A-Z_\.]+`i", '_', $post['filename']); 
			# Limit filename length
			$new_filename = substr($new_filename, 0, 255);
			# Remove file extension_loaded
			$new_filename = pathinfo($new_filename, PATHINFO_FILENAME);
			# Trim
			$new_filename = trim($new_filename);
			# Make sure there is a filename
			$new_filename = date("Y-m-d") .'_'. $new_filename;
			# Add mp4 always
			$new_filename .= '.mp4';
			
			// Bestimme den Zielpfad
			if (in_array($post['filetarpath'], array_values(self::$allowed_filetarpathes)))
				$new_target_dir = $this->uploadFolder . DIRECTORY_SEPARATOR . $post['filetarpath'];
			else
				$new_target_dir = $this->uploadFolder . DIRECTORY_SEPARATOR . 'forbidden_tarpath/';
			
			// Erzeuge zusammengesetzte Datei und nutze neuen Dateiname
            $this->createFileAndDeleteTmp($identifier, $new_filename, $new_target_dir);
        }

        return $this->response->header(200);
    }
	
	# We need this function here, because Resumable did declare it private, 
	# but it has also been changed for our purposes
	protected function createFileAndDeleteTmp($identifier, $filename, $dirname){
        $tmpFolder = new Folder($this->tmpChunkDir($identifier));
        $chunkFiles = $tmpFolder->read(true, true, true)[1];
        if ($this->createFileFromChunks($chunkFiles, $dirname . $filename) 
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
					# TODO:: The dir itself will not be deleted this way, but it needs approximately no space, so it is ok for me at this time.
					$this->pruneChunks(true, $expirationTime, $path); #continue;
				}
				elseif (time() - filemtime($path) > $expirationTime)
					unlink($path);
			}
			closedir($handle);
		}
	}
}

$request = new SimpleRequest();
$response = new SimpleResponse();

$resumable = new RTVResumable ($request, $response);
$resumable->tempFolder   = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tmp';
$resumable->uploadFolder = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$resumable->rtvprocess();