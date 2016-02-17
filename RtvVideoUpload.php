<?php

# Gestartet am 10. Februar 2016 von Sven als Beispiel fuer eine Extension

if(!defined('MEDIAWIKI')) {
    echo 'This extension is supposed to be run throught MediaWiki';
    exit(1);
}

$wgExtensionCredits['validextensionclass'][] = array(
    'path' => __FILE__,
    'name' => 'RtvVideoUpload',
    'author' => 'Sven K, Philip A', 
    'url' => 'https://riedberg.tv/wiki/RiedbergTV:Extension',
    'description' => 'Dies ist unsere eigene RiedbergTV Extension zum Hochladen und Konvertieren fertiger Videos.',
    'type' => 'specialpage',
    'version'  => 0.1,
    'license-name' => 'GPL-2.0+'
);


$wgResourceModules['ext.RtvVideoUpload'] = array(
	'styles' => array(
		'RtvVideoUpload.css'  => array( 'media' => 'all' ),
	),
	'scripts' => array(
		'resumable.js',
		'RtvVideoUpload.js',
	),
	'dependencies' => array(
		'jquery',
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'RtvVideoUpload',
);

$wgAutoloadClasses['SpecialVideoUpload'] = __DIR__ . '/SpecialVideoUpload.php';
$wgMessagesDirs['RtvVideoUpload'] = __DIR__ .'/i18n';
$wgSpecialPages['VideoUpload'] = 'SpecialVideoUpload';