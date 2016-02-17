<?php

class SpecialVideoUpload extends SpecialPage {
	function __construct() {
		// Benutzer brauchen "edit"-Rechte in MediaWiki, um diese
		// Spezialseite benutzen zu können.
		parent::__construct('VideoUpload', 'edit');
	}

	function getGroupName() {
		return 'media'; // Auflistung unter Spezial:Spezialseiten
	}

	function execute($par) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

		if(!$this->userCanExecute( $this->getUser())) {
			$this->displayRestrictionError();
			return;
		}
		
		$output->addModules('ext.RtvVideoUpload');
		
		# diese Texte werden angezeigt, sobald der Benutzer eingeloggt
		# die Spezialseite besucht:
		
		$output->addHTML("<p>Um herauszufinden, was man mit diesen Objekten so machen kann, siehe die MediaWiki class reference: <a href='https://doc.wikimedia.org/mediawiki-core/master/php/classSpecialPage.html'>SpecialPage</a> und von dort zB. <a href='https://doc.wikimedia.org/mediawiki-core/master/php/classOutputPage.html'>OutputPage</a>, <a href='https://doc.wikimedia.org/mediawiki-core/master/php/classWebRequest.html'>WebRequest</a>.");

		# wir koennen hier auf alle moeglichen Objekte zugreifen.
		$user = $this->getUser();
		$username = $user->getName();
		$userpage = $user->getUserPage();
		$usermail = $user->getEmail();
		$output->addWikiText("Lieber [[$userpage|$username]], ich kann dir eine E-Mail an [mailto:$usermail $usermail] schicken.");

		$output->addHTML(<<<HTML
<div class="row">
	<noscript>
	<div class="alert alert-danger">
		<h3>Diese Seite benötigt JavaScript &ndash; bitte aktivere es!</h3>
	</div>
	</noscript>
	<div id="notSupported" class="alert alert-danger hide fade">
		<h3>Browser wird nicht unterstützt</h3>
		Dein Browser bietet nicht die notwendigen Funktionen, um diese Upload-Seite zu nutzen. Bitte probiere es mit Firefox oder Chrome.
	</div>

	<div class="col-sm-12">
		<button type="button" class="btn btn-info" aria-label="Add file" id="add-file-btn">
			<span class="glyphicon glyphicon-file" aria-hidden="true"></span>&nbsp;
			<span class="text">Datei auswählen</span>
		</button>
		<button type="button" class="btn btn-success pull-right" aria-label="Start upload" id="start-upload-btn">
			<span class="glyphicon glyphicon-upload" aria-hidden="true"></span>&nbsp;
			<span class="text">Upload starten</span>
		</button>
		<button type="button" class="btn btn-default pull-right hide" aria-label="Pause upload" id="pause-upload-btn" style="margin-right: 6px">
			<span class="glyphicon glyphicon-pause" aria-hidden="true"></span>&nbsp;
			<span class="text">Pausieren</span>
		</button>
	</div>
		
	<div class="col-sm-12">
		<div id="dropzone" class="dropzone text-center">
			<h1 style="margin:0">Drag & Drop here</h1>
		</div>
	</div>

	<div class="col-sm-12">
		<div id="sharedAlertContainerFiles"></div>
		<ul class="list-group" id="file-list"></ul>
	</div>
</div>
HTML
);
	}
}

