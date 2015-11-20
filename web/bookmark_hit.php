<?php 
function __autoload($class_name) {
	$path = './classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( '../config/host.json' );

if (! $system->configFileExists()) {
	header ( 'Location:'.$system->getConfigUrl() );
	exit ();
}

include_once './inc/boot.php';
session_start();

$system->lookForAuthenticatedUser();

$b = $system->getBookmarkById($_REQUEST['bookmark_id']);

if (!($b instanceof Bookmark)) {
    header ('Location: '.$system->getProjectUrl());
	exit;
} elseif ($b->isPrivate() && !$system->isUserAuthenticated()) {
	//
	// Demande d'accès à ressource confidentielle sans authentification de l'utilisateur
	//
	header ('Location: '.$system->getProjectUrl());
	exit;
} else {
	if ($system->isUserAuthenticated()) {
		if(isset($_REQUEST['latitude']) && isset($_REQUEST['longitude'])) {
			$b->addHit($system->getAuthenticatedUserId(),$_REQUEST['latitude'],$_REQUEST['longitude']);
		} else {
			$b->addHit($system->getAuthenticatedUserId());
		}
	}
	header ('Location: '.$b->getUrl());
	exit;
}