<?php 
require_once './classes/System.class.php';
$system = new System('./config/host.json');

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
		$response = $b->getSnapshot();
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