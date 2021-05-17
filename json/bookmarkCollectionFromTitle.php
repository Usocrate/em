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

include_once '../inc/boot.php';

session_start();

$system->lookForAuthenticatedUser();

if (isset($_REQUEST['pattern'])) {
    $pattern = $_REQUEST['pattern'];
}

header('Content-type: text/plain; charset=UTF-8');
//header('Content-type: text/javascript; charset=UTF-8');

echo BookmarkCollection::getFromTitle($pattern)->toJson();