<?php
function __autoload($class_name) {
	$path = '../classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( '../config/host.json' );

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