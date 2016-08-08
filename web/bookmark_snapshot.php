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
} else {
	if ($system->isUserAuthenticated()) {
		$response = $b->getSnapshotFromPhantomJS();
		//echo json_encode($response);
		//*
		echo '<html>';
		echo '<body>';
		foreach ($response as $r) {
		  echo '<p>'.$r.'</p>';    
		}
		echo '<body>';
		echo '</html>';
		//*/
	}
}