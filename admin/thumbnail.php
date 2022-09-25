<?php
require_once '../classes/System.class.php';
$system = new System('../config/host.json');

if (! $system->configFileExists ()) {
	header ( 'Location:' . $system->getConfigUrl () );
	exit ();
}

include_once '../inc/boot.php';

session_start ();

if (! $system->isUserAuthenticated ()) {
	header ( 'Location:' . $system->getLoginUrl() );
	exit ();
}

if ($handle = opendir ( Bookmark::getSnapshotDirectoryPath () )) {
	while ( false !== ($file = readdir ( $handle )) ) {
		if ($file != "." && $file != "..") {
			echo $file . '<br/>';
			$id = substr ( $file, 0, - 4 );
			$b = new Bookmark ( $id );
			$b->setSnapshotFileName ( $file );
			$b->toDB ();
		}
	}
	closedir ( $handle );
}