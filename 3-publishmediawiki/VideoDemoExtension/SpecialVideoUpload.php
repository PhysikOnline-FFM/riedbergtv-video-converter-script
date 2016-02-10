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

		# diese Texte werden angezeigt, sobald der Benutzer eingeloggt
		# die Spezialseite besucht:

		$output->addWikiText("Dieser Text wird auf der Spezialseite ''VideoUpload'' immer angezeigt. Die [https://www.mediawiki.org/wiki/Manual:Special_pages MediaWiki-Hilfe über Spezialseiten] erläutert, wie Spezialseitenprogrammierung funktioniert.");
		$output->addHTML("<p>Um herauszufinden, was man mit diesen Objekten so machen kann, siehe die MediaWiki class reference: <a href='https://doc.wikimedia.org/mediawiki-core/master/php/classSpecialPage.html'>SpecialPage</a> und von dort zB. <a href='https://doc.wikimedia.org/mediawiki-core/master/php/classOutputPage.html'>OutputPage</a>, <a href='https://doc.wikimedia.org/mediawiki-core/master/php/classWebRequest.html'>WebRequest</a>.");

		# wir koennen hier auf alle moeglichen Objekte zugreifen.
		$user = $this->getUser();
		$username = $user->getName();
		$userpage = $user->getUserPage();
		$usermail = $user->getEmail();
		$output->addWikiText("Lieber [[$userpage|$username]], ich kann dir eine E-Mail an [mailto:$usermail $usermail] schicken.");

		# fuer Formulare gibt es High-Level-APIs, zB
		# https://www.mediawiki.org/wiki/HTMLForm
		# aber wir koennen sie auch selbst zusammenbasteln:

		$feldname = 'feld1';
		if($request->wasPosted()) {
			$text = $request->getText($feldname);

			$output->addWikiText($text ? "'''Danke für das Übermitteln von $text'''" : "Leider nichts eingegeben.");

			$user->sendMail(
				"Testbenutzung von SpecialVideoUpload auf RiedbergTV",
				"Vielen Dank, dass du $text eingegeben hast."
			);
		} else {
			$output->addHTML(<<<HTML
<form method="post">
<p>Nun ein kleines Formular: <input type="text" name="$feldname">
<p><input type="submit" value="Abschicken">
</form>
HTML
);
		}
	}
}


