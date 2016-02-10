<?php

# Gestartet am 10. Februar 2016 von Sven als Beispiel fuer eine Extension

if(!defined('MEDIAWIKI')) {
    echo 'This extension is supposed to be run throught MediaWiki';
    exit(1);
}

$wgExtensionCredits['validextensionclass'][] = array(
    'path' => __FILE__,
    'name' => 'VideoDemoExtension',
    'author' => 'Sven K', 
    'url' => 'https://riedberg.tv/wiki/RiedbergTV:Extension',
    'description' => 'Dies ist ein Beispiel, wie man Extensions schreibt fuer RiedbergTV',
    'type' => 'specialpage',
    'version'  => 0.1,
    'license-name' => 'GPL-2.0+'
);

#$wgAutoloadClasses['MyExtension'] = __DIR__ . '/VideoDemoExtension.body.php';
$wgAutoloadClasses['SpecialVideoUpload'] = __DIR__ . '/SpecialVideoUpload.php';

$wgMessagesDirs['VideoDemoExtension'] = __DIR__ .'/i18n';

$wgSpecialPages['VideoUpload'] = 'SpecialVideoUpload';
