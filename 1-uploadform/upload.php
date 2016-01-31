<?php
require_once('vendor/autoload.php');

use Cake\Filesystem\File; 
use Dilab\Network\SimpleRequest;
use Dilab\Network\SimpleResponse;
use Dilab\Resumable;
 
// Creating out own class of Resumable to overload some functions.
// The benefit is that we have not to change the original source code (Y).
class RTVResumable extends Resumable {
	
	# parent::__construct() has not to be called if RTVResumable does not define it's own constructor.
	
	# We need this function here, but Resumable did declare it private
	protected function resumableParam($shortName){
        $resumableParams = $this->resumableParams();
        if (!isset($resumableParams['resumable' . ucfirst($shortName)])) {
            return null;
        }
        return $resumableParams['resumable' . ucfirst($shortName)];
    }
	
	# This is the thing we want to change, because we want to define the destination folder by POST parameter
	# here also an email could been sent to the person
	public function moveUploadedFile($file, $destFile){
        $file = new File($file);
        if ($file->exists()) {
            return $file->copy($destFile);
        }
        return false;
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
				if (time() - filemtime($path) > $expirationTime)
					unlink($path);
			}
			closedir($handle);
		}
	}
}

$request = new SimpleRequest();
$response = new SimpleResponse();

$resumable = new RTVResumable ($request, $response);
$resumable->tempFolder = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tmp';
$resumable->uploadFolder = __DIR__ . DIRECTORY_SEPARATOR .'uploads';
$resumable->process();
$resumable->pruneChunks(true); # on a random base. Other option is to implement a cron script