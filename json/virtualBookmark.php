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

if (isset($_REQUEST['url'])) {
	$url = $_REQUEST['url'];
}

header('Content-type: text/plain; charset=UTF-8');
//header('Content-type: text/javascript; charset=UTF-8');

if (isset($_REQUEST['callback'])) {
	echo $_REQUEST['callback'].'(';
}

$b = new Bookmark();
$b->hydrateFromUrl($url);

echo $b->toJson();
if (isset($_REQUEST['callback'])) {
	echo ');';
}