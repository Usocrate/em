<?php
function __autoload($class_name) {
	$path = '../../classes/';
	if (is_file ( $path . $class_name . '.class.php' )) {
		include_once $path . $class_name . '.class.php';
	} elseif ($path . $class_name . '.interface.php') {
		include_once $path . $class_name . '.interface.php';
	}
}

$system = new System ( '../../config/host.json' );

include_once '../inc/boot.php';

session_start();

$publishers = $system->getPublishersByName($_REQUEST['query']);

$items = array();
foreach ($publishers as $p) {
	$items[] = $p->toJson();
}

header('Content-type: text/plain; charset=UTF-8');
$output = '{"publishers":['.implode(',', $items).']}';
echo $output;
?>